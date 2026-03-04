<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Models\User;
use App\Notifications\SecurityOverdueNotification;
use Carbon\Carbon;

class TestSecurityEndTimeNotification extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'whatsapp:test-security-end-time {--rent_id=} {--phone=} {--force}';

    /**
     * The console command description.
     */
    protected $description = 'Test security end-time notification system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Security End-Time Notification System...');

        // Test 1: Check GOWA connection
        $whatsappService = app(\App\Services\WhatsAppService::class);
        $isConnected = $whatsappService->checkConnection();
        
        if ($isConnected) {
            $this->info('✅ GOWA connection successful');
        } else {
            $this->error('❌ GOWA connection failed');
            $this->warn('Please make sure GOWA is running on http://localhost:3000');
            return 1;
        }

        // Test 2: Show security users
        $this->info("\n=== SECURITY USERS (Role ID = 2) ===");
        
        $securityUsers = User::where('role_id', 2)->get();
        if ($securityUsers->count() == 0) {
            $this->warn("No security users found");
            return 1;
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

        // Test 3: Test with specific rent ID
        $rentId = $this->option('rent_id');
        if ($rentId) {
            $rent = Rent::with(['user', 'room', 'items'])->find($rentId);
            if ($rent) {
                $this->info("\n=== TESTING NOTIFICATION FOR RENT ID: {$rentId} ===");
                $this->info("Room: {$rent->room->name}");
                $this->info("User: {$rent->user->name}");
                $this->info("Start Time: {$rent->time_start_use->format('d/m/Y H:i')}");
                $this->info("End Time: {$rent->time_end_use->format('d/m/Y H:i')}");
                $this->info("Status: {$rent->status}");
                
                // Check if rent has ended
                $now = Carbon::now();
                $hasEnded = $rent->time_end_use <= $now;
                
                if ($hasEnded || $this->option('force')) {
                    $this->info("\nSending security notifications...");
                    
                    $sentCount = 0;
                    foreach ($securityUsers as $security) {
                        if (!$security->phone) {
                            $this->warn("⚠️ Security {$security->name} has no phone number, skipping.");
                            continue;
                        }
                        
                        try {
                            $security->notify(new SecurityOverdueNotification($rent));
                            $this->info("✅ Security notification sent to {$security->name} ({$security->phone})");
                            $sentCount++;
                        } catch (\Exception $e) {
                            $this->error("❌ Failed to send notification to {$security->name}: " . $e->getMessage());
                        }
                    }
                    
                    $this->info("\nTest completed. {$sentCount} notification(s) sent successfully.");
                } else {
                    $this->warn("⚠️ Rent has not ended yet. Use --force to test anyway.");
                    $this->info("Current time: " . $now->format('d/m/Y H:i'));
                    $this->info("End time: " . $rent->time_end_use->format('d/m/Y H:i'));
                }
            } else {
                $this->error("❌ Rent with ID {$rentId} not found");
                return 1;
            }
        } else {
            // Test 4: Show rents that will end soon
            $this->info("\n=== RENTS THAT WILL END SOON (Next 30 minutes) ===");
            
            $soonEndingRents = Rent::where('status', 'dipinjam')
                ->where('time_end_use', '>', Carbon::now())
                ->where('time_end_use', '<', Carbon::now()->addMinutes(30))
                ->with(['user', 'room'])
                ->orderBy('time_end_use')
                ->get();

            if ($soonEndingRents->count() == 0) {
                $this->info("No rents ending soon");
            } else {
                $this->table(
                    ['ID', 'Room', 'User', 'End Time', 'Minutes Left'],
                    $soonEndingRents->map(function ($rent) {
                        $minutesLeft = Carbon::now()->diffInMinutes($rent->time_end_use, false);
                        return [
                            $rent->id,
                            $rent->room->name,
                            $rent->user->name,
                            $rent->time_end_use->format('H:i'),
                            $minutesLeft > 0 ? $minutesLeft : 'Ended'
                        ];
                    })
                );
            }
            
            $this->info("\nUse --rent_id=<id> to test notification for specific rent");
            $this->info("Use --force to test even if conditions are not met");
        }

        return 0;
    }
}


