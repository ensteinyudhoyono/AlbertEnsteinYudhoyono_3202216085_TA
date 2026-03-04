<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use Carbon\Carbon;

class CheckAndFixPenalties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rent:check-and-fix-penalties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and fix all penalties for overdue rents (comprehensive check)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Checking and fixing all penalties...');
        
        // 1. Check overdue rents that need auto penalty
        $this->info("\n1. Checking overdue rents (status: dipinjam)...");
        $overdueRents = Rent::where('status', 'dipinjam')
            ->where('time_end_use', '<', now())
            ->where('auto_penalty_calculated', false)
            ->with(['user', 'room'])
            ->get();

        if ($overdueRents->count() > 0) {
            $this->info("Found {$overdueRents->count()} overdue rent(s) needing auto penalty calculation.");
            
            foreach ($overdueRents as $rent) {
                $penaltyAmount = $rent->calculateAutoPenalty();
                if ($penaltyAmount > 0) {
                    $rent->update([
                        'penalty_amount' => $penaltyAmount,
                        'auto_penalty_calculated' => true,
                        'auto_penalty_calculated_at' => now(),
                    ]);
                    
                    $this->info("✅ Auto penalty applied: Rent #{$rent->id} - {$rent->user->name} - Rp " . number_format($penaltyAmount, 0, ',', '.'));
                }
            }
        } else {
            $this->info("No overdue rents found.");
        }

        // 2. Check completed rents with missing penalties
        $this->info("\n2. Checking completed rents with missing penalties...");
        $completedRents = Rent::where('status', 'selesai')
            ->whereNotNull('transaction_end')
            ->where('penalty_amount', 0)
            ->with(['user', 'room'])
            ->get();

        $fixedCount = 0;
        foreach ($completedRents as $rent) {
            $minutesLate = $rent->transaction_end->diffInMinutes($rent->time_end_use);
            if ($minutesLate > 0) {
                $hoursLate = ceil($minutesLate / 60);
                $penaltyAmount = $hoursLate * 5000;
                
                $rent->update(['penalty_amount' => $penaltyAmount]);
                $fixedCount++;
                
                $this->info("✅ Fixed missing penalty: Rent #{$rent->id} - {$rent->user->name} - Rp " . number_format($penaltyAmount, 0, ',', '.') . " ({$minutesLate} min late)");
            }
        }

        if ($fixedCount == 0) {
            $this->info("No completed rents with missing penalties found.");
        } else {
            $this->info("Fixed {$fixedCount} completed rent(s) with missing penalties.");
        }

        // 3. Summary
        $this->info("\n📊 Summary:");
        $this->info("- Overdue rents processed: {$overdueRents->count()}");
        $this->info("- Completed rents fixed: {$fixedCount}");
        $this->info("- Total fixes: " . ($overdueRents->count() + $fixedCount));

        $this->info("\n✅ Penalty check and fix completed!");
        return Command::SUCCESS;
    }
}
