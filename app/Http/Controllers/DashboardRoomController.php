<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Rent;
use App\Models\Item;
use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;

class DashboardRoomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // AJAX for DataTables
        if (request()->ajax()) {
            return $this->getRoomsDataTable();
        }

        // Check if this is the main dashboard or rooms listing
        if (request()->is('dashboard') && !request()->is('dashboard/rooms*')) {
            return view('dashboard.index', [
                'title' => "Dashboard",
                'rooms' => Room::all(),
                'items' => Item::all(),
            ]);
        }
        
        $rooms = Room::latest()->paginate(10);
        
        // Add current rental status to each room (only show approved rentals)
        foreach ($rooms as $room) {
            $currentRental = Rent::where('room_id', $room->id)
                ->where('status', 'dipinjam') // Only show approved rentals
                ->where('time_start_use', '<=', now())
                ->where('time_end_use', '>=', now())
                ->first();
            
            $room->current_rental = $currentRental;
        }
        
        return view('dashboard.rooms.index', [
            'title' => "Ruangan",
            'rooms' => $rooms,
            'items' => Item::all(),
        ]);
    }

    /**
     * Rooms DataTables JSON
     */
    private function getRoomsDataTable()
    {
        $rooms = Room::orderBy('id', 'desc')->get();

        // Attach current rental info
        foreach ($rooms as $room) {
            $currentRental = Rent::where('room_id', $room->id)
                ->where('status', 'dipinjam')
                ->where('time_start_use', '<=', now())
                ->where('time_end_use', '>=', now())
                ->first();
            $room->current_rental = $currentRental;
        }

        $data = [];
        foreach ($rooms as $room) {
            $currentRentalText = '';
            if ($room->current_rental) {
                $currentRentalText = 'Sedang dipinjam oleh ' . e($room->current_rental->user->name)
                    . ' (' . $room->current_rental->time_start_use->format('H:i')
                    . ' - ' . $room->current_rental->time_end_use->format('H:i') . ')';
            }

            $actions = '';
            if (auth()->user()->role_id <= 2) {
                $actions =
                    '<a href="/dashboard/rooms/' . e($room->code) . '/edit" class="bi bi-pencil-square text-warning border-0 editroom" id="editroom" data-id="' . $room->id . '" data-code="' . e($room->code) . '" data-bs-toggle="modal" data-bs-target="#editRoom"></a>' .
                    '&nbsp;'
                    . '<form action="/dashboard/rooms/' . e($room->code) . '" method="post" class="d-inline">'
                    . '<input type="hidden" name="_method" value="DELETE">'
                    . '<input type="hidden" name="_token" value="' . csrf_token() . '">'
                    . '<button type="submit" class="bi bi-trash-fill text-danger border-0" onclick="return confirm(\'Hapus data ruangan?\')"></button>'
                    . '</form>';
            }

            $data[] = [
                'name' => '<a href="/dashboard/rooms/' . e($room->code) . '" class="text-decoration-none" role="button">' . e($room->name) . '</a>'
                    . ($currentRentalText ? '<br><small class="text-danger"><i class="bi bi-clock"></i> ' . $currentRentalText . '</small>' : ''),
                'code' => e($room->code),
                'actions' => $actions,
            ];
        }

        // Search
        $search = request()->input('search.value');
        if ($search) {
            $data = array_values(array_filter($data, function ($row) use ($search) {
                return stripos(strip_tags($row['name']), $search) !== false
                    || stripos($row['code'], $search) !== false;
            }));
        }

        // Ordering
        $orderColumn = (int) request()->input('order.0.column', 0);
        $orderDir = request()->input('order.0.dir', 'asc');
        $cols = ['name', 'code', 'actions'];
        if (isset($cols[$orderColumn])) {
            $col = $cols[$orderColumn];
            usort($data, function ($a, $b) use ($col, $orderDir) {
                $va = strip_tags($a[$col]);
                $vb = strip_tags($b[$col]);
                return $orderDir === 'asc' ? strcmp($va, $vb) : strcmp($vb, $va);
            });
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
        // Only admin and security can add rooms
        if (auth()->user()->role_id > 2) {
            abort(403, 'Unauthorized action.');
        }

        // $request->file('img')->store('room-image');

        $validatedData = $request->validate([
            'code' => 'required|max:4|unique:rooms',
            'name' => 'required',
            'img' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'floor' => 'required',
            'capacity' => 'required',
            'description' => 'required|max:250',
        ], [
            'img.image' => 'File harus berupa gambar.',
            'img.mimes' => 'Format gambar harus JPG, PNG, atau GIF.',
            'img.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        if ($request->file('img')) {
            $validatedData['img'] = $this->handleImageUpload($request->file('img'));
        } else {
            $validatedData['img'] = "room-image/roomdefault.jpg";
        }

        $validatedData['status'] = false;

        Room::create($validatedData);

        return redirect('/dashboard/rooms')->with('roomSuccess', 'Data ruangan berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function show(Room $room)
    {
        return view('dashboard.rooms.show', [
            'title' => $room->name,
            'room' => $room,
            'rooms' => Room::all(),
            'rents' => Rent::where('room_id', $room->id)->get(),
            'items' => Item::all(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function edit(Room $room)
    {
        return json_encode($room);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Room $room)
    {
        // Only admin and security can edit rooms
        if (auth()->user()->role_id > 2) {
            abort(403, 'Unauthorized action.');
        }

        // return $request;
        $rules = [
            'name' => 'required',
            'img' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'floor' => 'required',
            'capacity' => 'required',
            'description' => 'required|max:250',
        ];
        
        $messages = [
            'img.image' => 'File harus berupa gambar.',
            'img.mimes' => 'Format gambar harus JPG, PNG, atau GIF.',
            'img.max' => 'Ukuran gambar maksimal 2MB.',
        ];

        if ($request->code != $room->code) {
            $rules['code'] = 'required|max:4|unique:rooms';
        }

        $validatedData = $request->validate($rules, $messages);

        if ($request->file('img')) {
            $validatedData['img'] = $this->handleImageUpload($request->file('img'), $room->img);
        } else {
            // Don't overwrite existing image if no new image is uploaded
            unset($validatedData['img']);
        }

        $validatedData['status'] = false;

        Room::where('id', $room->id)
            ->update($validatedData);

        return redirect('/dashboard/rooms')->with('roomSuccess', 'Data ruangan berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function destroy(Room $room)
    {
        // Only admin and security can delete rooms
        if (auth()->user()->role_id > 2) {
            abort(403, 'Unauthorized action.');
        }
        
        // Delete associated image if not default
        if ($room->img && $room->img !== 'room-image/roomdefault.jpg' && Storage::disk('public')->exists($room->img)) {
            Storage::disk('public')->delete($room->img);
        }
        
        Room::destroy($room->id);
        return redirect('/dashboard/rooms')->with('deleteRoom', 'Data ruangan berhasil dihapus');
    }

    /**
     * Handle image upload and processing with Intervention Image
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param string|null $oldImagePath
     * @return string
     */
    private function handleImageUpload($image, $oldImagePath = null)
    {
        // Delete old image if exists (but keep default image)
        if ($oldImagePath && $oldImagePath !== 'room-image/roomdefault.jpg' && Storage::disk('public')->exists($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        // Generate unique filename
        $filename = 'room_' . time() . '_' . uniqid() . '.jpg';
        $path = 'room-image/' . $filename;

        // Process image with Intervention Image
        $processedImage = Image::read($image)
            ->resize(800, 600) // Intervention Image v3 maintains aspect ratio by default
            ->toJpeg(85); // Convert to JPG with 85% quality

        // Store the processed image
        Storage::disk('public')->put($path, $processedImage);

        return $path;
    }
}
