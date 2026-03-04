#!/bin/bash

# Setup script untuk sistem notifikasi WhatsApp RentRoom
# Pastikan script ini dijalankan dari root directory project

echo "🚀 Setting up WhatsApp Notification System for RentRoom"
echo "======================================================"

# 1. Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the project root directory."
    exit 1
fi

echo "✅ Project directory confirmed"

# 2. Check PHP version
PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
echo "📋 PHP Version: $PHP_VERSION"

if [[ $(echo "$PHP_VERSION >= 8.0" | bc -l) -eq 0 ]]; then
    echo "❌ Error: PHP 8.0 or higher is required"
    exit 1
fi

# 3. Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# 4. Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Check environment file
if [ ! -f ".env" ]; then
    echo "❌ Error: .env file not found. Please create it from .env.example"
    exit 1
fi

echo "✅ Environment file found"

# 6. Check database connection
echo "🔍 Testing database connection..."
php artisan tinker --execute="echo 'Database connection: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED') . PHP_EOL;"

# 7. Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 8. Check GOWA connection
echo "📱 Testing GOWA WhatsApp connection..."
php artisan whatsapp:test

# 9. Check security users
echo "👮 Checking security users..."
php artisan tinker --execute="
    \$securityUsers = App\Models\User::where('role_id', 2)->get();
    echo 'Found ' . \$securityUsers->count() . ' security users' . PHP_EOL;
    foreach (\$securityUsers as \$user) {
        echo '- ' . \$user->name . ' (' . (\$user->phone ?? 'NO PHONE') . ')' . PHP_EOL;
    }
"

# 10. Setup cron job
echo "⏰ Setting up cron job..."
CRON_JOB="* * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo "⚠️ Cron job already exists"
else
    # Add cron job
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo "✅ Cron job added: $CRON_JOB"
fi

# 11. Create queue worker script
echo "📋 Creating queue worker script..."
cat > start_queue_worker.sh << 'EOF'
#!/bin/bash
# Start Laravel queue worker for notifications

echo "Starting Laravel queue worker..."
cd "$(dirname "$0")"

# Kill existing queue workers
pkill -f "queue:work" || true

# Start new queue worker
php artisan queue:work --daemon --tries=3 --timeout=60
EOF

chmod +x start_queue_worker.sh

# 12. Create systemd service for production
if command -v systemctl &> /dev/null; then
    echo "🔧 Creating systemd service for production..."
    
    SERVICE_NAME="rentroom-queue"
    SERVICE_FILE="/etc/systemd/system/$SERVICE_NAME.service"
    
    cat > "$SERVICE_FILE" << EOF
[Unit]
Description=RentRoom Queue Worker
After=network.target

[Service]
Type=simple
User=$(whoami)
Group=$(id -gn)
WorkingDirectory=$(pwd)
ExecStart=$(which php) artisan queue:work --daemon --tries=3 --timeout=60
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

    echo "✅ Systemd service created: $SERVICE_FILE"
    echo "   To enable: sudo systemctl enable $SERVICE_NAME"
    echo "   To start: sudo systemctl start $SERVICE_NAME"
fi

# 13. Test commands
echo "🧪 Testing notification commands..."
echo "Testing security debug command..."
php artisan rent:debug-security

echo "Testing end-time notification command..."
php artisan rent:notify-security-end-time

# 14. Final instructions
echo ""
echo "🎉 Setup completed successfully!"
echo "================================"
echo ""
echo "📋 NEXT STEPS:"
echo "1. Start queue worker: ./start_queue_worker.sh"
echo "2. Or use systemd: sudo systemctl start rentroom-queue"
echo "3. Check cron job: crontab -l"
echo "4. Monitor logs: tail -f storage/logs/laravel.log"
echo "5. Test notifications: php artisan rent:debug-security --test-send"
echo ""
echo "🔍 TROUBLESHOOTING:"
echo "- If notifications not working, run: php artisan rent:debug-security"
echo "- Check GOWA server is running on http://localhost:3000"
echo "- Verify security users have phone numbers"
echo "- Check queue worker is running: ps aux | grep queue:work"
echo ""
echo "📱 WhatsApp Integration:"
echo "- GOWA server should be running on port 3000"
echo "- Basic auth: admin:admin"
echo "- Check connection: curl -u admin:admin http://localhost:3000"
echo ""
echo "✅ System is ready for testing!"





