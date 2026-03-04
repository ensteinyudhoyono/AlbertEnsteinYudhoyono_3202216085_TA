<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Notifications\RentNotification;
use Carbon\Carbon;

class CheckExpiredRents extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rent:check-expired';

    /**
     * The console command description.
     */
    protected $description = 'Check for expired rents and send notifications to borrowers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired rents...');

        // Get current time
        $now = Carbon::now();
        
        // Find rents that have ended but status is still 'dipinjam'
        $expiredRents = Rent::where('status', 'dipinjam')
            ->where('time_end_use', '<', $now)
            ->with(['user', 'room'])
            ->get();

        if ($expiredRents->count() == 0) {
            $this->info('No expired rents found.');
            return 0;
        }

        $this->info("Found {$expiredRents->count()} expired rent(s).");

        $sentCount = 0;
        foreach ($expiredRents as $rent) {
            // Check if user has phone number
            if (!$rent->user->phone) {
                $this->warn("⚠️ User {$rent->user->name} has no phone number, skipping notification.");
                continue;
            }

            try {
                // Send expired notification
                $rent->user->notify(new RentNotification($rent, 'expired'));
                
                $this->info("✅ Expired notification sent to {$rent->user->name} for room {$rent->room->name}");
                $sentCount++;
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to send expired notification to {$rent->user->name}: " . $e->getMessage());
            }
        }

        $this->info("Expired rent check completed. {$sentCount} notification(s) sent successfully.");
        return 0;
    }
} 