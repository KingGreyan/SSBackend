import Link from 'next/link'

export default function Page() {
  return (
    <div className="flex min-h-screen w-full items-center justify-center p-6 md:p-10 bg-background">
      <div className="w-full max-w-sm">
        <div className="bg-card border border-border rounded-lg shadow-lg p-8 text-center">
          <div className="mb-6">
            <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h2 className="text-2xl font-bold text-foreground mb-2">Account Created!</h2>
            <p className="text-sm text-muted-foreground mb-6">
              Your account has been successfully created. Please check your email to verify your account.
            </p>
          </div>

          <div className="space-y-3">
            <p className="text-xs text-muted-foreground">
              Didn&apos;t receive the email? Check your spam folder or try logging in again.
            </p>
            <Link
              href="/auth/login"
              className="inline-block bg-primary text-primary-foreground px-6 py-2 rounded-lg hover:opacity-90 transition font-medium"
            >
              Go to Login
            </Link>
          </div>
        </div>
      </div>
    </div>
  )
}
