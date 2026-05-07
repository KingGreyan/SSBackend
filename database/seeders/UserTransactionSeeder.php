<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;

class UserTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userId = 3;
        $startDate = Carbon::create(2026, 1, 1);
        $endDate = now();

        // Fetch Categories
        $incomeCategories = Category::where('type', 'income')->pluck('id')->toArray();
        $expenseCategories = Category::where('type', 'expense')->pluck('id')->toArray();

        if (empty($incomeCategories) || empty($expenseCategories)) {
            $this->command->warn("Categories not found. Please seed categories first.");
            return;
        }

        $this->command->info("Seeding transactions for User ID: $userId from $startDate to $endDate");

        $transactions = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            // Randomly decide how many transactions for this day (0 to 3)
            $dailyTransactionCount = rand(0, 3);

            for ($i = 0; $i < $dailyTransactionCount; $i++) {
                // 80% chance of expense, 20% income
                $isExpense = rand(1, 100) <= 80;

                if ($isExpense) {
                    $categoryId = $expenseCategories[array_rand($expenseCategories)];
                    $amount = rand(10, 500) + (rand(0, 99) / 100); // 10.00 to 500.99
                    $type = 'expense';
                    $note = 'Random expense via Seeder';
                } else {
                    $categoryId = $incomeCategories[array_rand($incomeCategories)];
                    $amount = rand(1000, 5000) + (rand(0, 99) / 100); // 1000.00 to 5000.99
                    $type = 'income';
                    $note = 'Random income via Seeder';
                }

                $transactions[] = [
                    'user_id' => $userId,
                    'category_id' => $categoryId,
                    'type' => $type,
                    'amount' => $amount,
                    'date' => $currentDate->format('Y-m-d'),
                    'note' => $note,
                    'created_at' => $currentDate->format('Y-m-d H:i:s'),
                    'updated_at' => $currentDate->format('Y-m-d H:i:s'),
                ];
            }

            $currentDate->addDay();
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($transactions, 100) as $chunk) {
            Transaction::insert($chunk);
        }

        $this->command->info("Inserted " . count($transactions) . " transactions.");
    }
}
