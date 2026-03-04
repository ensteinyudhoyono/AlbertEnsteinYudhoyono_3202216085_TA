<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Models\User;
use App\Notifications\SecurityOverdueNotification;
use Carbon\Carbon;

class DebugSecurityNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rent:debug-security {--rent_id=} {--test-send}';

    /**
     * The console command description.
     */
    protected $description = 'Debug security notification system and show detailed information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 DEBUGGING SECURITY NOTIFICATION SYSTEM');
        $this->info('==========================================');

        // 1. Check current time and timezone
        $this->info("\n1️⃣ TIME & TIMEZONE CHECK");
        $now = Carbon::now();
        $this->info("Current time: {$now->format('Y-m-d H:i:s')}");
        $this->info("Timezone: {$now->timezone->getName()}");
        $this->info("UTC time: {$now->utc()->format('Y-m-d H:i:s')}");

        // 2. Check security users
        $this->info("\n2️⃣ SECURITY USERS CHECK");
        $securityUsers = User::where('role_id', 2)->get();
        
        if ($securityUsers->count() == 0) {
            $this->error("❌ No security users found!");
            return 1;
        }

        $this->table(
            ['ID', 'Name', 'Email', 'Phone', 'Status', 'Created'],
            $securityUsers->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone ?? 'NO PHONE',
                    $user->status,
                    $user->created_at->format('Y-m-d H:i')
                ];
            })
        );

        // 3. Check active rents
        $this->info("\n3️⃣ ACTIVE RENTS CHECK");
        $activeRents = Rent::where('status', 'dipinjam')
            ->with(['user', 'room'])
            ->orderBy('time_end_use')
            ->get();

        if ($activeRents->count() == 0) {
            $this->warn("⚠️ No active rents found!");
        } else {
            $this->table(
                ['ID', 'Room', 'User', 'Start Time', 'End Time', 'Minutes Left', 'Status'],
                $activeRents->map(function ($rent) use ($now) {
                    $minutesLeft = $now->diffInMinutes($rent->time_end_use, false);
                    $status = $minutesLeft > 0 ? "Active ({$minutesLeft}m left)" : "Ended ({abs($minutesLeft)}m ago)";
                    
                    return [
                        $rent->id,
                        $rent->room->name,
                        $rent->user->name,
                        $rent->time_start_use->format('H:i'),
                        $rent->time_end_use->format('H:i'),
                        $minutesLeft > 0 ? $minutesLeft : abs($minutesLeft),
                        $status
                    ];
                })
            );
        }

        // 4. Check rents ending soon (next 30 minutes)
        $this->info("\n4️⃣ RENTS ENDING SOON (Next 30 minutes)");
        $endingSoon = Rent::where('status', 'dipinjam')
            ->where('time_end_use', '>', $now)
            ->where('time_end_use', '<', $now->copy()->addMinutes(30))
            ->with(['user', 'room'])
            ->orderBy('time_end_use')
            ->get();

        if ($endingSoon->count() == 0) {
            $this->info("ℹ️ No rents ending in the next 30 minutes");
        } else {
            $this->table(
                ['ID', 'Room', 'User', 'End Time', 'Minutes Left'],
                $endingSoon->map(function ($rent) use ($now) {
                    $minutesLeft = $now->diffInMinutes($rent->time_end_use, false);
                    return [
                        $rent->id,
                        $rent->room->name,
                        $rent->user->name,
                        $rent->time_end_use->format('H:i'),
                        $minutesLeft
                    ];
                })
            );
        }

        // 5. Check rents that just ended (last 5 minutes)
        $this->info("\n5️⃣ RENTS THAT JUST ENDED (Last 5 minutes)");
        $justEnded = Rent::where('status', 'dipinjam')
            ->where('time_end_use', '>', $now->copy()->subMinutes(5))
            ->where('time_end_use', '<', $now)
            ->with(['user', 'room'])
            ->orderBy('time_end_use', 'desc')
            ->get();

        if ($justEnded->count() == 0) {
            $this->info("ℹ️ No rents ended in the last 5 minutes");
        } else {
            $this->table(
                ['ID', 'Room', 'User', 'End Time', 'Minutes Ago'],
                $justEnded->map(function ($rent) use ($now) {
                    $minutesAgo = $now->diffInMinutes($rent->time_end_use);
                    return [
                        $rent->id,
                        $rent->room->name,
                        $rent->user->name,
                        $rent->time_end_use->format('H:i'),
                        $minutesAgo
                    ];
                })
            );
        }

        // 6. Test notification sending if requested
        if ($this->option('test-send')) {
            $this->info("\n6️⃣ TESTING NOTIFICATION SENDING");
            
            $testRent = null;
            if ($this->option('rent_id')) {
                $testRent = Rent::with(['user', 'room', 'items'])->find($this->option('rent_id'));
            } else {
                // Use the most recent ended rent
                $testRent = $justEnded->first();
            }

            if ($testRent) {
                $this->info("Testing with rent ID: {$testRent->id}");
                $this->info("Room: {$testRent->room->name}");
                $this->info("User: {$testRent->user->name}");
                $this->info("End Time: {$testRent->time_end_use->format('Y-m-d H:i:s')}");

                $sentCount = 0;
                foreach ($securityUsers as $security) {
                    if (!$security->phone) {
                        $this->warn("⚠️ Security {$security->name} has no phone number, skipping.");
                        continue;
                    }

                    try {
                        $security->notify(new SecurityOverdueNotification($testRent));
                        $this->info("✅ Test notification sent to {$security->name} ({$security->phone})");
                        $sentCount++;
                    } catch (\Exception $e) {
                        $this->error("❌ Failed to send test notification to {$security->name}: " . $e->getMessage());
                    }
                }

                $this->info("\nTest completed. {$sentCount} notification(s) sent successfully.");
            } else {
                $this->error("❌ No suitable rent found for testing");
            }
        }

        // 7. Check scheduler status
        $this->info("\n7️⃣ SCHEDULER STATUS");
        $this->info("Command to run manually: php artisan rent:notify-security-end-time");
        $this->info("Scheduler should run every minute");
        $this->info("Check cron job: crontab -l");
        $this->info("Check queue: php artisan queue:work");

        // 8. Recommendations
        $this->info("\n8️⃣ RECOMMENDATIONS");
        
        if ($securityUsers->where('phone')->count() == 0) {
            $this->error("❌ CRITICAL: No security users have phone numbers!");
            $this->info("   Solution: Update security users with phone numbers");
        }
        
        if ($activeRents->count() == 0) {
            $this->warn("⚠️ No active rents to monitor");
            $this->info("   Solution: Create some test rents or wait for real data");
        }

        $this->info("\n🔍 Debug completed. Check the information above for issues.");
        return 0;
    }
}





