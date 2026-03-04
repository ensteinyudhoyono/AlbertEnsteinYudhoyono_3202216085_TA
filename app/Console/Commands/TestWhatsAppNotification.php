<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Notifications\RentNotification;
use App\Notifications\AdminRentNotification;
use App\Services\WhatsAppService;

class TestWhatsAppNotification extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'whatsapp:test {--rent_id=} {--phone=} {--message=} {--admin=}';

    /**
     * The console command description.
     */
    protected $description = 'Test WhatsApp notification system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing WhatsApp Notification System...');

        // Test 1: Check GOWA connection
        $whatsappService = app(WhatsAppService::class);
        $isConnected = $whatsappService->checkConnection();
        
        if ($isConnected) {
            $this->info('✅ GOWA connection successful');
        } else {
            $this->error('❌ GOWA connection failed');
            $this->warn('Please make sure GOWA is running on http://localhost:3000');
            return 1;
        }

        // Test 2: Send test message
        $phone = $this->option('phone');
        $message = $this->option('message') ?? 'Test pesan dari Laravel WhatsApp Notification System!';
        
        if ($phone) {
            $this->info("Sending test message to: {$phone}");
            $result = $whatsappService->sendTextMessage($phone, $message);
            
            if ($result) {
                $this->info('✅ Test message sent successfully');
            } else {
                $this->error('❌ Failed to send test message');
            }
        }

        // Test 3: Test notification with rent data
        $rentId = $this->option('rent_id');
        if ($rentId) {
            $rent = Rent::find($rentId);
            if ($rent) {
                $this->info("Testing notification for rent ID: {$rentId}");
                
                // Check if testing admin notification
                if ($this->option('admin')) {
                    $admins = \App\Models\User::where('role_id', 1)->whereNotNull('phone')->get();
                    if ($admins->count() > 0) {
                        $this->info("Sending admin notification to {$admins->count()} admin(s)");
                        foreach ($admins as $admin) {
                            $this->info("Sending to admin: {$admin->name} ({$admin->phone})");
                            try {
                                $admin->notify(new AdminRentNotification($rent));
                                $this->info("✅ Admin notification sent to {$admin->name}");
                            } catch (\Exception $e) {
                                $this->error("❌ Failed to send admin notification to {$admin->name}: " . $e->getMessage());
                            }
                        }
                    } else {
                        $this->warn('⚠️ No admins with phone numbers found');
                    }
                } else {
                    // Regular user notification
                    if ($rent->user->phone) {
                        $this->info("Sending notification to user: {$rent->user->name} ({$rent->user->phone})");
                        
                        try {
                            $rent->user->notify(new RentNotification($rent, 'approved'));
                            $this->info('✅ Notification sent successfully');
                        } catch (\Exception $e) {
                            $this->error('❌ Failed to send notification: ' . $e->getMessage());
                        }
                    } else {
                        $this->warn('⚠️ User does not have phone number');
                    }
                }
            } else {
                $this->error("❌ Rent with ID {$rentId} not found");
            }
        }

        // Test 4: Show available rents for testing
        if (!$rentId) {
            $this->info("\nAvailable rents for testing:");
            $rents = Rent::with('user', 'room')->latest()->take(5)->get();
            
            if ($rents->count() > 0) {
                $this->table(
                    ['ID', 'User', 'Phone', 'Room', 'Status'],
                    $rents->map(function ($rent) {
                        return [
                            $rent->id,
                            $rent->user->name,
                            $rent->user->phone ?? 'No phone',
                            $rent->room->name,
                            $rent->status
                        ];
                    })
                );
                
                $this->info('Use --rent_id=<id> to test notification with specific rent');
                $this->info('Use --rent_id=<id> --admin to test admin notification');
            } else {
                $this->warn('No rents found in database');
            }
        }

        $this->info('Test completed!');
        return 0;
    }
} 