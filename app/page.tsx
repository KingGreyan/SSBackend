'use client';

import { useEffect } from 'react';
import { createClient } from '@/lib/supabase/client';
import { useRouter } from 'next/navigation';

export default function Home() {
  const router = useRouter();

  useEffect(() => {
    const checkAuth = async () => {
      const supabase = createClient();
      const {
        data: { user },
      } = await supabase.auth.getUser();

      if (user) {
        router.push('/dashboard');
      }
    };

    checkAuth();
  }, [router]);

  return (
    <main className="min-h-screen bg-background flex items-center justify-center">
      <div className="text-center space-y-8 px-4">
        <div>
          <h1 className="text-4xl font-bold text-foreground mb-2">
            Smart Spending
          </h1>
          <p className="text-xl text-muted-foreground">
            AI-powered personal finance management
          </p>
        </div>

        <div className="flex gap-4 justify-center flex-wrap">
          <a
            href="/auth/login"
            className="px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:opacity-90 transition"
          >
            Sign In
          </a>
          <a
            href="/auth/sign-up"
            className="px-6 py-3 bg-secondary text-secondary-foreground rounded-lg hover:opacity-90 transition"
          >
            Create Account
          </a>
        </div>

        <div className="mt-12 text-sm text-muted-foreground max-w-md mx-auto">
          <p className="mb-4">Features:</p>
          <ul className="text-left space-y-2">
            <li>✓ Track your spending with AI insights</li>
            <li>✓ Get smart recommendations</li>
            <li>✓ Manage budgets and goals</li>
            <li>✓ Chat with AI assistant</li>
          </ul>
        </div>
      </div>
    </main>
  );
}
