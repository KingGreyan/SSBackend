import { createServerClient } from '@/lib/supabase/server'
import { NextRequest, NextResponse } from 'next/server'

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

    const geminiApiKey = process.env.GEMINI_API_KEY

    if (!geminiApiKey) {
      return NextResponse.json(
        { error: 'AI service not configured' },
        { status: 500 }
      )
    }

    // Call Gemini API with context about user's spending
    const systemPrompt = `You are a personal finance AI assistant. The user is ${profile?.full_name || 'a user'} and has made the following recent transactions: ${JSON.stringify(transactions || [])}. Help them with spending insights, budgeting advice, and financial recommendations based on their transaction history.`

    const response = await fetch('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-goog-api-key': geminiApiKey,
      },
      body: JSON.stringify({
        system_instruction: {
          parts: [{ text: systemPrompt }],
        },
        contents: [
          {
            role: 'user',
            parts: [{ text: message }],
          },
        ],
      }),
    })

    if (!response.ok) {
      const errorData = await response.json()
      console.error('[v0] Gemini API error:', errorData)
      return NextResponse.json(
        { error: 'Failed to get AI response' },
        { status: response.status }
      )
    }

    const data = await response.json()

    const aiResponse =
      data.candidates?.[0]?.content?.parts?.[0]?.text ||
      'I could not generate a response. Please try again.'

    return NextResponse.json({ response: aiResponse })
  } catch (error) {
    console.error('[v0] Error in AI chat:', error)
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    )
  }
}
