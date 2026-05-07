<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            // Expense Categories
            ['name' => 'Food & Dining', 'type' => 'expense', 'icon' => 'restaurant', 'color' => '#FF6B6B'],
            ['name' => 'Transportation', 'type' => 'expense', 'icon' => 'directions_car', 'color' => '#4ECDC4'],
            ['name' => 'Shopping', 'type' => 'expense', 'icon' => 'shopping_bag', 'color' => '#95E1D3'],
            ['name' => 'Bills & Utilities', 'type' => 'expense', 'icon' => 'receipt', 'color' => '#F38181'],
            ['name' => 'Entertainment', 'type' => 'expense', 'icon' => 'movie', 'color' => '#AA96DA'],
            ['name' => 'Healthcare', 'type' => 'expense', 'icon' => 'local_hospital', 'color' => '#FCBAD3'],
            ['name' => 'Education', 'type' => 'expense', 'icon' => 'school', 'color' => '#A8D8EA'],
            ['name' => 'Other Expenses', 'type' => 'expense', 'icon' => 'more_horiz', 'color' => '#C7CEEA'],

            // Income Categories
            ['name' => 'Salary', 'type' => 'income', 'icon' => 'account_balance_wallet', 'color' => '#4CAF50'],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => 'work', 'color' => '#8BC34A'],
            ['name' => 'Investment', 'type' => 'income', 'icon' => 'trending_up', 'color' => '#00BCD4'],
            ['name' => 'Gift', 'type' => 'income', 'icon' => 'card_giftcard', 'color' => '#FF9800'],
            ['name' => 'Other Income', 'type' => 'income', 'icon' => 'attach_money', 'color' => '#9C27B0'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
