<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rent;
use App\Notifications\RentNotification;

class CalculateAutoPenalties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rent:calculate-auto-penalties {--force : Force recalculation even if auto penalty already calculated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically calculate penalties for overdue rents (time_end_use has passed)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Calculating automatic penalties for overdue rents...');

        // Cari peminjaman yang sudah lewat waktu dan belum dihitung denda otomatis
        $query = Rent::where('time_end_use', '<', now())
            ->where(function($q) {
                $q->where('status', 'dipinjam') // Peminjaman aktif yang terlambat
                  ->orWhere(function($subQ) {
                      // Peminjaman selesai yang terlambat tapi belum dihitung denda otomatis
                      $subQ->where('status', 'selesai')
                           ->where('auto_penalty_calculated', false)
                           ->whereNotNull('transaction_end');
                  });
            });

        if (!$this->option('force')) {
            $query->where('auto_penalty_calculated', false);
        }

        $rents = $query->with(['user', 'room'])->get();

        if ($rents->count() == 0) {
            $this->info('No overdue rents found that need automatic penalty calculation.');
            return Command::SUCCESS;
        }

        $this->info("Found {$rents->count()} overdue rent(s) to process.");

        $updatedCount = 0;
        $totalPenalty = 0;
        $notificationsSent = 0;

        foreach ($rents as $rent) {
            $penaltyAmount = $rent->calculateAutoPenalty();
            
            if ($penaltyAmount > 0) {
                // Update penalty amount and mark as calculated
                $rent->update([
                    'penalty_amount' => $penaltyAmount,
                    'auto_penalty_calculated' => true,
                    'auto_penalty_calculated_at' => now(),
                ]);
                
                $updatedCount++;
                $totalPenalty += $penaltyAmount;
                
                $this->info("✅ Updated rent #{$rent->id} - {$rent->user->name} - {$rent->room->name} - Auto Penalty: Rp " . number_format($penaltyAmount, 0, ',', '.') . " (Overdue: {$rent->getHoursOverdue()} hours)");

                // Send notification to user about automatic penalty
                if ($rent->user->phone) {
                    try {
                        $rent->user->notify(new RentNotification($rent, 'auto_penalty'));
                        $notificationsSent++;
                        $this->line("📱 Notification sent to {$rent->user->name}");
                    } catch (\Exception $e) {
                        $this->warn("⚠️ Failed to send notification to {$rent->user->name}: " . $e->getMessage());
                    }
                } else {
                    $this->warn("⚠️ User {$rent->user->name} has no phone number, skipping notification.");
                }
            } else {
                // Mark as calculated even if no penalty (shouldn't happen for overdue rents)
                $rent->markAutoPenaltyCalculated();
                $this->line("⏭️  Rent #{$rent->id} - {$rent->user->name} - {$rent->room->name} - No penalty (unexpected for overdue rent)");
            }
        }

        $this->info("\nAutomatic penalty calculation completed!");
        $this->info("Updated: {$updatedCount} rent(s)");
        $this->info("Total penalty amount: Rp " . number_format($totalPenalty, 0, ',', '.'));
        $this->info("Notifications sent: {$notificationsSent}");

        return Command::SUCCESS;
    }
}
