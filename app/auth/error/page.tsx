import Link from 'next/link'

export default async function Page({
  searchParams,
}: {
  searchParams: Promise<{ error: string }>
}) {
  const params = await searchParams

  return (
    <div className="flex min-h-screen w-full items-center justify-center p-6 md:p-10 bg-background">
      <div className="w-full max-w-sm">
        <div className="bg-card border border-border rounded-lg shadow-lg p-8">
          <h2 className="text-2xl font-bold text-foreground mb-2">
            Sorry, something went wrong.
          </h2>
          <div className="mt-4">
            {params?.error ? (
              <p className="text-sm text-muted-foreground mb-6">
                Error: {params.error}
              </p>
            ) : (
              <p className="text-sm text-muted-foreground mb-6">
                An unspecified error occurred during authentication.
              </p>
            )}
            <Link
              href="/auth/login"
              className="inline-block bg-primary text-primary-foreground px-4 py-2 rounded-lg hover:opacity-90 transition font-medium"
            >
              Back to Login
            </Link>
          </div>
        </div>
      </div>
    </div>
  )
}
