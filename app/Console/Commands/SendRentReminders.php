<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Notifications\RentNotification;
use Carbon\Carbon;

class SendRentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rent:send-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Send reminder notifications for rents that will start in 30 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for rents that need reminders...');

        // Get current time
        $now = Carbon::now();
        
        // Calculate 30 minutes from now
        $thirtyMinutesFromNow = $now->copy()->addMinutes(30);
        
        // Find rents that will start in approximately 30 minutes (±5 minutes tolerance)
        // Only for active rents (dipinjam), exclude completed ones (selesai)
        $rentsNeedingReminders = Rent::where('status', 'dipinjam')
            ->whereBetween('time_start_use', [
                $thirtyMinutesFromNow->copy()->subMinutes(5),
                $thirtyMinutesFromNow->copy()->addMinutes(5)
            ])
            ->with(['user', 'room'])
            ->get();

        if ($rentsNeedingReminders->count() == 0) {
            $this->info('No rents need reminders at this time.');
            return 0;
        }

        $this->info("Found {$rentsNeedingReminders->count()} rent(s) that need reminders.");

        $sentCount = 0;
        foreach ($rentsNeedingReminders as $rent) {
            // Check if user has phone number
            if (!$rent->user->phone) {
                $this->warn("⚠️ User {$rent->user->name} has no phone number, skipping reminder.");
                continue;
            }

            try {
                // Send reminder notification
                $rent->user->notify(new RentNotification($rent, 'reminder'));
                
                $this->info("✅ Reminder sent to {$rent->user->name} for room {$rent->room->name}");
                $sentCount++;
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to send reminder to {$rent->user->name}: " . $e->getMessage());
            }
        }

        $this->info("Reminder process completed. {$sentCount} reminder(s) sent successfully.");
        return 0;
    }
} 