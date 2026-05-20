import { createClient } from '@/lib/supabase/server'
import { NextRequest, NextResponse } from 'next/server'

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

  const { message } = await request.json()

  if (!message) {
    return NextResponse.json(
      { error: 'Missing message' },
      { status: 400 }
    )
  }

  try {
    // Fetch user's recent transactions for context
    const { data: transactions } = await supabase
      .from('transactions')
      .select('*')
      .eq('user_id', user.id)
      .order('date', { ascending: false })
      .limit(10)

    const { data: profile } = await supabase
      .from('profiles')
      .select('*')
      .eq('id', user.id)
      .single()

    const groqApiKey = process.env.GROQ_API_KEY

    if (!groqApiKey) {
      return NextResponse.json(
        { error: 'AI service not configured' },
        { status: 500 }
      )
    }

    // Call Groq API with context about user's spending
    const systemPrompt = `You are a personal finance AI assistant. The user is ${profile?.full_name || 'a user'} and has made the following recent transactions: ${JSON.stringify(transactions || [])}. Help them with spending insights, budgeting advice, and financial recommendations based on their transaction history.`

    const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${groqApiKey}`,
      },
      body: JSON.stringify({
        model: 'mixtral-8x7b-32768',
        messages: [
          {
            role: 'system',
            content: systemPrompt,
          },
          {
            role: 'user',
            content: message,
          },
        ],
        temperature: 0.7,
        max_tokens: 1024,
      }),
    })

    if (!response.ok) {
      const errorData = await response.json()
      console.error('[v0] Groq API error:', errorData)
      return NextResponse.json(
        { error: 'Failed to get AI response' },
        { status: response.status }
      )
    }

    const data = await response.json()

    const aiResponse =
      data.choices?.[0]?.message?.content ||
      'I could not generate a response. Please try again.'

    return NextResponse.json({ reply: aiResponse })
  } catch (error) {
    console.error('[v0] Error in AI chat:', error)
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    )
  }
}
