# PowerShell script untuk setup sistem notifikasi WhatsApp RentRoom
# Jalankan script ini dari root directory project dengan PowerShell

Write-Host "🚀 Setting up WhatsApp Notification System for RentRoom" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green

# 1. Check if we're in the right directory
if (-not (Test-Path "artisan")) {
    Write-Host "❌ Error: artisan file not found. Please run this script from the project root directory." -ForegroundColor Red
    exit 1
}

Write-Host "✅ Project directory confirmed" -ForegroundColor Green

# 2. Check PHP version
try {
    $phpVersion = php -v 2>$null | Select-String "PHP" | Select-Object -First 1
    $versionMatch = $phpVersion -match "PHP (\d+\.\d+)"
    if ($versionMatch) {
        $version = $matches[1]
        Write-Host "📋 PHP Version: $version" -ForegroundColor Yellow
        
        if ([version]$version -lt [version]"8.0") {
            Write-Host "❌ Error: PHP 8.0 or higher is required" -ForegroundColor Red
            exit 1
        }
    }
} catch {
    Write-Host "❌ Error: PHP not found or not accessible" -ForegroundColor Red
    exit 1
}

# 3. Install dependencies
Write-Host "📦 Installing dependencies..." -ForegroundColor Yellow
composer install --no-dev --optimize-autoloader

# 4. Clear caches
Write-Host "🧹 Clearing caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Check environment file
if (-not (Test-Path ".env")) {
    Write-Host "❌ Error: .env file not found. Please create it from .env.example" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Environment file found" -ForegroundColor Green

# 6. Check database connection
Write-Host "🔍 Testing database connection..." -ForegroundColor Yellow
php artisan tinker --execute="echo 'Database connection: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED') . PHP_EOL;"

# 7. Run migrations
Write-Host "🗄️ Running database migrations..." -ForegroundColor Yellow
php artisan migrate --force

# 8. Check GOWA connection
Write-Host "📱 Testing GOWA WhatsApp connection..." -ForegroundColor Yellow
php artisan whatsapp:test

# 9. Check security users
Write-Host "👮 Checking security users..." -ForegroundColor Yellow
php artisan tinker --execute="
    `$securityUsers = App\Models\User::where('role_id', 2)->get();
    echo 'Found ' . `$securityUsers->count() . ' security users' . PHP_EOL;
    foreach (`$securityUsers as `$user) {
        echo '- ' . `$user->name . ' (' . (`$user->phone ?? 'NO PHONE') . ')' . PHP_EOL;
    }
"

# 10. Setup Windows Task Scheduler
Write-Host "⏰ Setting up Windows Task Scheduler..." -ForegroundColor Yellow

$taskName = "RentRoomScheduler"
$taskCommand = "php"
$taskArgs = "artisan schedule:run"
$workingDir = (Get-Location).Path

# Check if task already exists
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

if ($existingTask) {
    Write-Host "⚠️ Task '$taskName' already exists" -ForegroundColor Yellow
} else {
    try {
        # Create the task
        $action = New-ScheduledTaskAction -Execute $taskCommand -Argument $taskArgs -WorkingDirectory $workingDir
        $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration (New-TimeSpan -Days 365)
        $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
        
        Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -User "SYSTEM" -RunLevel Highest
        
        Write-Host "✅ Windows Task Scheduler task created: $taskName" -ForegroundColor Green
        Write-Host "   Task will run every minute" -ForegroundColor Green
    } catch {
        Write-Host "❌ Failed to create Windows Task Scheduler task: $_" -ForegroundColor Red
        Write-Host "   You may need to run PowerShell as Administrator" -ForegroundColor Yellow
    }
}

# 11. Create queue worker script
Write-Host "📋 Creating queue worker script..." -ForegroundColor Yellow

$queueScript = @"
@echo off
REM Start Laravel queue worker for notifications

echo Starting Laravel queue worker...
cd /d "$workingDir"

REM Kill existing queue workers
taskkill /f /im php.exe /fi "WINDOWTITLE eq queue:work" 2>nul

