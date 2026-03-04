# 🚨 **Troubleshooting: Security Notifications Not Working**

## 🔍 **Quick Diagnostic Commands**

### **1. Run Debug Command**
```bash
# Comprehensive system check
php artisan rent:debug-security

# Test with specific rent ID
php artisan rent:debug-security --rent_id=1

# Test notification sending
php artisan rent:debug-security --test-send
```

### **2. Check System Status**
```bash
# Check cron jobs
crontab -l

# Check queue workers
ps aux | grep queue:work

# Check Laravel scheduler
php artisan schedule:list

# Check failed jobs
php artisan queue:failed
```

## 🚨 **Common Issues & Solutions**

### **Issue 1: Cron Job Not Running**

#### **Symptoms:**
- Notifications never sent
- `php artisan schedule:list` shows no recent runs
- No entries in `storage/logs/laravel.log`

#### **Solutions:**

**A. Check if cron service is running:**
```bash
# Ubuntu/Debian
sudo systemctl status cron

# CentOS/RHEL
sudo systemctl status crond

# macOS
sudo launchctl list | grep cron
```

**B. Verify cron job exists:**
```bash
crontab -l
# Should show: * * * * * cd /path/to/rentroom && php artisan schedule:run >> /dev/null 2>&1
```

**C. Add cron job manually:**
```bash
# Open crontab editor
crontab -e

# Add this line (replace with your actual path)
* * * * * cd /d/Aplikasi/laragon/www/rentroom && php artisan schedule:run >> /dev/null 2>&1
```

**D. Test cron manually:**
```bash
# Run scheduler manually
php artisan schedule:run

# Check if commands execute
php artisan rent:notify-security-end-time
```

### **Issue 2: Queue Worker Not Processing**

#### **Symptoms:**
- Notifications stuck in queue
- `php artisan queue:failed` shows failed jobs
- No WhatsApp messages sent

#### **Solutions:**

**A. Start queue worker:**
```bash
# Start in foreground (for testing)
php artisan queue:work

# Start in background
php artisan queue:work --daemon

# Start with specific options
php artisan queue:work --daemon --tries=3 --timeout=60
```

**B. Check queue configuration:**
```bash
# Check .env file
cat .env | grep QUEUE

# Should show:
# QUEUE_CONNECTION=database
# QUEUE_QUEUE=default
```

**C. Create jobs table if missing:**
```bash
# Create jobs table
php artisan queue:table
php artisan migrate

# Create failed_jobs table
php artisan queue:failed-table
php artisan migrate
```

**D. Clear failed jobs:**
```bash
# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### **Issue 3: Timezone Mismatch**

#### **Symptoms:**
- Notifications sent at wrong time
- Time calculations incorrect
- Database time vs application time mismatch

#### **Solutions:**

**A. Check timezone settings:**
```bash
# Check PHP timezone
php -r "echo date_default_timezone_get();"

# Check Laravel timezone
php artisan tinker --execute="echo config('app.timezone');"

# Check database timezone
php artisan tinker --execute="echo DB::select('SELECT NOW() as now')[0]->now;"
```

**B. Fix timezone in .env:**
```env
APP_TIMEZONE=Asia/Jakarta
DB_TIMEZONE=+07:00
```

**C. Update AppServiceProvider:**
```php
// In app/Providers/AppServiceProvider.php
public function boot()
{
    date_default_timezone_set('Asia/Jakarta');
    config(['app.timezone' => 'Asia/Jakarta']);
}
```

### **Issue 4: GOWA Server Not Accessible**

#### **Symptoms:**
- "GOWA connection failed" errors
- WhatsApp messages never sent
- Connection timeout errors

#### **Solutions:**

**A. Check GOWA server status:**
```bash
# Check if GOWA is running
ps aux | grep gowa

# Check port availability
netstat -tulpn | grep :3000

# Test connection
curl -u admin:admin http://localhost:3000
```

**B. Start GOWA server:**
```bash
# Navigate to GOWA directory
cd gowa

# Start REST API mode
./windows-amd64.exe rest --basic-auth=admin:admin --port=3000 --debug=true

# Or for Linux/Mac
./gowa rest --basic-auth=admin:admin --port=3000 --debug=true
```

**C. Check GOWA configuration:**
```bash
# Check .env WhatsApp settings
cat .env | grep WHATSAPP

# Should show:
# WHATSAPP_BASE_URL=http://localhost:3000
# WHATSAPP_CLIENT_ID=your_client_id
# WHATSAPP_API_KEY=your_api_key
# WHATSAPP_BASIC_USER=admin
# WHATSAPP_BASIC_PASSWORD=admin
# WHATSAPP_USE_API_CREDENTIALS=false
```

### **Issue 5: Security Users Missing Phone Numbers**

#### **Symptoms:**
- "No security users with phone numbers found"
- Security users exist but no notifications sent

#### **Solutions:**

**A. Check security users:**
```bash
php artisan tinker --execute="
    \$users = App\Models\User::where('role_id', 2)->get();
    foreach (\$users as \$user) {
        echo \$user->name . ': ' . (\$user->phone ?? 'NO PHONE') . PHP_EOL;
    }
