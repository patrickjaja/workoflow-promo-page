#!/bin/bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

BACKUP_DIR="${SCRIPT_DIR}/backups/$(date +%Y%m%d_%H%M%S)"
ENV_FILE="${SCRIPT_DIR}/.env"

function rollback() {
    echo "❌ Deployment failed! Rolling back..."
    git reset --hard HEAD
    docker-compose up -d
    exit 1
}

trap rollback ERR

echo "🚀 Starting safe deployment with backup..."

echo "📁 Creating backup directory..."
mkdir -p "$BACKUP_DIR"

echo "💾 Backing up database..."
docker exec workoflow-promo-mariadb-1 mysqldump -u root -prootpassword workoflow_db > "$BACKUP_DIR/database_backup.sql"

echo "📥 Pulling latest changes from git..."
git pull origin main

echo "🐳 Building Docker images..."
docker-compose build --no-cache

echo "🐳 Pulling external Docker images..."
docker-compose pull

echo "📦 Installing/updating Composer dependencies..."
docker exec workoflow-promo-frankenphp-1 composer install --no-dev --optimize-autoloader

echo "🔍 Checking for pending migrations..."
PENDING_MIGRATIONS=$(docker exec workoflow-promo-frankenphp-1 bin/console doctrine:migrations:status --no-interaction | grep "New Migrations:" | awk '{print $3}')

if [ "$PENDING_MIGRATIONS" != "0" ] && [ ! -z "$PENDING_MIGRATIONS" ]; then
    echo "📊 Found $PENDING_MIGRATIONS pending migrations"
    echo "🗄️  Running database migrations..."
    docker exec workoflow-promo-frankenphp-1 bin/console doctrine:migrations:migrate --no-interaction
else
    echo "✅ No pending migrations"
fi

echo "🧹 Clearing all caches..."
docker exec workoflow-promo-frankenphp-1 bin/console cache:clear --env=prod --no-debug
docker exec workoflow-promo-frankenphp-1 bin/console cache:pool:clear cache.global_clearer

echo "🔥 Warming up cache..."
docker exec workoflow-promo-frankenphp-1 bin/console cache:warmup --env=prod --no-debug

echo "🔄 Gracefully restarting containers..."
docker-compose restart

echo "⏳ Waiting for services to be ready..."
attempts=0
max_attempts=30
while [ $attempts -lt $max_attempts ]; do
    if docker exec workoflow-promo-frankenphp-1 bin/console about --no-interaction > /dev/null 2>&1; then
        echo "✅ Application is ready!"
        break
    fi
    attempts=$((attempts + 1))
    echo "⏳ Waiting for application to be ready... ($attempts/$max_attempts)"
    sleep 2
done

if [ $attempts -eq $max_attempts ]; then
    echo "❌ Application failed to start within timeout!"
    rollback
fi

echo "🏥 Running health checks..."
docker-compose ps
docker exec workoflow-promo-frankenphp-1 bin/console about --no-interaction

echo "🧹 Cleaning up old backups (keeping last 5)..."
cd "$SCRIPT_DIR/backups" && ls -t | tail -n +6 | xargs -r rm -rf

echo "✅ Deployment completed successfully!"
echo "💾 Backup saved to: $BACKUP_DIR"

exit 0