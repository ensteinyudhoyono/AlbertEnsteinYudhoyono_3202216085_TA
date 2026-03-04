# 🧪 **Testing Guide: Security End-Time Notifications**

## 🎯 **Tujuan Testing**
Memverifikasi bahwa notifikasi untuk security (role=2) dikirim tepat saat `time_end_use` dan berfungsi dengan baik.

## 🚀 **Setup Testing Environment**

### **1. Pastikan GOWA Server Berjalan**
```bash
# Check if GOWA is running
curl -u admin:admin http://localhost:3000

# Expected response: GOWA server info
```

### **2. Pastikan Cron Job Aktif**
```bash
# Check if Laravel scheduler is running
crontab -l

# Should show:
# * * * * * cd /path/to/rentroom && php artisan schedule:run >> /dev/null 2>&1
```

### **3. Check Queue Worker**
```bash
# Start queue worker if not running
php artisan queue:work

# Or run in background
php artisan queue:work --daemon
```

## 🧪 **Test Cases**

### **Test Case 1: Koneksi WhatsApp**
```bash
# Test basic connection
php artisan whatsapp:test

# Expected output:
# ✅ GOWA connection successful
```

### **Test Case 2: List Security Users**
```bash
# Test security user listing
php artisan whatsapp:test-security-end-time

# Expected output:
# === SECURITY USERS (Role ID = 2) ===
# +----+---------+------------------+-------------+--------+
# | ID | Name    | Email           | Phone       | Status |
# +----+---------+------------------+-------------+--------+
# | 2  | Penjaga | penjaga@gmail.com| 6281234567890 | active |
# +----+---------+------------------+-------------+--------+
```

### **Test Case 3: Test dengan Rent ID Spesifik**
```bash
# Test notification untuk rent tertentu
php artisan whatsapp:test-security-end-time --rent_id=1

# Expected output:
# === TESTING NOTIFICATION FOR RENT ID: 1 ===
# Room: Aula Elisabet
# User: John Doe
# Start Time: 09/12/2024 10:00
# End Time: 09/12/2024 12:00
# Status: dipinjam
# 
# Sending security notifications...
# ✅ Security notification sent to Penjaga (6281234567890)
# 
# Test completed. 1 notification(s) sent successfully.
```

### **Test Case 4: Force Test (Meskipun Belum Waktunya)**
```bash
# Test force notification
php artisan whatsapp:test-security-end-time --rent_id=1 --force

# Expected output:
# ✅ Security notification sent to Penjaga (6281234567890)
```

### **Test Case 5: Test Scheduler Otomatis**
```bash
# Test scheduler command langsung
php artisan rent:notify-security-end-time

# Expected output:
# Checking for rents that have reached their end time...
# Found 1 rent(s) that have just ended.
# Found 1 security user(s) to notify.
# 
# Processing rent ID 1 for room Aula Elisabet
# ✅ Security notification sent to Penjaga (6281234567890)
# 
# Security end-time notifications completed. 1 notification(s) sent successfully.
```

## 📱 **Verifikasi WhatsApp Message**

### **Expected WhatsApp Message:**
```
🚨 PEMINJAMAN TELAH BERAKHIR - TINDAK LANJUTI

Ruangan: Aula Elisabet
Peminjam: John Doe
Waktu: 09/12/2024 10:00 - 09/12/2024 12:00
Tujuan: Meeting Tim

⚠️ Peminjaman ini telah berakhir. Silakan:
1. Cek apakah ruangan sudah dikembalikan
2. Pastikan semua item sudah dikembalikan
3. Tindak lanjuti jika ada keterlambatan

Status: MENUNGGU TINDAK LANJUT SECURITY
```

## 🔍 **Debugging & Troubleshooting**

### **1. Check Laravel Logs**
```bash
# Monitor logs in real-time
tail -f storage/logs/laravel.log

# Look for:
# - WhatsApp API calls
# - Notification delivery status
# - Error messages
```

