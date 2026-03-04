<?php

namespace App\Http\Controllers;

use App\Models\Rent;
use App\Models\Room;
use App\Models\Item;
use Illuminate\Http\Request;
use App\Exports\RentsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Notifications\RentNotification;
use App\Notifications\AdminRentNotification;

class DashboardRentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            return $this->getDataTableData();
        }
        // Loaner (role_id = 3) can only see their own rentals including pending
        if (auth()->user()->role_id == 3) {
            return view('dashboard.rents.index', [
                'adminRents' => collect(), // Empty collection for loaners
                'userRents' => Rent::where('user_id', auth()->user()->id)->get(), // Include pending rentals
                'title' => "Peminjaman Saya",
                'rooms' => Room::all(),
                'items' => Item::all(),
            ]);
        }
        
        // Admin and Security can see all rentals including pending
        return view('dashboard.rents.index', [
            'adminRents' => Rent::latest()->get(), // Include pending rentals
            'userRents' => Rent::where('user_id', auth()->user()->id)->get(), // Include pending rentals
            'title' => "Peminjaman",
            'rooms' => Room::all(),
            'items' => Item::all(),
        ]);
    }

    /**
     * Export rents to Excel file
     */
    public function exportExcel()
    {
        $filename = 'rents_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new RentsExport(auth()->user()), $filename);
    }

    /**
     * Build DataTables data for rents
     */
    private function getDataTableData()
    {
        $isAdminOrSecurity = auth()->user()->role_id <= 2;
        if (auth()->user()->role_id == 3) {
            $rents = Rent::where('user_id', auth()->user()->id)->latest()->get();
        } else {
            $rents = Rent::latest()->get();
        }

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

            $statusBadge = match ($rent->status) {
                'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
                'dipinjam' => '<span class="badge bg-primary">Dipinjam</span>',
                'selesai' => '<span class="badge bg-success">Selesai</span>',
                'ditolak' => '<span class="badge bg-danger">Ditolak</span>',
                default => '<span class="badge bg-secondary">' . e($rent->status) . '</span>',
            };

            $returnAction = '-';
            if ($isAdminOrSecurity) {
                if ($rent->status === 'dipinjam') {
                    $returnAction = '<a href="/dashboard/rents/' . $rent->id . '/endTransaction" class="btn btn-success" style="padding:2px 10px"><i class="bi bi-check fs-5"></i></a>';
                } else {
                    $returnAction = !is_null($rent->transaction_end) ? e($rent->transaction_end) : '-';
                }
            }

            $actions = '';
            if (auth()->user()->role_id == 1) {
                $actions = '<form action="/dashboard/rents/' . $rent->id . '" method="post" class="d-inline">'
                    . '<input type="hidden" name="_method" value="DELETE">'
                    . '<input type="hidden" name="_token" value="' . csrf_token() . '">'
                    . '<button type="submit" class="bi bi-trash-fill text-danger border-0" onclick="return confirm(\'Hapus data peminjaman?\')"></button>'
                    . '</form>';
            }

            $fullNotesEscaped = $rent->notes ? e($rent->notes) : '';
            $shortNotesEscaped = $rent->notes ? e(\Illuminate\Support\Str::limit($rent->notes, 80)) : '';

            // Calculate penalty display - show real-time penalty for overdue rents
            $penaltyDisplay = '<span class="text-muted">-</span>';
            
            if ($rent->status === 'selesai' && $rent->transaction_end) {
                // For completed rents, use stored penalty or calculate if not set
                $penaltyAmount = $rent->penalty_amount > 0 ? $rent->penalty_amount : $rent->calculatePenalty();
                if ($penaltyAmount > 0) {
                    $penaltyDisplay = '<span class="badge bg-danger">Rp ' . number_format($penaltyAmount, 0, ',', '.') . '</span>';
                }
            } elseif ($rent->status === 'dipinjam' && $rent->isOverdue()) {
                // For active overdue rents, calculate real-time penalty
                $penaltyAmount = $rent->calculateAutoPenalty();
                if ($penaltyAmount > 0) {
                    $penaltyDisplay = '<span class="badge bg-warning text-dark">Rp ' . number_format($penaltyAmount, 0, ',', '.') . ' <small>(terlambat)</small></span>';
                }
            } elseif ($rent->hasPenalty()) {
                // For any other case with stored penalty
                $penaltyDisplay = '<span class="badge bg-danger">Rp ' . number_format($rent->penalty_amount, 0, ',', '.') . '</span>';
            }

            $row = [
                'code' => '<a href="/dashboard/rooms/' . e($rent->room->code) . '" class="text-decoration-none" role="button">' . e($rent->room->code) . '</a>',
                'borrower' => e($rent->user->name),
                'start' => (string) $rent->time_start_use,
                'end' => (string) $rent->time_end_use,
                'purpose' => e($rent->purpose),
                'items' => $itemsList,
                // New column for return time shown to all roles
                'return_time' => $rent->transaction_end ? (string) $rent->transaction_end : '<span class="text-muted">-</span>',
                'penalty' => $penaltyDisplay,
                'status_badge' => $statusBadge,
                'notes' => $rent->notes
                    ? '<span class="text-muted small dt-notes" data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . $fullNotesEscaped . '">' . $shortNotesEscaped . '</span>'
                    : '<span class="text-muted">-</span>',
            ];
            if ($isAdminOrSecurity) {
                $row['return_action'] = $returnAction;
            }
            if (auth()->user()->role_id == 1) {
                $row['actions'] = $actions;
            }
            $data[] = $row;
        }

        // Search filter
        $search = request()->input('search.value');
        if ($search) {
            $data = array_values(array_filter($data, function ($row) use ($search) {
                foreach (['code','borrower','purpose','items','notes'] as $k) {
                    if (isset($row[$k]) && stripos(strip_tags($row[$k]), $search) !== false) return true;
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'room_id' => 'required',
            'time_start_use' => 'required',
            'time_end_use' => 'required',
            'purpose' => 'required|max:250',
            'items' => 'nullable|array',
            'item_quantities' => 'nullable|array',
        ]);

        // Check if room is available for the requested time
        $roomAvailability = $this->checkRoomAvailability(
            $validatedData['room_id'],
            $validatedData['time_start_use'],
            $validatedData['time_end_use']
        );

        if (!$roomAvailability['available']) {
            return redirect()->back()
                ->withErrors(['room_id' => "⚠️ Peminjaman tidak dapat dilakukan! {$roomAvailability['message']}"])
                ->withInput();
        }
        
        $validatedData['user_id'] = auth()->user()->id;
        $validatedData['transaction_start'] = now();
        $validatedData['status'] = 'pending';
        $validatedData['transaction_end'] = null;

        $rent = Rent::create($validatedData);

        // Handle item selection
        if ($request->has('items') && $request->has('item_quantities')) {
            $items = $request->input('items');
            $quantities = $request->input('item_quantities');
            
            foreach ($items as $itemId) {
                if (isset($quantities[$itemId]) && $quantities[$itemId] > 0) {
                    $item = Item::find($itemId);
                    
                    // Check if requested quantity is available
                    if ($quantities[$itemId] > $item->quantity) {
                        return redirect()->back()->withErrors(['items' => "Jumlah {$item->name} yang diminta ({$quantities[$itemId]}) melebihi stok yang tersedia ({$item->quantity})"]);
                    }
                    
                    $rent->items()->attach($itemId, ['quantity' => $quantities[$itemId]]);
                    
                    // Don't reduce available quantity yet - wait for approval
                }
            }
        }

        // Send notification to all admins
        $admins = \App\Models\User::where('role_id', 1)->whereNotNull('phone')->get();
        foreach ($admins as $admin) {
            if ($admin->phone) {
                $admin->notify(new AdminRentNotification($rent));
            }
        }

        return redirect('/dashboard/temporaryRents')->with('rentSuccess', 'Peminjaman berhasil diajukan dan sedang menunggu persetujuan admin.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Rent  $rent
     * @return \Illuminate\Http\Response
     */
    public function show(Rent $rent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Rent  $rent
     * @return \Illuminate\Http\Response
     */
    public function edit(Rent $rent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Rent  $rent
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Rent $rent)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Rent  $rent
     * @return \Illuminate\Http\Response
     */
    public function destroy(Rent $rent)
    {
        // Loaner can only delete their own rentals
        if (auth()->user()->role_id == 3 && $rent->user_id != auth()->user()->id) {
            abort(403, 'Unauthorized action.');
        }
        
        // Return items to inventory if rental is active (dipinjam)
        // For pending rentals, items were never deducted, so just detach them
        if ($rent->status === 'dipinjam') {
            foreach ($rent->items as $item) {
                $item->increment('quantity', $item->pivot->quantity);
            }
        } elseif ($rent->status === 'pending') {
            // For pending rentals, just detach items (they were never deducted)
            $rent->items()->detach();
        }
        
        Rent::destroy($rent->id);
        
        // Redirect based on where the delete was called from
        if ($rent->status === 'pending') {
            return redirect('/dashboard/temporaryRents')->with('deleteRent', 'Data peminjaman sementara berhasil dihapus');
        }
        
        return redirect('/dashboard/rents')->with('deleteRent', 'Data peminjaman berhasil dihapus');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Rent  $rent
     * @return \Illuminate\Http\Response
     */
    public function endTransaction($id)
    {
        // Only admin and security can end transactions
        if (auth()->user()->role_id >= 3) {
            abort(403, 'Unauthorized action. Hanya admin dan security yang dapat mengakhiri transaksi peminjaman.');
        }

        $rent = Rent::findOrFail($id);
        
        // Return items to inventory
        foreach ($rent->items as $item) {
            $item->increment('quantity', $item->pivot->quantity);
        }
        
        // Always calculate penalty for late return (ensure accuracy)
        $penaltyAmount = $rent->calculatePenalty();
        
        // If auto penalty was already calculated, use the higher amount
        if ($rent->hasAutoPenaltyCalculated() && $rent->penalty_amount > $penaltyAmount) {
            $penaltyAmount = $rent->penalty_amount;
        }

        $transaction = [
            'transaction_end' => now(),
            'status' => 'selesai',
            'penalty_amount' => $penaltyAmount,
        ];

        $rent->update($transaction);

        // Send WhatsApp notification to user
        if ($rent->user->phone) {
            $rent->user->notify(new RentNotification($rent, 'completed'));
        }

        return redirect('/dashboard/rents');
    }

    /**
     * Check if a room is available for the specified time period
     *
     * @param int $roomId
     * @param string $startTime
     * @param string $endTime
     * @return array
     */
    private function checkRoomAvailability($roomId, $startTime, $endTime)
    {
        // Convert times to Carbon instances for comparison
        $requestedStart = \Carbon\Carbon::parse($startTime);
        $requestedEnd = \Carbon\Carbon::parse($endTime);

        // Check if end time is after start time
        if ($requestedEnd <= $requestedStart) {
            return [
                'available' => false,
                'message' => '❌ Waktu selesai harus setelah waktu mulai!'
            ];
        }

        // Check for overlapping rentals (only approved rentals block new requests)
        $overlappingRentals = Rent::where('room_id', $roomId)
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
                $conflictingRentals[] = $rental->time_start_use->format('d/m/Y H:i') . ' - ' . $rental->time_end_use->format('H:i');
            }
            
            return [
                'available' => false,
                'message' => '🚫 Ruangan sudah disetujui untuk dipinjam pada waktu: ' . implode(', ', $conflictingRentals)
            ];
        }

        return [
            'available' => true,
            'message' => ''
        ];
    }
}
