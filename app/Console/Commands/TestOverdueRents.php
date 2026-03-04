<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Models\User;
use App\Notifications\RentNotification;
use Carbon\Carbon;

class TestOverdueRents extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rent:test-overdue {--rent_id=} {--force}';

    /**
     * The console command description.
     */
    protected $description = 'Test overdue rent notification system for security';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Overdue Rent Notification System for Security...');

        $rentId = $this->option('rent_id');
        $force = $this->option('force');

        if ($rentId) {
            // Test specific rent
            $rent = Rent::with(['user', 'room'])->find($rentId);
            if (!$rent) {
                $this->error("❌ Rent with ID {$rentId} not found");
                return 1;
            }

            $this->info("Testing overdue notification for rent ID: {$rentId}");
            $this->info("User: {$rent->user->name}");
            $this->info("Room: {$rent->room->name}");
            $this->info("Start time: {$rent->time_start_use}");
            $this->info("End time: {$rent->time_end_use}");
            $this->info("Status: {$rent->status}");
            $this->info("Is overdue (5+ minutes): " . ($rent->time_end_use < Carbon::now()->subMinutes(5) ? 'Yes' : 'No'));

            if ($rent->status !== 'dipinjam' && !$force) {
                $this->warn("⚠️ Rent status is '{$rent->status}', not 'dipinjam'");
                $this->warn("Use --force to send notification anyway");
                return 1;
            }

            if ($rent->time_end_use >= Carbon::now()->subMinutes(5) && !$force) {
                $this->warn("⚠️ Rent is not overdue yet (less than 5 minutes past end time)");
                $this->warn("Use --force to send notification anyway");
                return 1;
            }

            // Get security users
            $securityUsers = User::where('role_id', 2)->whereNotNull('phone')->get();
            if ($securityUsers->count() == 0) {
                $this->error("❌ No security users with phone numbers found");
                return 1;
            }

            $sentCount = 0;
            foreach ($securityUsers as $security) {
                try {
                    $security->notify(new RentNotification($rent, 'overdue'));
                    $this->info("✅ Overdue notification sent successfully to security {$security->name}");
                    $sentCount++;
                } catch (\Exception $e) {
                    $this->error("❌ Failed to send overdue notification to security {$security->name}: " . $e->getMessage());
                }
            }

            if ($sentCount > 0) {
                $this->info("✅ Total {$sentCount} overdue notification(s) sent to security");
            }

        } else {
            // Show overdue rents
            $this->info("\n=== OVERDUE RENTS (5+ minutes past end time) ===");
            
            $overdueRents = Rent::where('status', 'dipinjam')
                ->where('time_end_use', '<', Carbon::now()->subMinutes(5))
                ->with(['user', 'room'])
                ->orderBy('time_end_use', 'desc')
                ->get();

            if ($overdueRents->count() == 0) {
                $this->warn("No overdue rents found");
            } else {
                $this->table(
                    ['ID', 'User', 'Room', 'Start Time', 'End Time', 'Minutes Overdue'],
                    $overdueRents->map(function ($rent) {
                        $minutesOverdue = Carbon::now()->diffInMinutes($rent->time_end_use);
                        return [
                            $rent->id,
                            $rent->user->name,
                            $rent->room->name,
                            $rent->time_start_use->format('d/m/Y H:i'),
                            $rent->time_end_use->format('d/m/Y H:i'),
                            $minutesOverdue . ' menit'
                        ];
                    })
                );
            }

            $this->info("\nUse --rent_id=<id> to test overdue notification for specific rent");
            $this->info("Use --rent_id=<id> --force to test even if conditions are not met");
            
            // Show security users
            $this->info("\n=== SECURITY USERS (Role ID = 2) ===");
            
            $securityUsers = User::where('role_id', 2)->get();
            if ($securityUsers->count() == 0) {
                $this->warn("No security users found");
            } else {
                $this->table(
                    ['ID', 'Name', 'Email', 'Phone', 'Status'],
                    $securityUsers->map(function ($user) {
                        return [
                            $user->id,
                            $user->name,
                            $user->email,
                            $user->phone ?? 'No phone',
                            $user->status
                        ];
                    })
                );
            }
            
            // Show rents that will be overdue soon
            $this->info("\n=== RENTS THAT WILL BE OVERDUE SOON (Next 10 minutes) ===");
            
            $soonOverdueRents = Rent::where('status', 'dipinjam')
                ->where('time_end_use', '>', Carbon::now()->subMinutes(10))
                ->where('time_end_use', '<', Carbon::now()->addMinutes(10))
                ->with(['user', 'room'])
                ->orderBy('time_end_use')
                ->get();

            if ($soonOverdueRents->count() == 0) {
                $this->warn("No rents will be overdue in the next 10 minutes");
            } else {
                $this->table(
                    ['ID', 'User', 'Room', 'Start Time', 'End Time', 'Status'],
                    $soonOverdueRents->map(function ($rent) {
                        $status = $rent->time_end_use < Carbon::now() ? 'Overdue' : 'Will be overdue soon';
                        return [
                            $rent->id,
                            $rent->user->name,
                            $rent->room->name,
                            $rent->time_start_use->format('d/m/Y H:i'),
                            $rent->time_end_use->format('d/m/Y H:i'),
                            $status
                        ];
                    })
                );
            }
        }

        return 0;
    }
} 