### **2. Check Database Notifications**
```sql
-- Check if notifications are stored
SELECT * FROM notifications 
WHERE type = 'App\\Notifications\\SecurityOverdueNotification' 
ORDER BY created_at DESC 
LIMIT 5;

-- Check notification data
SELECT 
    id,
    type,
    notifiable_type,
    notifiable_id,
    data,
    created_at
FROM notifications 
WHERE type = 'App\\Notifications\\SecurityOverdueNotification';
```

### **3. Check Queue Status**
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### **4. Check GOWA Server Status**
```bash
# Check GOWA health
curl -u admin:admin http://localhost:3000/health

# Check GOWA logs
# Look at GOWA console output for errors
```

## 📊 **Performance Testing**

### **1. Load Testing**
```bash
# Test multiple notifications
for i in {1..10}; do
    php artisan rent:notify-security-end-time
    sleep 1
done
```

### **2. Concurrent Testing**
```bash
# Test multiple commands simultaneously
php artisan rent:notify-security-end-time &
php artisan rent:notify-security-end-time &
php artisan rent:notify-security-end-time &
wait
```

## 🚨 **Common Issues & Solutions**

### **Issue 1: "No security users found"**
**Solution:**
```bash
# Check if security users exist
php artisan tinker
>>> App\Models\User::where('role_id', 2)->get();

# Create test security user if needed
php artisan tinker
>>> App\Models\User::create([
    'name' => 'Test Security',
    'email' => 'security@test.com',
    'password' => bcrypt('password'),
    'role_id' => 2,
    'phone' => '6281234567890',
    'status' => 'active'
]);
```

### **Issue 2: "GOWA connection failed"**
**Solution:**
```bash
# Check GOWA server
ps aux | grep gowa

# Restart GOWA
pkill gowa
./gowa rest --basic-auth=admin:admin --port=3000

# Check port availability
netstat -tulpn | grep :3000
```

### **Issue 3: "No phone number"**
**Solution:**
```bash
# Update user phone number
php artisan tinker
>>> $user = App\Models\User::find(2);
>>> $user->update(['phone' => '6281234567890']);
```

### **Issue 4: "Queue not processing"**
**Solution:**
```bash
# Start queue worker
php artisan queue:work --daemon

# Or use supervisor for production
sudo supervisorctl restart laravel-worker
```

## ✅ **Success Criteria**

### **Functional Requirements:**
- [ ] Security users (role=2) menerima notifikasi tepat saat `time_end_use`
- [ ] Notifikasi dikirim ke semua security users yang aktif
- [ ] Message content sesuai dengan template yang ditentukan
- [ ] Notifikasi tersimpan di database
- [ ] WhatsApp message terkirim dengan sukses

### **Performance Requirements:**
- [ ] Notifikasi dikirim dalam waktu < 5 detik setelah `time_end_use`
- [ ] System dapat handle multiple concurrent notifications
- [ ] No memory leaks atau performance degradation

### **Reliability Requirements:**
- [ ] Notifikasi tetap terkirim meskipun ada network issues
- [ ] Failed notifications dapat di-retry
- [ ] System logs semua activities untuk debugging

## 📝 **Test Report Template**

```markdown
# Test Report: Security End-Time Notifications

**Date:** [Date]
**Tester:** [Name]
**Environment:** [Local/Staging/Production]

## Test Results:
- [ ] Test Case 1: Koneksi WhatsApp
- [ ] Test Case 2: List Security Users  
- [ ] Test Case 3: Test dengan Rent ID
- [ ] Test Case 4: Force Test
- [ ] Test Case 5: Scheduler Otomatis

## Issues Found:
- [Issue description]

## Recommendations:
- [Recommendation]

## Status: ✅ PASS / ❌ FAIL
```

## 🎉 **Congratulations!**

Jika semua test cases berhasil, berarti sistem notifikasi security end-time sudah berfungsi dengan baik! 

Security users akan menerima notifikasi WhatsApp tepat saat peminjaman berakhir, memungkinkan mereka untuk segera mengambil tindakan yang diperlukan.





