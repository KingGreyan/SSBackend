'use client';

import { useState, useEffect } from 'react';
import { useTodos } from '@/lib/hooks/useData';
import { createClient } from '@/lib/supabase/client';
import Link from 'next/link';
import { redirect } from 'next/navigation';

export default function TodosPage() {
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [title, setTitle] = useState('');
  const [priority, setPriority] = useState('medium');
  const [dueDate, setDueDate] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [filter, setFilter] = useState('all');
  const { todos, mutateTodos } = useTodos(filter === 'completed' ? { completed: true } : filter === 'pending' ? { completed: false } : undefined);

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
      const response = await fetch('/api/todos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title,
          priority,
          due_date: dueDate ? new Date(dueDate).toISOString() : null,
        }),
      });

      if (response.ok) {
        setTitle('');
        setPriority('medium');
        setDueDate('');
        mutateTodos();
      }
    } catch (error) {
      console.error('Error creating todo:', error);
    } finally {
      setSubmitting(false);
    }
  };

  const handleToggle = async (id: string, completed: boolean) => {
    try {
      await fetch(`/api/todos/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ completed: !completed }),
      });
      mutateTodos();
    } catch (error) {
      console.error('Error updating todo:', error);
    }
  };

  const handleDelete = async (id: string) => {
    try {
      await fetch(`/api/todos/${id}`, { method: 'DELETE' });
      mutateTodos();
    } catch (error) {
      console.error('Error deleting todo:', error);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-lg text-foreground">Loading...</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      <header className="border-b border-border">
        <div className="max-w-7xl mx-auto px-4 py-6 flex justify-between items-center">
          <div className="flex items-center gap-4">
            <Link href="/dashboard" className="text-primary hover:underline">
              ← Dashboard
            </Link>
            <h1 className="text-3xl font-bold text-foreground">Todo Manager</h1>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-12">
        {/* Add Todo Form */}
        <div className="bg-card border border-border rounded-lg p-6 mb-12">
          <h2 className="text-2xl font-semibold mb-6 text-foreground">Create New Task</h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Task Title</label>
                <input
                  type="text"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  required
                  className="w-full px-3 py-2 border border-border rounded-lg bg-background text-foreground"
                  placeholder="e.g., Pay utility bill"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Priority</label>
                <select
                  value={priority}
                  onChange={(e) => setPriority(e.target.value)}
                  className="w-full px-3 py-2 border border-border rounded-lg bg-background text-foreground"
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-foreground mb-2">Due Date</label>
                <input
                  type="date"
                  value={dueDate}
                  onChange={(e) => setDueDate(e.target.value)}
                  className="w-full px-3 py-2 border border-border rounded-lg bg-background text-foreground"
                />
              </div>
            </div>
            <button
              type="submit"
              disabled={submitting}
              className="bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:opacity-90 transition disabled:opacity-50"
            >
              {submitting ? 'Creating...' : 'Add Task'}
            </button>
          </form>
        </div>

        {/* Filter Buttons */}
        <div className="flex gap-2 mb-8">
          {['all', 'pending', 'completed'].map((f) => (
            <button
              key={f}
              onClick={() => setFilter(f)}
              className={`px-4 py-2 rounded-lg font-medium transition ${
                filter === f
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-card border border-border text-foreground hover:bg-muted'
              }`}
            >
              {f.charAt(0).toUpperCase() + f.slice(1)}
            </button>
          ))}
        </div>

        {/* Todos List */}
        <div className="bg-card border border-border rounded-lg p-6">
          {todos.length > 0 ? (
            <ul className="space-y-3">
              {todos.map((todo: any) => (
                <li
                  key={todo.id}
                  className="flex items-center gap-4 p-4 border border-border rounded-lg hover:bg-muted transition"
                >
                  <input
                    type="checkbox"
                    checked={todo.completed}
                    onChange={() => handleToggle(todo.id, todo.completed)}
                    className="w-5 h-5 cursor-pointer"
                  />
                  <div className="flex-1">
                    <p
                      className={`font-medium ${
                        todo.completed
                          ? 'text-muted-foreground line-through'
                          : 'text-foreground'
                      }`}
                    >
                      {todo.title}
                    </p>
                    {todo.due_date && (
                      <p className="text-xs text-muted-foreground">
                        Due: {new Date(todo.due_date).toLocaleDateString()}
                      </p>
                    )}
                  </div>
                  <span
                    className={`px-2 py-1 rounded text-xs font-medium ${
                      todo.priority === 'high'
                        ? 'bg-red-100 text-red-800'
                        : todo.priority === 'medium'
                          ? 'bg-yellow-100 text-yellow-800'
                          : 'bg-green-100 text-green-800'
                    }`}
                  >
                    {todo.priority.charAt(0).toUpperCase() + todo.priority.slice(1)}
                  </span>
                  <button
                    onClick={() => handleDelete(todo.id)}
                    className="text-red-600 hover:text-red-800 text-sm font-medium"
                  >
                    Delete
                  </button>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-muted-foreground">No tasks yet. Create one above!</p>
          )}
        </div>
      </main>
    </div>
  );
}
