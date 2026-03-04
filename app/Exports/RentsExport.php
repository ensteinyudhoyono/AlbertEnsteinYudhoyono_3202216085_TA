<?php

namespace App\Exports;

use App\Models\Rent;
use Illuminate\Contracts\Auth\Authenticatable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /** @var Authenticatable|null */
    private $user;

    public function __construct($user = null)
    {
        $this->user = $user;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Rent::with(['room', 'user', 'items'])->orderByDesc('id');

        if ($this->user && method_exists($this->user, 'getAttribute')) {
            // Loaner can only export their own records
            if ((int) $this->user->getAttribute('role_id') === 3) {
                $query->where('user_id', $this->user->getAttribute('id'));
            }
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Kode Ruangan',
            'Nama Ruangan',
            'Nama Peminjam',
            'Mulai Pinjam',
            'Selesai Pinjam',
            'Tujuan',
            'Item yang Dipinjam',
            'Waktu Transaksi',
            'Waktu Pengembalian',
            'Denda Keterlambatan',
            'Status',
            'Catatan',
        ];
    }

    /**
     * @param Rent $rent
     */
    public function map($rent): array
    {
        $items = '-';
        if ($rent->items && $rent->items->count() > 0) {
            $items = $rent->items->map(function ($item) {
                return $item->name . ' (' . $item->pivot->quantity . ')';
            })->implode(', ');
        }

        $start = $rent->time_start_use ? (string) $rent->time_start_use : '';
        $end = $rent->time_end_use ? (string) $rent->time_end_use : '';
        $returnedAt = $rent->transaction_end ? (string) $rent->transaction_end : '-';

        return [
            optional($rent->room)->code,
            optional($rent->room)->name,
            optional($rent->user)->name,
            $start,
            $end,
            (string) $rent->purpose,
            $items,
            $rent->transaction_start ? (string) $rent->transaction_start : '-',
            $returnedAt,
            $rent->hasPenalty() ? 'Rp ' . number_format((float) $rent->penalty_amount, 0, ',', '.') : '-',
            (string) $rent->status,
            $rent->notes ?? '-',
        ];
    }
}

