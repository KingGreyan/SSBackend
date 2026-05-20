# Groq AI Setup Guide

This guide explains how to set up Groq AI for your application after migrating from Gemini.

## Prerequisites

1. **Groq Account**: Create a free account at https://console.groq.com
2. **API Key**: Get your API key from https://console.groq.com/keys

## Environment Variables

### For Next.js (Frontend AI Chat)

Add the following to your `.env.local` file:

```bash
GROQ_API_KEY=your_groq_api_key_here
```

### For Laravel (Backend Controllers)

Add the following to your `.env` file:

```bash
GROQ_API_KEY=your_groq_api_key_here
```

## Migration Summary

The following components have been updated to use Groq instead of Gemini:

### Next.js Components
- **`app/api/ai/chat/route.ts`** - Updated to call Groq API instead of Gemini
  - Changed endpoint from `generativelanguage.googleapis.com` to `api.groq.com`
  - Updated request/response format to OpenAI-compatible format
  - Fixed response field from `response` to `reply` to match frontend expectations

### React Frontend
- **`app/ai-chat/page.tsx`** - No changes needed (already compatible with the updated API)

### Laravel Backend
- **`app/Services/GroqService.php`** - New service class replacing GeminiService
  - Implements all financial analysis methods
  - Uses Groq API for generation
  - Supports tool calling for database queries
  
- **`app/Http/Controllers/AIController.php`** - Updated to use GroqService
  - All AI methods now use Groq instead of Gemini
  - Changed database field from `gemini_api_key` to `groq_api_key`

## Available Models

Groq provides several fast models:

- **mixtral-8x7b-32768** (default) - Great for general tasks and financial insights
- **llama-2-70b-4096** - Larger model for complex analysis
- **llama-2-13b-chat** - Lighter model for quick responses

Currently configured to use: **mixtral-8x7b-32768**

## Testing the Connection

### Test with Next.js Route
```bash
curl -X POST http://localhost:3000/api/ai/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "What is 2+2?"}'
```

### Test with Laravel
Use the AIController's `testApiConnection()` endpoint.

## Important Notes

1. **API Key Security**: Never commit your API key to version control. Use environment variables.
2. **Free Tier Limits**: Groq offers generous free tier with rate limiting. Check their console for current limits.
3. **Database Migration**: If you have existing `gemini_api_key` fields in your database, you may need to:
   - Add a new `groq_api_key` column
   - Migrate user API keys if applicable
   - Update the migration if needed

## Troubleshooting

### "AI service not configured" Error
- Check that `GROQ_API_KEY` is set in your environment variables
- Verify the API key is valid at https://console.groq.com/keys

### Rate Limiting (429 Error)
- Groq may rate limit requests during high usage
- The service will automatically retry with exponential backoff
- Consider caching responses or implementing request queuing

### Authentication Errors
- Ensure your API key starts with `gsk_`
- Verify the key hasn't been revoked in the Groq console

## Support

- Groq Documentation: https://console.groq.com/docs
- API Reference: https://console.groq.com/docs/api
- Get API Key: https://console.groq.com/keys
