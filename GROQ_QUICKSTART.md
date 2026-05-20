# Groq AI - Quick Start Guide

## Step 1: Get Your Groq API Key

1. Go to https://console.groq.com/keys
2. Sign up or log in to your Groq account
3. Click "Create API Key"
4. Copy your API key (starts with `gsk_`)

## Step 2: Add to Your Environment

### For Local Development (Next.js)

Create or edit `.env.local`:
```bash
GROQ_API_KEY=gsk_your_api_key_here
```

### For Local Development (Laravel)

Create or edit `.env`:
```bash
GROQ_API_KEY=gsk_your_api_key_here
```

### For Production (Vercel)

1. Go to your Vercel project settings
2. Navigate to Environment Variables
3. Add new variable:
   - Name: `GROQ_API_KEY`
   - Value: `gsk_your_api_key_here`

### For Production (Laravel - Server)

Add to your server's environment:
```bash
export GROQ_API_KEY=gsk_your_api_key_here
```

## Step 3: Verify Setup

### Test Next.js Route

```bash
curl -X POST http://localhost:3000/api/ai/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello, what is 2+2?"}'
```

Expected response:
```json
{
  "reply": "2 + 2 = 4"
}
```

### Test in Browser

1. Go to http://localhost:3000/ai-chat
2. Type a message (e.g., "Tell me about my finances")
3. You should see Groq's response appear

## Step 4: Use the Chat

### Frontend Chat Page
- Navigate to `/ai-chat`
- Ask financial questions
- Get AI-powered responses instantly

### Available Questions

- "What's my spending pattern?"
- "How can I save money?"
- "Give me budget recommendations"
- "Analyze my spending"
- "What are my financial insights?"

## Common Issues

### "AI service not configured" Error
**Solution:** Check that `GROQ_API_KEY` is properly set in your environment variables.

```bash
# Verify in Node.js
node -e "console.log(process.env.GROQ_API_KEY)"
```

### "Unauthorized" Error  
**Solution:** Your API key may be invalid or revoked. Get a new one from https://console.groq.com/keys

### "Rate Limited" (429 Error)
**Solution:** Groq has rate limits. The app will automatically retry after a short delay. For high volume, consider:
- Implementing caching
- Using a queue system
- Upgrading your Groq plan

### Chat Not Responding
**Solution:** 
1. Check browser console for errors
2. Check server logs (look for "[v0]" messages)
3. Verify API key is correctly set
4. Try a simpler message first

## Groq API Details

| Property | Value |
|----------|-------|
| Endpoint | https://api.groq.com/openai/v1/chat/completions |
| Model | mixtral-8x7b-32768 |
| API Key Format | gsk_xxxxxxxxxxxxx |
| Rate Limit | Varies by plan |
| Free Tier | Yes, very generous |

## Using Different Models

To use a different Groq model, edit these files:

**Next.js route (`app/api/ai/chat/route.ts`):**
```typescript
body: JSON.stringify({
  model: 'llama-2-70b-4096',  // Change this
  messages: [...]
})
```

**Laravel service (`app/Services/GroqService.php`):**
```php
'model' => 'llama-2-70b-4096',  // Change this
```

### Available Models
- `mixtral-8x7b-32768` - Default, fast and accurate
- `llama-2-70b-4096` - Larger, better for complex tasks
- `llama-2-13b-chat` - Lighter, faster responses

## Support & Resources

- **Groq Console:** https://console.groq.com
- **API Documentation:** https://console.groq.com/docs
- **Get API Key:** https://console.groq.com/keys
- **Check Usage:** https://console.groq.com/usage
- **Groq Status:** https://status.groq.com

## Next Steps

1. ✅ Get API key from Groq
2. ✅ Add GROQ_API_KEY to environment
3. ✅ Test with curl or browser
4. ✅ Start using the chat feature!

---

**That's it!** Your Groq AI setup is complete. The chat should now work with fast, reliable AI responses.
