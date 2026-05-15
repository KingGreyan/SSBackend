'use client';

import { useEffect, useState } from 'react';
import { useTransactions, useCategories } from '@/lib/hooks/useData';
import { createClient } from '@/lib/supabase/client';
import Link from 'next/link';
import { redirect } from 'next/navigation';

export default function Dashboard() {
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const { transactions } = useTransactions();
  const { categories } = useCategories();

  useEffect(() => {
    const checkAuth = async () => {
      const supabase = createClient();
      const {
        data: { user },
      } = await supabase.auth.getUser();

      if (!user) {
        redirect('/auth/login');
      }
      setUser(user);
      setLoading(false);
    };

    checkAuth();
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-lg text-foreground">Loading...</div>
      </div>
    );
  }

  const expenses = transactions.filter((t: any) => t.type === 'expense');
  const income = transactions.filter((t: any) => t.type === 'income');

  const totalExpenses = expenses.reduce((sum: number, t: any) => sum + parseFloat(t.amount), 0);
  const totalIncome = income.reduce((sum: number, t: any) => sum + parseFloat(t.amount), 0);
  const balance = totalIncome - totalExpenses;

  const expensesByCategory = expenses.reduce(
    (acc: Record<string, number>, t: any) => {
      const cat = categories.find((c: any) => c.id === t.category_id);
      const name = cat?.name || 'Uncategorized';
      acc[name] = (acc[name] || 0) + parseFloat(t.amount);
      return acc;
    },
    {}
  );

  const recentTransactions = transactions.slice(0, 5);

  return (
    <div className="min-h-screen bg-background">
      <header className="border-b border-border">
        <div className="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
          <h1 className="text-3xl font-bold text-foreground">Smart Spending</h1>
          <div className="flex items-center gap-4">
            <span className="text-sm text-muted-foreground">{user?.email}</span>
            <Link href="/profile" className="text-sm text-primary hover:underline">
              Profile
            </Link>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-12">
        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
          <div className="bg-card border border-border rounded-lg p-6">
            <h3 className="text-sm font-medium text-muted-foreground mb-2">Total Income</h3>
            <p className="text-3xl font-bold text-green-600">${totalIncome.toFixed(2)}</p>
          </div>
          <div className="bg-card border border-border rounded-lg p-6">
            <h3 className="text-sm font-medium text-muted-foreground mb-2">Total Expenses</h3>
            <p className="text-3xl font-bold text-red-600">${totalExpenses.toFixed(2)}</p>
          </div>
          <div className="bg-card border border-border rounded-lg p-6">
            <h3 className="text-sm font-medium text-muted-foreground mb-2">Balance</h3>
            <p className={`text-3xl font-bold ${balance >= 0 ? 'text-green-600' : 'text-red-600'}`}>
              ${balance.toFixed(2)}
            </p>
          </div>
        </div>

        {/* Navigation Links */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-12">
          <Link
            href="/transactions"
            className="bg-primary text-primary-foreground rounded-lg p-6 hover:opacity-90 transition"
          >
            <h2 className="text-xl font-semibold mb-2">Manage Transactions</h2>
            <p className="text-sm opacity-90">Add, view, and edit your spending</p>
          </Link>
          <Link
            href="/ai-chat"
            className="bg-primary text-primary-foreground rounded-lg p-6 hover:opacity-90 transition"
          >
            <h2 className="text-xl font-semibold mb-2">AI Spending Insights</h2>
            <p className="text-sm opacity-90">Chat with AI for recommendations</p>
          </Link>
          <Link
            href="/todos"
            className="bg-primary text-primary-foreground rounded-lg p-6 hover:opacity-90 transition"
          >
            <h2 className="text-xl font-semibold mb-2">Todo Manager</h2>
            <p className="text-sm opacity-90">Track your tasks and goals</p>
          </Link>
          <Link
            href="/categories"
            className="bg-primary text-primary-foreground rounded-lg p-6 hover:opacity-90 transition"
          >
            <h2 className="text-xl font-semibold mb-2">Categories</h2>
            <p className="text-sm opacity-90">Organize your spending</p>
          </Link>
        </div>

        {/* Expenses by Category */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
          <div className="bg-card border border-border rounded-lg p-6">
            <h2 className="text-xl font-semibold mb-6 text-foreground">Expenses by Category</h2>
            {Object.entries(expensesByCategory).length > 0 ? (
              <ul className="space-y-3">
                {Object.entries(expensesByCategory).map(([name, amount]) => (
                  <li key={name} className="flex justify-between items-center">
                    <span className="text-foreground">{name}</span>
                    <span className="font-semibold text-foreground">${(amount as number).toFixed(2)}</span>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-muted-foreground">No expenses yet</p>
            )}
          </div>

          {/* Recent Transactions */}
          <div className="bg-card border border-border rounded-lg p-6">
            <h2 className="text-xl font-semibold mb-6 text-foreground">Recent Transactions</h2>
            {recentTransactions.length > 0 ? (
              <ul className="space-y-3">
                {recentTransactions.map((transaction: any) => (
                  <li key={transaction.id} className="flex justify-between items-center pb-3 border-b border-border last:border-0">
                    <div>
                      <p className="font-medium text-foreground">{transaction.title}</p>
                      <p className="text-xs text-muted-foreground">
                        {new Date(transaction.date).toLocaleDateString()}
                      </p>
                    </div>
                    <span
                      className={`font-semibold ${
                        transaction.type === 'income' ? 'text-green-600' : 'text-red-600'
                      }`}
                    >
                      {transaction.type === 'income' ? '+' : '-'}${Math.abs(parseFloat(transaction.amount)).toFixed(2)}
                    </span>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-muted-foreground">No transactions yet</p>
            )}
          </div>
        </div>
      </main>
    </div>
  );
}
