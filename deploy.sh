#!/bin/bash

# Production Deployment Script - Factory System
echo "🚀 Starting Factory System Deployment..."

# Go to project directory
cd factory-system || exit

# Put app into maintenance mode
echo "🔒 Entering maintenance mode..."
php artisan down --render="errors::503" --refresh=15

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Install PHP dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Install Node dependencies & compile
echo "🎨 Building frontend assets..."
npm ci
npm run build

# Cache configuration
echo "⚡ Caching config & routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run database migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Restart queues
echo "🔄 Restarting queue workers..."
php artisan queue:restart

# Bring app out of maintenance mode
echo "✅ Deployment complete. Bringing app online..."
php artisan up

echo "🎉 Factory System deployed successfully!"
