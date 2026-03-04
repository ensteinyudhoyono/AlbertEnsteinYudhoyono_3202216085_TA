<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Models\User;
use App\Notifications\SecurityOverdueNotification;
use Carbon\Carbon;

class SendSecurityEndTimeNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rent:notify-security-end-time {--rent_id=} {--test}';

    /**
     * The console command description.
     */
    protected $description = 'Send notifications to security users when rent time_end_use is reached for immediate action';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info(' Checking for rents that have reached their end time...');

        // Get current time with timezone info
        $now = Carbon::now();
        $this->info("⏰ Current time: {$now->format('Y-m-d H:i:s')} ({$now->timezone->getName()})");
        
        // Log time range we're checking (more precise timing)
        $startTime = $now->copy()->subMinutes(1)->format('Y-m-d H:i:s');
        $endTime = $now->copy()->addMinutes(1)->format('Y-m-d H:i:s');
        $this->info("🔍 Checking rents with end time between: {$startTime} and {$endTime}");
        
        // Find rents that have just ended (within 1 minute tolerance for better accuracy)
        $endedRents = Rent::where('status', 'dipinjam')
            ->whereBetween('time_end_use', [
                $now->copy()->subMinutes(1),
                $now->copy()->addMinutes(1)
            ])
            ->with(['user', 'room', 'items'])
            ->get();

        if ($endedRents->count() == 0) {
            $this->info('✅ No rents have ended at this time.');
            
            // Debug: Show some nearby rents for troubleshooting
            $this->info("\n=== 🔍 DEBUG: Nearby Rents ===");
            $nearbyRents = Rent::where('status', 'dipinjam')
                ->where('time_end_use', '>', $now->copy()->subMinutes(5))
                ->where('time_end_use', '<', $now->copy()->addMinutes(5))
                ->with(['user', 'room'])
                ->orderBy('time_end_use')
                ->get();
                
            if ($nearbyRents->count() > 0) {
                $this->table(
                    ['ID', 'Room', 'User', 'End Time', 'Minutes Ago/From Now'],
                    $nearbyRents->map(function ($rent) use ($now) {
                        $diffMinutes = $now->diffInMinutes($rent->time_end_use, false);
                        $status = $diffMinutes > 0 ? "in {$diffMinutes} min" : abs($diffMinutes) . " min ago";
                        return [
                            $rent->id,
                            $rent->room->name,
                            $rent->user->name,
                            $rent->time_end_use->format('H:i:s'),
                            $status
                        ];
                    })
                );
            } else {
                $this->warn('⚠️ No nearby rents found in ±5 minutes range.');
            }
            
            return 0;
        }

        $this->info(" Found {$endedRents->count()} rent(s) that have just ended - SENDING SECURITY ALERTS!");

        // Get all security users (role_id = 2) with phone numbers
        $securityUsers = User::where('role_id', 2)
            ->where('status', 'active')
            ->whereNotNull('phone')
            ->get();

        if ($securityUsers->count() == 0) {
            $this->error('❌ No active security users with phone numbers found!');
            $this->warn('💡 Please ensure security users are created with role_id = 2 and have phone numbers.');
            return 1;
        }

        $this->info("👮 Found {$securityUsers->count()} security user(s) to notify.");

        $sentCount = 0;
        foreach ($endedRents as $rent) {
            $this->info("\n Processing rent ID {$rent->id} for room {$rent->room->name}");
            $this->info(" Peminjam: {$rent->user->name}");
            $this->info(" Kontak: " . ($rent->user->phone ?? 'Tidak tersedia'));
            $this->info("⏰ Waktu berakhir: {$rent->time_end_use->format('d/m/Y H:i:s')}");
            
            foreach ($securityUsers as $security) {
                try {
                    // Send notification to security
                    $security->notify(new SecurityOverdueNotification($rent));
                    
                    $this->info("✅ Security notification sent to {$security->name} ({$security->phone})");
                    $sentCount++;
                    
                } catch (\Exception $e) {
                    $this->error("❌ Failed to send security notification to {$security->name}: " . $e->getMessage());
                }
            }
        }

        $this->info("\n🎯 Security end-time notifications completed. {$sentCount} notification(s) sent successfully.");
        $this->warn("💡 Security should now take immediate action to check the rooms and contact renters!");
        return 0;
    }
}
