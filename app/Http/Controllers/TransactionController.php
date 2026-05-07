<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = auth()->user()->transactions()
            ->with('category');

        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        return response()->json(
            $query->orderBy('date', 'desc')
                ->latest()
                ->get()
        );
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
            'amount' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:income,expense',
            'note' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        if (!isset($validated['date'])) {
            $validated['date'] = now();
        }

        $transaction = auth()->user()->transactions()->create($validated);
        $transaction->load('category');

        return response()->json($transaction, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $transaction = auth()->user()->transactions()->with('category')->findOrFail($id);
        return response()->json($transaction);
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
        $transaction = auth()->user()->transactions()->findOrFail($id);

        $validated = $request->validate([
            'amount' => 'numeric',
            'category_id' => 'exists:categories,id',
            'type' => 'in:income,expense',
            'note' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        $transaction->update($validated);
        $transaction->load('category');

        return response()->json($transaction);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $transaction = auth()->user()->transactions()->findOrFail($id);
        $transaction->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
