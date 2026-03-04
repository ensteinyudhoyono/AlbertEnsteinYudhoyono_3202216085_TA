<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Notifications\RentNotification;
use Carbon\Carbon;

class TestExpiredRents extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rent:test-expired {--rent_id=} {--force}';

    /**
     * The console command description.
     */
    protected $description = 'Test expired rent notification system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Expired Rent Notification System...');

        $rentId = $this->option('rent_id');
        $force = $this->option('force');

        if ($rentId) {
            // Test specific rent
            $rent = Rent::with(['user', 'room'])->find($rentId);
            if (!$rent) {
                $this->error("❌ Rent with ID {$rentId} not found");
                return 1;
            }

            $this->info("Testing expired notification for rent ID: {$rentId}");
            $this->info("User: {$rent->user->name}");
            $this->info("Room: {$rent->room->name}");
            $this->info("Start time: {$rent->time_start_use}");
            $this->info("End time: {$rent->time_end_use}");
            $this->info("Status: {$rent->status}");
            $this->info("Is expired: " . ($rent->time_end_use < Carbon::now() ? 'Yes' : 'No'));

            if ($rent->status !== 'dipinjam' && !$force) {
                $this->warn("⚠️ Rent status is '{$rent->status}', not 'dipinjam'");
                $this->warn("Use --force to send notification anyway");
                return 1;
            }

            if ($rent->time_end_use >= Carbon::now() && !$force) {
                $this->warn("⚠️ Rent has not expired yet");
                $this->warn("Use --force to send notification anyway");
                return 1;
            }

            if (!$rent->user->phone) {
                $this->error("❌ User {$rent->user->name} has no phone number");
                return 1;
            }

            try {
                $rent->user->notify(new RentNotification($rent, 'expired'));
                $this->info("✅ Expired notification sent successfully to {$rent->user->name}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to send expired notification: " . $e->getMessage());
                return 1;
            }

        } else {
            // Show expired rents
            $this->info("\n=== EXPIRED RENTS (Status: dipinjam) ===");
            
            $expiredRents = Rent::where('status', 'dipinjam')
                ->where('time_end_use', '<', Carbon::now())
                ->with(['user', 'room'])
                ->orderBy('time_end_use', 'desc')
                ->get();

            if ($expiredRents->count() == 0) {
                $this->warn("No expired rents found");
                return 0;
            }

            $this->table(
                ['ID', 'User', 'Phone', 'Room', 'Start Time', 'End Time', 'Time Since Expired'],
                $expiredRents->map(function ($rent) {
                    $timeSinceExpired = Carbon::now()->diffForHumans($rent->time_end_use, true);
                    return [
                        $rent->id,
                        $rent->user->name,
                        $rent->user->phone ?? 'No phone',
                        $rent->room->name,
                        $rent->time_start_use->format('d/m/Y H:i'),
                        $rent->time_end_use->format('d/m/Y H:i'),
                        $timeSinceExpired
                    ];
                })
            );

            $this->info("\nUse --rent_id=<id> to test expired notification for specific rent");
            $this->info("Use --rent_id=<id> --force to test even if conditions are not met");
            
            // Show active rents that will expire soon
            $this->info("\n=== ACTIVE RENTS EXPIRING SOON (Next 2 hours) ===");
            
            $expiringSoonRents = Rent::where('status', 'dipinjam')
                ->where('time_end_use', '>', Carbon::now())
                ->where('time_end_use', '<', Carbon::now()->addHours(2))
                ->with(['user', 'room'])
                ->orderBy('time_end_use')
                ->get();

            if ($expiringSoonRents->count() == 0) {
                $this->warn("No rents expiring in the next 2 hours");
            } else {
                $this->table(
                    ['ID', 'User', 'Room', 'Start Time', 'End Time', 'Time Until Expired'],
                    $expiringSoonRents->map(function ($rent) {
                        $timeUntilExpired = Carbon::now()->diffForHumans($rent->time_end_use, true);
                        return [
                            $rent->id,
                            $rent->user->name,
                            $rent->room->name,
                            $rent->time_start_use->format('d/m/Y H:i'),
                            $rent->time_end_use->format('d/m/Y H:i'),
                            $timeUntilExpired
                        ];
                    })
                );
            }
        }

        return 0;
    }
} 