<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('type')->constrained()->onDelete('cascade');
        });

        // Migrate existing category strings to category_id
        $transactions = DB::table('transactions')->get();
        foreach ($transactions as $transaction) {
            if ($transaction->category) {
                // Try to find matching category by name and type
                $category = DB::table('categories')
                    ->where('name', $transaction->category)
                    ->where('type', $transaction->type)
                    ->first();

                // If not found, create a new category or use "Other" category
                if (!$category) {
                    $otherCategoryName = $transaction->type === 'income' ? 'Other Income' : 'Other Expenses';
                    $category = DB::table('categories')
                        ->where('name', $otherCategoryName)
                        ->where('type', $transaction->type)
                        ->first();
                }

                if ($category) {
                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update(['category_id' => $category->id]);
                }
            }
        }

        // Drop the old category column
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('category')->nullable();
        });

        // Restore category names from category_id
        $transactions = DB::table('transactions')->get();
        foreach ($transactions as $transaction) {
            if ($transaction->category_id) {
                $category = DB::table('categories')->find($transaction->category_id);
                if ($category) {
                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update(['category' => $category->name]);
                }
            }
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
