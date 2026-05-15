'use client';

import { useState, useEffect } from 'react';
import { useCategories } from '@/lib/hooks/useData';
import { createClient } from '@/lib/supabase/client';
import Link from 'next/link';
import { redirect } from 'next/navigation';

export default function CategoriesPage() {
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [name, setName] = useState('');
  const [type, setType] = useState('expense');
  const [color, setColor] = useState('#3b82f6');
  const [submitting, setSubmitting] = useState(false);
  const { categories, mutateCategories } = useCategories();

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

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);

    try {
      const response = await fetch('/api/categories', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, type, color }),
      });

      if (response.ok) {
        setName('');
        setColor('#3b82f6');
        mutateCategories();
      } else {
        const error = await response.json();
        alert(error.error || 'Failed to create category');
      }
    } catch (error) {
      console.error('Error creating category:', error);
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (id: string) => {
    try {
      await fetch(`/api/categories/${id}`, { method: 'DELETE' });
      mutateCategories();
    } catch (error) {
      console.error('Error deleting category:', error);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-lg text-foreground">Loading...</div>
      </div>
    );
  }

  const expenseCategories = categories.filter((c: any) => c.type === 'expense');
  const incomeCategories = categories.filter((c: any) => c.type === 'income');

  return (
    <div className="min-h-screen bg-background">
      <header className="border-b border-border">
        <div className="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
          <div className="flex items-center gap-4">
            <Link href="/dashboard" className="text-primary hover:underline">
              ← Dashboard
            </Link>
            <h1 className="text-3xl font-bold text-foreground">Categories</h1>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-12">
        {/* Add Category Form */}
        <div className="bg-card border border-border rounded-lg p-6 mb-12">
          <h2 className="text-2xl font-semibold mb-6 text-foreground">Create New Category</h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Category Name</label>
                <input
                  type="text"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  required
                  className="w-full px-3 py-2 border border-border rounded-lg bg-background text-foreground"
                  placeholder="e.g., Groceries"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Type</label>
                <select
                  value={type}
                  onChange={(e) => setType(e.target.value)}
                  className="w-full px-3 py-2 border border-border rounded-lg bg-background text-foreground"
                >
                  <option value="expense">Expense</option>
                  <option value="income">Income</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Color</label>
                <div className="flex items-center gap-2">
                  <input
                    type="color"
                    value={color}
                    onChange={(e) => setColor(e.target.value)}
                    className="w-12 h-10 border border-border rounded-lg cursor-pointer"
                  />
                  <input
                    type="text"
                    value={color}
                    onChange={(e) => setColor(e.target.value)}
                    className="flex-1 px-3 py-2 border border-border rounded-lg bg-background text-foreground text-sm"
                  />
                </div>
              </div>
            </div>
            <button
              type="submit"
              disabled={submitting}
              className="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:opacity-90 transition disabled:opacity-50"
            >
              {submitting ? 'Creating...' : 'Create Category'}
            </button>
          </form>
        </div>

        {/* Expense Categories */}
        <div className="mb-12">
          <h2 className="text-2xl font-semibold mb-6 text-foreground">Expense Categories</h2>
          {expenseCategories.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {expenseCategories.map((category: any) => (
                <div
                  key={category.id}
                  className="bg-card border border-border rounded-lg p-4 flex items-center justify-between"
                >
                  <div className="flex items-center gap-3">
                    <div
                      className="w-8 h-8 rounded-full"
                      style={{ backgroundColor: category.color || '#3b82f6' }}
                    ></div>
                    <span className="font-medium text-foreground">{category.name}</span>
                  </div>
                  <button
                    onClick={() => handleDelete(category.id)}
                    className="text-red-600 hover:text-red-800 text-sm font-medium"
                  >
                    Delete
                  </button>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-muted-foreground">No expense categories yet.</p>
          )}
        </div>

        {/* Income Categories */}
        <div>
          <h2 className="text-2xl font-semibold mb-6 text-foreground">Income Categories</h2>
          {incomeCategories.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {incomeCategories.map((category: any) => (
                <div
                  key={category.id}
                  className="bg-card border border-border rounded-lg p-4 flex items-center justify-between"
                >
                  <div className="flex items-center gap-3">
                    <div
                      className="w-8 h-8 rounded-full"
                      style={{ backgroundColor: category.color || '#10b981' }}
                    ></div>
                    <span className="font-medium text-foreground">{category.name}</span>
                  </div>
                  <button
                    onClick={() => handleDelete(category.id)}
                    className="text-red-600 hover:text-red-800 text-sm font-medium"
                  >
                    Delete
                  </button>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-muted-foreground">No income categories yet.</p>
          )}
        </div>
      </main>
    </div>
  );
}
