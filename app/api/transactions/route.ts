import { createClient } from '@/lib/supabase/server'
import { NextRequest, NextResponse } from 'next/server'

export async function GET(request: NextRequest) {
  const supabase = await createClient()

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
  const startDate = searchParams.get('startDate')
  const endDate = searchParams.get('endDate')
  const type = searchParams.get('type')

  let query = supabase
    .from('transactions')
    .select('*')
    .eq('user_id', user.id)

  if (startDate) {
    query = query.gte('date', startDate)
  }

  if (endDate) {
    query = query.lte('date', endDate)
  }

  if (type) {
    query = query.eq('type', type)
  }

  const { data: transactions, error } = await query.order('date', { ascending: false })

  if (error) {
    console.error('[v0] Error fetching transactions:', error)
    return NextResponse.json(
      { error: error.message || 'Failed to fetch transactions' },
      { status: 500 }
    )
  }

  return NextResponse.json(transactions || [])
}

export async function POST(request: NextRequest) {
  const supabase = await createClient()

  const {
    data: { user },
  } = await supabase.auth.getUser()

  if (!user) {
    return NextResponse.json(
      { error: 'Unauthorized' },
      { status: 401 }
    )
  }

  const { title, description, amount, type, category_id, date } = await request.json()

  if (!title || !amount || !type || !date) {
    return NextResponse.json(
      { error: 'Missing required fields' },
      { status: 400 }
    )
  }

  const { data: transaction, error } = await supabase
    .from('transactions')
    .insert({
      user_id: user.id,
      title,
      description,
      amount,
      type,
      category_id,
      date,
    })
    .select()
    .single()

  if (error) {
    console.error('[v0] Error creating transaction:', error)
    return NextResponse.json(
      { error: error.message || 'Failed to create transaction' },
      { status: 500 }
    )
  }

  return NextResponse.json(transaction, { status: 201 })
}
