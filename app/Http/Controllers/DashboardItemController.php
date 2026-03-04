<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            return $this->getDataTableData();
        }
        
        return view('dashboard.items.index', [
            'title' => "Item",
        ]);
    }

    /**
     * Get DataTables data via AJAX
     */
    private function getDataTableData(): JsonResponse
    {
        $items = Item::with('rents')->get();
        
        $data = [];
        foreach ($items as $item) {
            // Calculate rented quantity
            $rentedQuantity = $item->rents->where('status', 'dipinjam')->sum('pivot.quantity');
            $availableQuantity = $item->quantity - $rentedQuantity;
            
            // Get current renters
            $currentRenters = $item->rents->where('status', 'dipinjam')->map(function($rent) {
                return $rent->user->name . ' (' . $rent->pivot->quantity . ')';
            })->implode('<br>');
            
            $actions = '';
            if (auth()->user()->role_id <= 2) {
                $actions = '
                    <a href="/dashboard/items/' . $item->id . '/edit" class="btn btn-warning btn-sm mb-1" title="Edit">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    <form action="/dashboard/items/' . $item->id . '" method="post" class="d-inline">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Hapus item ini?\')" title="Hapus">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </form>';
            }
            
            $data[] = [
                'DT_RowId' => 'row_' . $item->id,
                'id' => $item->id,
                'name' => $item->name,
                'available_quantity' => $availableQuantity,
                'rented_quantity' => $rentedQuantity,
                'total_quantity' => $item->quantity,
                'current_renters' => $currentRenters ?: '-',
                'actions' => $actions
            ];
        }
        
        // Handle search
        $search = request()->input('search.value');
        if ($search) {
            $data = array_filter($data, function($item) use ($search) {
                return stripos($item['name'], $search) !== false || 
                       stripos($item['available_quantity'], $search) !== false ||
                       stripos($item['rented_quantity'], $search) !== false ||
                       stripos($item['total_quantity'], $search) !== false ||
                       stripos($item['current_renters'], $search) !== false;
            });
        }
        
        // Handle ordering
        $orderColumn = request()->input('order.0.column', 1);
        $orderDir = request()->input('order.0.dir', 'asc');
        
        if ($orderColumn == 1) { // Name column
            usort($data, function($a, $b) use ($orderDir) {
                return $orderDir === 'asc' ? 
                    strcmp($a['name'], $b['name']) : 
                    strcmp($b['name'], $a['name']);
            });
        } elseif ($orderColumn == 2) { // Available quantity column
            usort($data, function($a, $b) use ($orderDir) {
                return $orderDir === 'asc' ? 
                    $a['available_quantity'] - $b['available_quantity'] : 
                    $b['available_quantity'] - $a['available_quantity'];
            });
        } elseif ($orderColumn == 3) { // Rented quantity column
            usort($data, function($a, $b) use ($orderDir) {
                return $orderDir === 'asc' ? 
                    $a['rented_quantity'] - $b['rented_quantity'] : 
                    $b['rented_quantity'] - $a['rented_quantity'];
            });
        } elseif ($orderColumn == 4) { // Total quantity column
            usort($data, function($a, $b) use ($orderDir) {
                return $orderDir === 'asc' ? 
                    $a['total_quantity'] - $b['total_quantity'] : 
                    $b['total_quantity'] - $a['total_quantity'];
            });
        }
        
        // Handle pagination
        $start = request()->input('start', 0);
        $length = request()->input('length', 10);
        $totalRecords = count($data);
        $data = array_slice($data, $start, $length);
        
        return response()->json([
            'draw' => request()->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.items.create', [
            'title' => "Tambah Item",
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Only admin and security can add items
        if (auth()->user()->role_id > 2) {
            abort(403, 'Unauthorized action.');
        }

        $validatedData = $request->validate([
            'name' => 'required|max:100|unique:items',
            'quantity' => 'required|integer|min:0',
            'description' => 'nullable|max:500',
        ]);

        Item::create($validatedData);

        return redirect('/dashboard/items?refresh=true')->with('itemSuccess', 'Item berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        return view('dashboard.items.show', [
            'title' => $item->name,
            'item' => $item,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item)
    {
        return view('dashboard.items.edit', [
            'title' => "Edit Item",
            'item' => $item,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item)
    {
        // Only admin and security can edit items
        if (auth()->user()->role_id > 2) {
            abort(403, 'Unauthorized action.');
        }

        $validatedData = $request->validate([
            'name' => 'required|max:100|unique:items,name,' . $item->id,
            'quantity' => 'required|integer|min:0',
            'description' => 'nullable|max:500',
        ]);

        $item->update($validatedData);

        return redirect('/dashboard/items?refresh=true')->with('itemSuccess', 'Item berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        // Only admin and security can delete items
        if (auth()->user()->role_id > 2) {
            abort(403, 'Unauthorized action.');
        }

        // Check if item is currently being rented
        if ($item->rents()->where('status', 'dipinjam')->count() > 0) {
            return redirect('/dashboard/items')->with('itemError', 'Item tidak dapat dihapus karena sedang dipinjam');
        }

        $item->delete();

        return redirect('/dashboard/items?refresh=true')->with('deleteItem', 'Item berhasil dihapus');
    }
}
