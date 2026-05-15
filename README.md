# Smart Spending - AI-Powered Personal Finance Manager

A modern web application for managing personal finances with AI-powered insights and recommendations.

## Features

- **Transaction Management**: Add, track, and organize your income and expenses
- **Smart Categories**: Create and manage spending categories with color coding
- **Budget Tracking**: Set and monitor budgets to control your spending
- **AI Chat Assistant**: Get personalized spending insights and recommendations powered by Google Gemini
- **Todo Management**: Track financial goals and tasks with priority levels
- **Dashboard Analytics**: View spending trends, expense breakdowns, and financial overview
- **User Authentication**: Secure login with email and password via Supabase Auth
- **Responsive Design**: Beautiful UI that works seamlessly on desktop and mobile devices

## Tech Stack

- **Frontend**: React 18 + Next.js 15 with TypeScript
- **Styling**: Tailwind CSS with custom design tokens
- **Database**: Supabase (PostgreSQL) with Row Level Security
- **Authentication**: Supabase Auth
- **AI Integration**: Google Gemini API
- **Deployment**: Vercel
- **Package Manager**: pnpm

## Getting Started

### Prerequisites

- Node.js 18+ and pnpm
- Supabase account and project
- Google Gemini API key

### Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd smart-spending
```

2. Install dependencies:
```bash
pnpm install
```

3. Set up environment variables in your Vercel dashboard:
```
NEXT_PUBLIC_SUPABASE_URL=your_supabase_url
NEXT_PUBLIC_SUPABASE_ANON_KEY=your_supabase_anon_key
GEMINI_API_KEY=your_gemini_api_key
```

4. Start the development server:
```bash
pnpm dev
```

Open [http://localhost:3000](http://localhost:3000) to view the app.

## Project Structure

```
smart-spending/
├── app/
│   ├── api/                 # API routes for backend operations
│   │   ├── ai/             # AI chat endpoint
│   │   ├── categories/      # Category CRUD
│   │   ├── profile/        # User profile management
│   │   ├── todos/          # Todo CRUD
│   │   └── transactions/   # Transaction CRUD
│   ├── auth/               # Authentication pages
│   │   ├── callback/       # OAuth callback
│   │   ├── login/          # Login page
│   │   ├── sign-up/        # Sign up page
│   │   └── error/          # Auth error page
│   ├── dashboard/          # Main dashboard
│   ├── transactions/       # Transaction management
│   ├── categories/         # Category management
│   ├── todos/              # Todo management
│   ├── ai-chat/            # AI chat interface
│   ├── profile/            # User profile
│   ├── layout.tsx          # Root layout
│   ├── page.tsx            # Home page
│   └── globals.css         # Global styles with design tokens
├── lib/
│   ├── hooks/              # Custom React hooks
│   │   └── useData.ts      # Data fetching hooks with SWR
│   └── supabase/           # Supabase configuration
│       ├── client.ts       # Browser client
│       ├── server.ts       # Server client
│       └── proxy.ts        # Proxy configuration
├── middleware.ts           # Next.js middleware
├── tailwind.config.ts      # Tailwind CSS configuration
└── tsconfig.json           # TypeScript configuration
```

## Database Schema

### Tables

- **profiles**: User profile information
- **categories**: Transaction categories (Income/Expense)
- **transactions**: Financial transactions
- **budgets**: Budget limits and tracking
- **todos**: Task management for financial goals

All tables include Row Level Security (RLS) policies to ensure users can only access their own data.

## API Routes

### Authentication
- `POST /api/auth/callback` - OAuth callback handler

### Profile
- `GET /api/profile` - Get user profile
- `PATCH /api/profile` - Update user profile

### Categories
- `GET /api/categories` - List user categories
- `POST /api/categories` - Create category
- `PATCH /api/categories/[id]` - Update category
- `DELETE /api/categories/[id]` - Delete category

### Transactions
- `GET /api/transactions` - List transactions
- `POST /api/transactions` - Create transaction
- `PATCH /api/transactions/[id]` - Update transaction
- `DELETE /api/transactions/[id]` - Delete transaction

### Todos
- `GET /api/todos` - List todos
- `POST /api/todos` - Create todo
- `PATCH /api/todos/[id]` - Update todo
- `DELETE /api/todos/[id]` - Delete todo

### AI
- `POST /api/ai/chat` - Chat with AI assistant

## Features in Detail

### Dashboard
View your financial overview with:
- Total income and expenses
- Current balance
- Expenses by category
- Recent transactions
- Quick navigation to all features

### Transaction Management
- Add income and expense transactions
- Categorize transactions
- Filter by date range
- Edit and delete transactions
- View transaction history

### AI Chat Assistant
- Get spending insights and patterns
- Receive personalized recommendations
- Ask questions about your finances
- Get tips for saving money

### Todo Manager
- Create financial goals and tasks
- Set priority levels (Low, Medium, High)
- Add due dates
- Filter by completion status
- Track progress on financial objectives

## Security Features

- **Row Level Security**: All database tables protected with RLS policies
- **Secure Authentication**: Password-based authentication with email verification
- **Environment Variables**: Sensitive keys stored securely
- **HTTPS Only**: All communications encrypted in transit
- **Input Validation**: Server-side validation on all API endpoints

## Deployment

The app is ready to deploy on Vercel:

1. Push your code to GitHub
2. Connect your GitHub repository to Vercel
3. Set environment variables in Vercel dashboard
4. Deploy with a single click

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open source and available under the MIT License.

## Support

For support, please open an issue on GitHub or contact the development team.

## Future Enhancements

- Recurring transactions
- Advanced analytics and charts
- Budget alerts and notifications
- Data export (CSV, PDF)
- Multi-currency support
- Investment tracking
- Bill reminders
- Savings goals
