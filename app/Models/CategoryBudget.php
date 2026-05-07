<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'budget_limit',
        'period',
    ];

    protected $casts = [
        'budget_limit' => 'decimal:2',
    ];

    /**
     * Get the user that owns the budget.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for this budget.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get current spending for this budget period.
     */
    public function getCurrentSpending()
    {
        $query = $this->user->transactions()
            ->where('category_id', $this->category_id)
            ->where('type', 'expense');

        if ($this->period === 'monthly') {
            $query->whereMonth('date', now()->month)
                  ->whereYear('date', now()->year);
        } else {
            $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        return $query->sum('amount');
    }

    /**
     * Check if budget is exceeded.
     */
    public function isExceeded()
    {
        return $this->getCurrentSpending() > $this->budget_limit;
    }

    /**
     * Get budget status percentage.
     */
    public function getPercentage()
    {
        if ($this->budget_limit == 0) return 0;
        return ($this->getCurrentSpending() / $this->budget_limit) * 100;
    }
}
