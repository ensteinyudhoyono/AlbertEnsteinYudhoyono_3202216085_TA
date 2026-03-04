<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;

class CalculatePenalties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rent:calculate-penalties {--force : Force recalculation even if penalty already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate penalties for completed rents that were returned late';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Calculating penalties for late returns...');

        $query = Rent::where('status', 'selesai')
            ->whereNotNull('transaction_end');

        if (!$this->option('force')) {
            $query->where('penalty_amount', 0);
        }

        $rents = $query->get();

        if ($rents->count() == 0) {
            $this->info('No rents found that need penalty calculation.');
            return Command::SUCCESS;
        }

        $this->info("Found {$rents->count()} rent(s) to process.");

        $updatedCount = 0;
        $totalPenalty = 0;

        foreach ($rents as $rent) {
            $penaltyAmount = $rent->calculatePenalty();
            
            if ($penaltyAmount > 0) {
                $rent->update(['penalty_amount' => $penaltyAmount]);
                $updatedCount++;
                $totalPenalty += $penaltyAmount;
                
                $this->info("✅ Updated rent #{$rent->id} - {$rent->user->name} - {$rent->room->name} - Penalty: Rp " . number_format($penaltyAmount, 0, ',', '.'));
            } else {
                $this->line("⏭️  Rent #{$rent->id} - {$rent->user->name} - {$rent->room->name} - No penalty (returned on time)");
            }
        }

        $this->info("\nPenalty calculation completed!");
        $this->info("Updated: {$updatedCount} rent(s)");
        $this->info("Total penalty amount: Rp " . number_format($totalPenalty, 0, ',', '.'));

        return Command::SUCCESS;
    }
}
