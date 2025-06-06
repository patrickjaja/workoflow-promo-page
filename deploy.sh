#!/bin/bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "🚀 Starting deployment..."

echo "📥 Pulling latest changes from git..."
git pull origin main

echo "🐳 Building Docker images..."
docker-compose build

echo "🐳 Pulling latest Docker images..."
docker-compose pull

echo "📦 Installing/updating Composer dependencies..."
docker exec workoflow-promo-frankenphp-1 composer install --no-dev --optimize-autoloader

echo "🗄️  Running database migrations..."
docker exec workoflow-promo-frankenphp-1 bin/console doctrine:migrations:migrate --no-interaction

echo "🧹 Clearing Symfony cache..."
docker exec workoflow-promo-frankenphp-1 bin/console cache:clear --env=prod --no-debug

echo "🔥 Warming up cache..."
docker exec workoflow-promo-frankenphp-1 bin/console cache:warmup --env=prod --no-debug

echo "🔄 Restarting containers..."
docker-compose down
docker-compose up -d

echo "⏳ Waiting for services to be ready..."
sleep 10

echo "🏥 Health check..."
docker-compose ps

echo "✅ Deployment completed successfully!"
echo "📊 Current container status:"
docker-compose ps

exit 0