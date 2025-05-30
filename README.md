# Workoflow for Teams - SaaS Subscription Platform

A modern SaaS application built with FrankenPHP and Symfony, featuring Google OAuth authentication, Stripe payments, and a responsive design.

## Features

- 🚀 **FrankenPHP** - High-performance PHP server
- 🔐 **Google OAuth** - Secure authentication
- 💳 **Stripe Integration** - Pro subscription payments (€10/month)
- 📧 **Email Notifications** - Free subscription notifications
- 🎨 **Modern UI** - Responsive design with Tailwind CSS
- 🗄️ **MariaDB** - Reliable database storage
- 📊 **Subscription Management** - Free, Pro, and Enterprise plans

## Quick Start

### Prerequisites

- Docker and Docker Compose
- Google OAuth credentials
- Stripe account (for payments)

### Setup

1. **Clone and configure environment:**
   ```bash
   cd workoflow-promo
   cp .env .env.local
   ```

2. **Configure Google OAuth:**
   - Go to [Google Cloud Console](https://console.cloud.google.com)
   - Create a new project or select existing
   - Enable Google+ API
   - Create OAuth 2.0 credentials
   - Add redirect URI: `http://localhost/connect/google/check`
   - Update `.env.local`:
     ```
     GOOGLE_CLIENT_ID=your_google_client_id
     GOOGLE_CLIENT_SECRET=your_google_client_secret
     ```

3. **Configure Stripe:**
   - Get your keys from [Stripe Dashboard](https://dashboard.stripe.com)
   - Update `.env.local`:
     ```
     STRIPE_PUBLISHABLE_KEY=pk_test_your_key
     STRIPE_SECRET_KEY=sk_test_your_key
     ```

4. **Start the application:**
   ```bash
   docker-compose up -d
   ```

5. **Install dependencies and setup database:**
   ```bash
   docker-compose exec frankenphp composer install
   docker-compose exec frankenphp php bin/console doctrine:migrations:migrate --no-interaction
   ```

6. **Access the application:**
   - **Main App**: http://localhost
   - **PHPMyAdmin**: http://localhost:8080
   - **MailHog**: http://localhost:8025

## Architecture

### Services

- **FrankenPHP**: Web server with Caddy and PHP 8.3
- **MariaDB**: Database server
- **Redis**: Caching and sessions
- **MailHog**: Email testing
- **PHPMyAdmin**: Database management

### Directory Structure

```
workoflow-promo/
├── config/              # Symfony configuration
├── docker/              # Docker configurations
├── migrations/          # Database migrations
├── public/              # Web assets (CSS, JS, images)
├── src/
│   ├── Controller/      # Application controllers
│   ├── Entity/          # Database entities
│   ├── Repository/      # Data repositories
│   └── Security/        # Authentication logic
├── templates/           # Twig templates
└── docker-compose.yml   # Docker services
```

## Subscription Plans

### Free Plan (€0/month)
- Up to 3 team members
- Basic project management
- 5GB storage
- Email support
- Sends notification email to admin

### Pro Plan (€10/month)
- Unlimited team members
- Advanced project management
- 100GB storage
- Priority support
- Advanced analytics
- API access
- Stripe payment integration

### Enterprise Plan (Custom)
- Everything in Pro
- Unlimited storage
- 24/7 phone support
- Custom integrations
- SSO & advanced security
- Dedicated account manager
- Contact sales for pricing

## Development

### Running Commands

```bash
# Install Composer dependencies
docker-compose exec frankenphp composer install

# Run database migrations
docker-compose exec frankenphp php bin/console doctrine:migrations:migrate

# Create new migration
docker-compose exec frankenphp php bin/console make:migration

# Clear cache
docker-compose exec frankenphp php bin/console cache:clear

# Check routes
docker-compose exec frankenphp php bin/console debug:router
```

### Testing Payments

1. Use Stripe test card numbers:
   - Success: `4242 4242 4242 4242`
   - Decline: `4000 0000 0000 0002`

2. Use any future expiry date and any 3-digit CVC

### Email Testing

- All emails are captured by MailHog
- Access MailHog at http://localhost:8025
- Free subscription notifications sent to admin email

## Security Features

- Google OAuth 2.0 authentication
- CSRF protection
- SQL injection prevention via Doctrine ORM
- XSS protection in Twig templates
- Secure session handling
- SSL/TLS encryption in production

## Production Deployment

1. **Update environment variables:**
   ```bash
   APP_ENV=prod
   APP_DEBUG=false
   DATABASE_URL=mysql://user:pass@db:3306/workoflow_prod
   ```

2. **Configure production domain in Caddyfile**

3. **Use production Stripe keys**

4. **Set up proper SMTP for emails**

5. **Configure Google OAuth with production domain**

## Monitoring

The application includes built-in analytics and performance monitoring:

- Page view tracking
- User interaction events
- Performance metrics
- Error tracking
- Subscription conversion tracking

## Support

For support or questions:
- Check the [issues](https://github.com/your-repo/issues)
- Email: support@workoflow.com

## License

This project is licensed under the MIT License.