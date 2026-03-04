<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rent;
use App\Models\Room;
use App\Models\Item;
use App\Notifications\RentNotification;

class TemporaryRentController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return $this->getDataTableData();
        }
        // Loaner can only see their own pending rentals
        if (auth()->user()->role_id == 3) {
            return view('dashboard.temporaryRents.index', [
                'title' => "Peminjaman Sementara Saya",
                'rents' => Rent::where('status', 'pending')->where('user_id', auth()->user()->id)->get(),
            ]);
        }
        
        // Admin and Security can see all pending rentals
        return view('dashboard.temporaryRents.index', [
            'title' => "Peminjaman Sementara",
            'rents' => Rent::where('status', 'pending')->get(),
        ]);
    }

    private function getDataTableData()
    {
        if (auth()->user()->role_id == 3) {
            $rents = Rent::where('status', 'pending')->where('user_id', auth()->user()->id)->latest()->get();
        } else {
            $rents = Rent::where('status', 'pending')->latest()->get();
        }

        $isAdmin = auth()->user()->role_id == 1;
        $data = [];
        foreach ($rents as $rent) {
            $itemsList = '-';
            if ($rent->items->count() > 0) {
                $chunks = [];
                foreach ($rent->items as $item) {
                    $chunks[] = e($item->name) . ' (' . $item->pivot->quantity . ')';
                }
                $itemsList = implode('<br>', $chunks);
            }

            $actions = '';
            if ($isAdmin) {
                // Open per-row modal so admin can input approved quantities
                $actions = '<button type="button" class="btn btn-success mb-2" style="padding: 2px 10px" data-bs-toggle="modal" data-bs-target="#acceptModal' . $rent->id . '"><i class="bi bi-check-lg"></i></button>'
                    . ' '
                    . '<button type="button" class="btn btn-danger mb-2" style="padding: 2px 10px" data-bs-toggle="modal" data-bs-target="#declineModal' . $rent->id . '"><i class="bi bi-x-lg"></i></button>';
            } elseif (auth()->user()->role_id == 2) {
                $actions = '<span class="text-muted">Hanya admin yang dapat menyetujui/menolak</span>';
            } else {
                $actions = '<span class="text-muted">Menunggu persetujuan admin</span>';
            }

            $data[] = [
                'room_code' => '<a href="/dashboard/rooms/' . e($rent->room->code) . '" class="text-decoration-none">' . e($rent->room->code) . '</a>',
                'user_name' => e($rent->user->name),
                'start' => (string) $rent->time_start_use,
                'end' => (string) $rent->time_end_use,
                'purpose' => e($rent->purpose),
                'items' => $itemsList,
                'transaction_start' => (string) $rent->transaction_start,
                'status' => e($rent->status),
                'notes' => $rent->notes ? '<span class="text-muted small">' . e(\Illuminate\Support\Str::limit($rent->notes, 50)) . '</span>' : '<span class="text-muted">-</span>',
                'actions' => $actions,
                'id' => $rent->id,
            ];
        }

        // Search
        $search = request()->input('search.value');
        if ($search) {
            $data = array_values(array_filter($data, function ($row) use ($search) {
                foreach (['room_code','user_name','purpose','items','notes'] as $k) {
                    if (stripos(strip_tags($row[$k]), $search) !== false) return true;
                }
                return false;
            }));
        }

        // Pagination
        $start = (int) request()->input('start', 0);
        $length = (int) request()->input('length', 10);
        $total = count($data);
        $paged = array_slice($data, $start, $length);

        return response()->json([
            'draw' => (int) request()->input('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $paged,
        ]);
    }

    public function acceptRents($id)
    {
        // Only admin can accept rentals
        if (auth()->user()->role_id != 1) {
            abort(403, 'Unauthorized action. Hanya admin yang dapat menyetujui peminjaman.');
        }

        $rent = Rent::findOrFail($id);
        
        // Check if room is still available for this rental
        $roomAvailability = $this->checkRoomAvailabilityForApproval($rent);
        
        if (!$roomAvailability['available']) {
            return redirect('/dashboard/temporaryRents')
                ->with('rentError', "Tidak dapat menyetujui peminjaman. {$roomAvailability['message']}");
        }

        // Handle approved quantities per item (admin may adjust)
        $approvedQuantities = request('approved_quantities', []);

        foreach ($rent->items as $item) {
            $requestedQty = (int) $item->pivot->quantity;
            $approvedQty = isset($approvedQuantities[$item->id])
                ? max(0, (int) $approvedQuantities[$item->id])
                : $requestedQty;

            // Validate available stock (since stock not deducted yet, use current quantity)
            if ($approvedQty > $item->quantity) {
                return redirect('/dashboard/temporaryRents')
                    ->with('rentError', "Jumlah {$item->name} yang disetujui ({$approvedQty}) melebihi stok tersedia ({$item->quantity}).");
            }

            // Update pivot quantity to approved, then deduct inventory accordingly
            if ($approvedQty !== $requestedQty) {
                $rent->items()->updateExistingPivot($item->id, ['quantity' => $approvedQty]);
            }
            if ($approvedQty > 0) {
                $item->decrement('quantity', $approvedQty);
            }
        }
        
        $rentStatus = [
            'status' => 'dipinjam',
            'notes' => request('notes') ? 'Disetujui oleh ' . auth()->user()->name . ': ' . request('notes') : 'Disetujui oleh ' . auth()->user()->name,
        ];

        $rent->update($rentStatus);

        // Send WhatsApp notification to user
        if ($rent->user->phone) {
            $rent->user->notify(new RentNotification($rent, 'approved'));
        }

        return redirect('/dashboard/temporaryRents')->with('rentSuccess', 'Peminjaman berhasil disetujui');
    }

    public function declineRents($id)
    {
        // Only admin can decline rentals
        if (auth()->user()->role_id != 1) {
            abort(403, 'Unauthorized action. Hanya admin yang dapat menolak peminjaman.');
        }

        $rent = Rent::findOrFail($id);
        
        // Remove items from rental when declined (no need to return to inventory since they weren't deducted)
        $rent->items()->detach();
        
        $rentStatus = [
            'status' => 'ditolak',
            'notes' => request('notes') ? 'Ditolak oleh ' . auth()->user()->name . ': ' . request('notes') : 'Ditolak oleh ' . auth()->user()->name,
        ];

        $rent->update($rentStatus);

        // Send WhatsApp notification to user
        if ($rent->user->phone) {
            $rent->user->notify(new RentNotification($rent, 'rejected'));
        }

        return redirect('/dashboard/temporaryRents');
    }

    /**
     * Check if a room is available for approval (excluding the current rental)
     *
     * @param Rent $rent
     * @return array
     */
    private function checkRoomAvailabilityForApproval($rent)
    {
        $requestedStart = \Carbon\Carbon::parse($rent->time_start_use);
        $requestedEnd = \Carbon\Carbon::parse($rent->time_end_use);

        // Check for overlapping rentals (only approved rentals block approval)
        $overlappingRentals = Rent::where('room_id', $rent->room_id)
            ->where('id', '!=', $rent->id) // Exclude current rental
            ->where('status', 'dipinjam') // Only check approved rentals
            ->where(function ($query) use ($requestedStart, $requestedEnd) {
                $query->where(function ($q) use ($requestedStart, $requestedEnd) {
                    // Check if existing rental overlaps with requested time
                    $q->where('time_start_use', '<', $requestedEnd)
                      ->where('time_end_use', '>', $requestedStart);
                });
            })
            ->get();

        if ($overlappingRentals->count() > 0) {
            $conflictingRentals = [];
            foreach ($overlappingRentals as $rental) {
                $statusText = $rental->status === 'dipinjam' ? 'Disetujui' : 'Menunggu Persetujuan';
                $conflictingRentals[] = $rental->time_start_use->format('d/m/Y H:i') . ' - ' . $rental->time_end_use->format('H:i') . " ({$statusText})";
            }
            
            return [
                'available' => false,
                'message' => 'Ruangan sudah disetujui untuk dipinjam pada waktu: ' . implode(', ', $conflictingRentals)
            ];
        }

        return [
            'available' => true,
            'message' => ''
        ];
    }
}
