<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Notifications\RentNotification;
use Carbon\Carbon;

class TestRentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rent:test-reminders {--rent_id=} {--force}';

    /**
     * The console command description.
     */
    protected $description = 'Test rent reminder system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Rent Reminder System...');

        $rentId = $this->option('rent_id');
        $force = $this->option('force');

        if ($rentId) {
            // Test specific rent
            $rent = Rent::with(['user', 'room'])->find($rentId);
            if (!$rent) {
                $this->error("❌ Rent with ID {$rentId} not found");
                return 1;
            }

            $this->info("Testing reminder for rent ID: {$rentId}");
            $this->info("User: {$rent->user->name}");
            $this->info("Room: {$rent->room->name}");
            $this->info("Start time: {$rent->time_start_use}");
            $this->info("Status: {$rent->status}");

            if ($rent->status !== 'dipinjam' && !$force) {
                $this->warn("⚠️ Rent status is '{$rent->status}', not 'dipinjam'");
                $this->warn("Use --force to send reminder anyway");
                return 1;
            }

            if (!$rent->user->phone) {
                $this->error("❌ User {$rent->user->name} has no phone number");
                return 1;
            }

            try {
                $rent->user->notify(new RentNotification($rent, 'reminder'));
                $this->info("✅ Reminder sent successfully to {$rent->user->name}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to send reminder: " . $e->getMessage());
                return 1;
            }

        } else {
            // Show upcoming rents (only active ones)
            $this->info("\n=== UPCOMING RENTS (Next 2 hours) - Active Only ===");
            
            $upcomingRents = Rent::where('status', 'dipinjam')
                ->where('time_start_use', '>', Carbon::now())
                ->where('time_start_use', '<', Carbon::now()->addHours(2))
                ->with(['user', 'room'])
                ->orderBy('time_start_use')
                ->get();

            if ($upcomingRents->count() == 0) {
                $this->warn("No upcoming rents found in the next 2 hours");
                return 0;
            }

            $this->table(
                ['ID', 'User', 'Phone', 'Room', 'Start Time', 'Time Until Start'],
                $upcomingRents->map(function ($rent) {
                    $timeUntilStart = Carbon::now()->diffForHumans($rent->time_start_use, true);
                    return [
                        $rent->id,
                        $rent->user->name,
                        $rent->user->phone ?? 'No phone',
                        $rent->room->name,
                        $rent->time_start_use->format('d/m/Y H:i'),
                        $timeUntilStart
                    ];
                })
            );

            $this->info("\nUse --rent_id=<id> to test reminder for specific rent");
            $this->info("Use --rent_id=<id> --force to test even if status is not 'dipinjam'");
            
            // Show completed rents for comparison
            $completedRents = Rent::where('status', 'selesai')
                ->where('time_start_use', '>', Carbon::now()->subDays(1))
                ->with(['user', 'room'])
                ->orderBy('time_start_use', 'desc')
                ->take(3)
                ->get();
                
            if ($completedRents->count() > 0) {
                $this->info("\n=== RECENTLY COMPLETED RENTS (No reminders sent) ===");
                $this->table(
                    ['ID', 'User', 'Room', 'Start Time', 'Status'],
                    $completedRents->map(function ($rent) {
                        return [
                            $rent->id,
                            $rent->user->name,
                            $rent->room->name,
                            $rent->time_start_use->format('d/m/Y H:i'),
                            $rent->status
                        ];
                    })
                );
            }
        }

        return 0;
    }
} 