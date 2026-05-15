import { createServerClient } from '@/lib/supabase/server'
import { NextRequest, NextResponse } from 'next/server'

export async function GET(request: NextRequest) {
  const supabase = await createServerClient()

  const {
    data: { user },
  } = await supabase.auth.getUser()

  if (!user) {
    return NextResponse.json(
      { error: 'Unauthorized' },
      { status: 401 }
    )
  }

  const searchParams = request.nextUrl.searchParams
  const completed = searchParams.get('completed')

  let query = supabase
    .from('todos')
    .select('*')
    .eq('user_id', user.id)

  if (completed !== null) {
    query = query.eq('completed', completed === 'true')
  }

  const { data: todos, error } = await query.order('due_date', { ascending: true })

  if (error) {
    return NextResponse.json(
      { error: error.message },
      { status: 500 }
    )
  }

  return NextResponse.json(todos)
}

export async function POST(request: NextRequest) {
  const supabase = await createServerClient()

  const {
    data: { user },
  } = await supabase.auth.getUser()

  if (!user) {
    return NextResponse.json(
      { error: 'Unauthorized' },
      { status: 401 }
    )
  }

  const { title, description, due_date, priority } = await request.json()

  if (!title) {
    return NextResponse.json(
      { error: 'Missing required fields' },
      { status: 400 }
    )
  }

  const { data: todo, error } = await supabase
    .from('todos')
    .insert({
      user_id: user.id,
      title,
      description,
      due_date,
      priority: priority || 'medium',
    })
    .select()
    .single()

  if (error) {
    return NextResponse.json(
      { error: error.message },
      { status: 500 }
    )
  }

  return NextResponse.json(todo, { status: 201 })
}
