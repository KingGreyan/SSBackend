<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodoController extends Controller
{
    /**
     * Get all todos for authenticated user (with optional filters)
     */
    public function index(Request $request)
    {
        $query = Todo::where('user_id', Auth::id())
                    ->orderBy('date', 'asc')
                    ->orderBy('time', 'asc');

        // Filter by date if provided
        if ($request->has('date')) {
            $query->forDate($request->date);
        }

        // Filter by completed status
        if ($request->has('completed')) {
            if ($request->completed == 'true' || $request->completed == 1) {
                $query->completed();
            } else {
                $query->incomplete();
            }
        }

        return response()->json($query->get());
    }

    /**
     * Get todos for a specific month
     */
    public function getByMonth($year, $month)
    {
        $todos = Todo::where('user_id', Auth::id())
                    ->forMonth($year, $month)
                    ->orderBy('date', 'asc')
                    ->orderBy('time', 'asc')
                    ->get();

        // Group by date for easier frontend consumption
        $grouped = $todos->groupBy(function($todo) {
            return $todo->date->format('Y-m-d');
        });

        return response()->json([
            'todos' => $todos,
            'grouped' => $grouped
        ]);
    }

    /**
     * Create a new todo
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'priority' => 'nullable|in:low,medium,high',
            'category' => 'nullable|string|max:100',
        ]);

        $validated['user_id'] = Auth::id();

        $todo = Todo::create($validated);

        return response()->json([
            'message' => 'Todo created successfully',
            'todo' => $todo
        ], 201);
    }

    /**
     * Update a todo
     */
    public function update(Request $request, $id)
    {
        $todo = Todo::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'sometimes|required|date',
            'time' => 'nullable|date_format:H:i',
            'completed' => 'sometimes|boolean',
            'priority' => 'nullable|in:low,medium,high',
            'category' => 'nullable|string|max:100',
        ]);

        $todo->update($validated);

        return response()->json([
            'message' => 'Todo updated successfully',
            'todo' => $todo
        ]);
    }

    /**
     * Toggle completion status
     */
    public function toggleComplete($id)
    {
        $todo = Todo::where('user_id', Auth::id())->findOrFail($id);
        $todo->completed = !$todo->completed;
        $todo->save();

        return response()->json([
            'message' => 'Todo status toggled',
            'todo' => $todo
        ]);
    }

    /**
     * Delete a todo
     */
    public function destroy($id)
    {
        $todo = Todo::where('user_id', Auth::id())->findOrFail($id);
        $todo->delete();

        return response()->json([
            'message' => 'Todo deleted successfully'
        ]);
    }
}