REM Start new queue worker
php artisan queue:work --daemon --tries=3 --timeout=60
"@

$queueScript | Out-File -FilePath "start_queue_worker.bat" -Encoding ASCII
Write-Host "✅ Queue worker script created: start_queue_worker.bat" -ForegroundColor Green

# 12. Create PowerShell queue worker script
$psQueueScript = @"
# Start Laravel queue worker for notifications

Write-Host "Starting Laravel queue worker..." -ForegroundColor Green
Set-Location "$workingDir"

# Kill existing queue workers
Get-Process php -ErrorAction SilentlyContinue | Where-Object { `$_.ProcessName -eq "php" } | Stop-Process -Force -ErrorAction SilentlyContinue

# Start new queue worker
php artisan queue:work --daemon --tries=3 --timeout=60
"@

$psQueueScript | Out-File -FilePath "start_queue_worker.ps1" -Encoding UTF8
Write-Host "✅ PowerShell queue worker script created: start_queue_worker.ps1" -ForegroundColor Green

# 13. Test commands
Write-Host "🧪 Testing notification commands..." -ForegroundColor Yellow
Write-Host "Testing security debug command..." -ForegroundColor Yellow
php artisan rent:debug-security

Write-Host "Testing end-time notification command..." -ForegroundColor Yellow
php artisan rent:notify-security-end-time

# 14. Final instructions
Write-Host ""
Write-Host "🎉 Setup completed successfully!" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host ""
Write-Host "📋 NEXT STEPS:" -ForegroundColor Yellow
Write-Host "1. Start queue worker: .\start_queue_worker.bat" -ForegroundColor White
Write-Host "2. Or PowerShell: .\start_queue_worker.ps1" -ForegroundColor White
Write-Host "3. Check Task Scheduler: taskschd.msc" -ForegroundColor White
Write-Host "4. Monitor logs: Get-Content storage\logs\laravel.log -Wait" -ForegroundColor White
Write-Host "5. Test notifications: php artisan rent:debug-security --test-send" -ForegroundColor White
Write-Host ""
Write-Host "🔍 TROUBLESHOOTING:" -ForegroundColor Yellow
Write-Host "- If notifications not working, run: php artisan rent:debug-security" -ForegroundColor White
Write-Host "- Check GOWA server is running on http://localhost:3000" -ForegroundColor White
Write-Host "- Verify security users have phone numbers" -ForegroundColor White
Write-Host "- Check queue worker is running: Get-Process php" -ForegroundColor White
Write-Host ""
Write-Host "📱 WhatsApp Integration:" -ForegroundColor Yellow
Write-Host "- GOWA server should be running on port 3000" -ForegroundColor White
Write-Host "- Basic auth: admin:admin" -ForegroundColor White
Write-Host "- Check connection: Invoke-WebRequest -Uri http://localhost:3000 -Credential (New-Object System.Management.Automation.PSCredential('admin', (ConvertTo-SecureString 'admin' -AsPlainText -Force)))" -ForegroundColor White
Write-Host ""
Write-Host "⚠️ IMPORTANT NOTES:" -ForegroundColor Red
Write-Host "- Windows Task Scheduler may require Administrator privileges" -ForegroundColor White
Write-Host "- If Task Scheduler fails, use manual cron alternative or run manually" -ForegroundColor White
Write-Host "- Queue worker must be running for notifications to work" -ForegroundColor White
Write-Host ""
Write-Host "✅ System is ready for testing!" -ForegroundColor Green

# 15. Alternative manual setup instructions
Write-Host ""
Write-Host "🔄 ALTERNATIVE MANUAL SETUP:" -ForegroundColor Cyan
Write-Host "If Task Scheduler doesn't work, you can:" -ForegroundColor White
Write-Host "1. Run manually every minute: php artisan schedule:run" -ForegroundColor White
Write-Host "2. Use Windows Service (requires additional setup)" -ForegroundColor White
Write-Host "3. Use third-party cron alternatives" -ForegroundColor White





