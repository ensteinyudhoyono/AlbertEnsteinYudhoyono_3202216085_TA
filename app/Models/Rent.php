<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'time_start_use' => 'datetime',
        'time_end_use' => 'datetime',
        'transaction_start' => 'datetime',
        'transaction_end' => 'datetime',
        'penalty_amount' => 'decimal:2',
        'auto_penalty_calculated' => 'boolean',
        'auto_penalty_calculated_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'rent_items')->withPivot('quantity');
    }

    /**
     * Calculate penalty amount for late return
     * Penalty is Rp5,000 per hour for late returns
     *
     * @return float
     */
    public function calculatePenalty()
    {
        if (!$this->transaction_end) {
            return 0;
        }

        $endTime = $this->time_end_use;
        $actualReturnTime = $this->transaction_end;

        // If returned on time or early, no penalty
        if ($actualReturnTime <= $endTime) {
            return 0;
        }

        // Calculate hours late (rounded up)
        $hoursLate = ceil($actualReturnTime->diffInMinutes($endTime) / 60);
        
        // Penalty: Rp5,000 per hour
        return $hoursLate * 5000;
    }

    /**
     * Check if the rent has a penalty
     *
     * @return bool
     */
    public function hasPenalty()
    {
        return $this->penalty_amount > 0;
    }

    /**
     * Get formatted penalty amount
     *
     * @return string
     */
    public function getFormattedPenaltyAmount()
    {
        return 'Rp ' . number_format((float) $this->penalty_amount, 0, ',', '.');
    }

    /**
     * Get penalty display for table - shows real-time penalty for overdue rents
     *
     * @return string
     */
    public function getPenaltyDisplay()
    {
        // For completed rents, use stored penalty or calculate if not set
        if ($this->status === 'selesai' && $this->transaction_end) {
            $penaltyAmount = $this->penalty_amount > 0 ? $this->penalty_amount : $this->calculatePenalty();
            if ($penaltyAmount > 0) {
                return '<span class="badge bg-danger">Rp ' . number_format($penaltyAmount, 0, ',', '.') . '</span>';
            }
        } 
        // For active overdue rents, calculate real-time penalty
        elseif ($this->status === 'dipinjam' && $this->isOverdue()) {
            $penaltyAmount = $this->calculateAutoPenalty();
            if ($penaltyAmount > 0) {
                return '<span class="badge bg-warning text-dark">Rp ' . number_format($penaltyAmount, 0, ',', '.') . ' <small>(terlambat)</small></span>';
            }
        } 
        // For any other case with stored penalty
        elseif ($this->hasPenalty()) {
            return '<span class="badge bg-danger">Rp ' . number_format($this->penalty_amount, 0, ',', '.') . '</span>';
        }

        return '<span class="text-muted">-</span>';
    }

    /**
     * Get hours late for penalty calculation
     *
     * @return int
     */
    public function getHoursLate()
    {
        if (!$this->transaction_end) {
            return 0;
        }

        $endTime = $this->time_end_use;
        $actualReturnTime = $this->transaction_end;

        if ($actualReturnTime <= $endTime) {
            return 0;
        }

        return ceil($actualReturnTime->diffInMinutes($endTime) / 60);
    }

    /**
     * Calculate penalty amount for overdue rent (based on current time vs end time)
     * This is used for automatic penalty calculation when time_end_use has passed
     *
     * @return float
     */
    public function calculateAutoPenalty()
    {
        $endTime = $this->time_end_use;
        $currentTime = now();

        // If current time is before or equal to end time, no penalty
        if ($currentTime <= $endTime) {
            return 0;
        }

        // Calculate hours overdue (rounded up)
        $hoursOverdue = ceil($currentTime->diffInMinutes($endTime) / 60);
        
        // Penalty: Rp5,000 per hour
        return $hoursOverdue * 5000;
    }

    /**
     * Get hours overdue for automatic penalty calculation
     *
     * @return int
     */
    public function getHoursOverdue()
    {
        $endTime = $this->time_end_use;
        $currentTime = now();

        if ($currentTime <= $endTime) {
            return 0;
        }

        return ceil($currentTime->diffInMinutes($endTime) / 60);
    }

    /**
     * Check if the rent is overdue (time_end_use has passed)
     *
     * @return bool
     */
    public function isOverdue()
    {
        return now() > $this->time_end_use;
    }

    /**
     * Check if auto penalty has been calculated
     *
     * @return bool
     */
    public function hasAutoPenaltyCalculated()
    {
        return $this->auto_penalty_calculated;
    }

    /**
     * Mark auto penalty as calculated
     */
    public function markAutoPenaltyCalculated()
    {
        $this->update([
            'auto_penalty_calculated' => true,
            'auto_penalty_calculated_at' => now(),
        ]);
    }
}