"
```

**B. Update security user phone:**
```bash
php artisan tinker --execute="
    \$user = App\Models\User::where('role_id', 2)->first();
    if (\$user) {
        \$user->update(['phone' => '6281234567890']);
        echo 'Phone updated for ' . \$user->name . PHP_EOL;
    }
"
```

**C. Create test security user:**
```bash
php artisan tinker --execute="
    App\Models\User::create([
        'name' => 'Test Security',
        'email' => 'security@test.com',
        'password' => bcrypt('password'),
        'role_id' => 2,
        'phone' => '6281234567890',
        'status' => 'active'
    ]);
    echo 'Test security user created' . PHP_EOL;
"
```

### **Issue 6: No Active Rents to Monitor**

#### **Symptoms:**
- "No rents have ended at this time"
- No notifications because no data exists

#### **Solutions:**

**A. Check existing rents:**
```bash
php artisan tinker --execute="
    \$rents = App\Models\Rent::all();
    echo 'Total rents: ' . \$rents->count() . PHP_EOL;
    foreach (\$rents as \$rent) {
        echo 'ID: ' . \$rent->id . ', Status: ' . \$rent->status . ', End: ' . \$rent->time_end_use . PHP_EOL;
    }
"
```

**B. Create test rent:**
```bash
php artisan tinker --execute="
    \$user = App\Models\User::first();
    \$room = App\Models\Room::first();
    
    if (\$user && \$room) {
        \$rent = App\Models\Rent::create([
            'user_id' => \$user->id,
            'room_id' => \$room->id,
            'time_start_use' => now()->subHour(),
            'time_end_use' => now()->addMinutes(5), // Will end in 5 minutes
            'purpose' => 'Testing notifications',
            'status' => 'dipinjam'
        ]);
        echo 'Test rent created with ID: ' . \$rent->id . PHP_EOL;
    }
"
```

## 🔧 **Advanced Troubleshooting**

### **1. Enable Detailed Logging**
```bash
# Set log level to debug
echo "LOG_LEVEL=debug" >> .env

# Clear log cache
php artisan config:clear

# Monitor logs in real-time
tail -f storage/logs/laravel.log
```

### **2. Test Individual Components**
```bash
# Test WhatsApp service
php artisan tinker --execute="
    \$service = app(App\Services\WhatsAppService::class);
    echo 'Connection: ' . (\$service->checkConnection() ? 'OK' : 'FAILED') . PHP_EOL;
"

# Test notification channel
php artisan tinker --execute="
    \$channel = app('Illuminate\Notifications\ChannelManager')->driver('whatsapp');
    echo 'Channel: ' . get_class(\$channel) . PHP_EOL;
"
```

### **3. Check Database Integrity**
```bash
# Check table structure
php artisan tinker --execute="
    echo 'Users table: ' . (Schema::hasTable('users') ? 'OK' : 'MISSING') . PHP_EOL;
    echo 'Rents table: ' . (Schema::hasTable('rents') ? 'OK' : 'MISSING') . PHP_EOL;
    echo 'Notifications table: ' . (Schema::hasTable('notifications') ? 'OK' : 'MISSING') . PHP_EOL;
"
```

## 📋 **Step-by-Step Recovery**

### **Complete System Reset:**
```bash
# 1. Stop all services
pkill -f "queue:work"
pkill -f "gowa"

# 2. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 3. Restart GOWA
cd gowa && ./windows-amd64.exe rest --basic-auth=admin:admin --port=3000

# 4. Start queue worker
php artisan queue:work --daemon

# 5. Test system
php artisan rent:debug-security --test-send
```

### **Manual Testing:**
```bash
# 1. Test basic functionality
php artisan whatsapp:test

# 2. Check security users
php artisan rent:debug-security

# 3. Test notification manually
php artisan rent:notify-security-end-time

# 4. Monitor logs
tail -f storage/logs/laravel.log
```

## 🎯 **Success Indicators**

### **System Working Correctly When:**
- ✅ `php artisan rent:debug-security` shows security users with phone numbers
- ✅ `php artisan whatsapp:test` shows "GOWA connection successful"
- ✅ `php artisan rent:notify-security-end-time` finds and processes rents
- ✅ Queue worker is running: `ps aux | grep queue:work`
- ✅ Cron job exists: `crontab -l` shows schedule:run
- ✅ Logs show successful notification delivery

### **Common Success Messages:**
```
✅ GOWA connection successful
✅ Security notification sent to [Name] ([Phone])
✅ Security end-time notifications completed. X notification(s) sent successfully.
```

## 🆘 **Still Not Working?**

If all troubleshooting steps fail:

1. **Check system resources:** CPU, memory, disk space
2. **Verify network connectivity:** Firewall, proxy settings
3. **Check PHP extensions:** Required extensions installed
4. **Review server logs:** Apache/Nginx error logs
5. **Contact support:** Provide debug output and error messages

## 📞 **Emergency Commands**

```bash
# Force notification test (bypass all checks)
php artisan rent:debug-security --test-send --rent_id=1

# Reset entire notification system
php artisan queue:flush && php artisan config:clear && php artisan cache:clear

# Check system health
php artisan rent:debug-security && php artisan whatsapp:test && php artisan queue:work --once
```

---

**Remember:** Most issues are related to cron jobs, queue workers, or GOWA server. Start with the debug command and work through each component systematically.





