<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Models\User;
use App\Notifications\SecurityOverdueNotification;
use Carbon\Carbon;

class CheckOverdueRents extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rent:check-overdue';

    /**
     * The console command description.
     */
    protected $description = 'Check for overdue rents (5 minutes past end time) and notify security';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for overdue rents (5+ minutes past end time)...');

        // Get current time
        $now = Carbon::now();
        
        // Find rents that ended more than 5 minutes ago but status is still 'dipinjam'
        $overdueRents = Rent::where('status', 'dipinjam')
            ->where('time_end_use', '<', $now->copy()->subMinutes(5))
            ->with(['user', 'room'])
            ->get();

        if ($overdueRents->count() == 0) {
            $this->info('No overdue rents found.');
            return 0;
        }

        $this->info("Found {$overdueRents->count()} overdue rent(s).");

        // Get all security users (role_id = 2)
        $securityUsers = User::where('role_id', 2)->whereNotNull('phone')->get();

        if ($securityUsers->count() == 0) {
            $this->warn('No security users with phone numbers found.');
            return 0;
        }

        $sentCount = 0;
        foreach ($overdueRents as $rent) {
            foreach ($securityUsers as $security) {
                try {
                    // Send overdue notification to security
                    $security->notify(new SecurityOverdueNotification($rent));
                    
                    $this->info("✅ Overdue notification sent to security {$security->name} for rent ID {$rent->id} (Room: {$rent->room->name})");
                    $sentCount++;
                    
                } catch (\Exception $e) {
                    $this->error("❌ Failed to send overdue notification to security {$security->name}: " . $e->getMessage());
                }
            }
        }

        $this->info("Overdue rent check completed. {$sentCount} notification(s) sent successfully.");
        return 0;
    }
} 