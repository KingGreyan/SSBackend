<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $categories = $query->orderBy('type')->orderBy('name')->get();

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'type' => 'in:income,expense',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category is being used in transactions
        if ($category->transactions()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category that is being used in transactions'
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }

    /**
     * Get budget status for all categories
     */
    public function getBudgetStatus()
    {
        $user = auth()->user();
        $budgets = $user->categoryBudgets()->with('category')->get();

        return response()->json($budgets->map(function ($budget) {
            return [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category_name' => $budget->category->name,
                'budget_limit' => (float) $budget->budget_limit,
                'period' => $budget->period,
                'spent' => (float) $budget->getCurrentSpending(),
                'remaining' => (float) ($budget->budget_limit - $budget->getCurrentSpending()),
                'percentage' => round($budget->getPercentage(), 2),
                'status' => $budget->isExceeded() ? 'exceeded' : 
                           ($budget->getPercentage() > 80 ? 'warning' : 'ok'),
            ];
        }));
    }

    /**
     * Set or update budget for a category
     */
    public function setBudget(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'budget_limit' => 'required|numeric|min:0',
            'period' => 'in:monthly,weekly',
        ]);

        $user = auth()->user();

        $budget = \App\Models\CategoryBudget::updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $validated['category_id'],
            ],
            [
                'budget_limit' => $validated['budget_limit'],
                'period' => $validated['period'] ?? 'monthly',
            ]
        );

        return response()->json([
            'message' => 'Budget set successfully',
            'budget' => $budget,
        ]);
    }

    /**
     * Delete a budget
     */
    public function deleteBudget($id)
    {
        $user = auth()->user();
        $budget = \App\Models\CategoryBudget::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $budget->delete();

        return response()->json(['message' => 'Budget deleted successfully']);
    }
}
