<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardUserController extends Controller
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

        return view('dashboard.users.index', [
            'title' => 'User',
            'roles' => Role::all(),
            'users' => User::with('role')->latest()->paginate(10),
            'security' => User::with('role')->where('role_id', 2)->get(),
        ]);
    }

    /**
     * Build DataTables JSON for users
     */
    private function getDataTableData()
    {
        $query = User::with('role')->orderBy('id', 'desc');

        $users = $query->get();

        $data = [];
        foreach ($users as $user) {
            $statusBadge = $user->status === 'active'
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-danger">Inactive</span>';

            $actions = '';
            if (auth()->user()->role_id == 1) {
                $toggleIcon = $user->status === 'active'
                    ? '<i class="bi bi-toggle-on text-success" title="Deaktifkan User"></i>'
                    : '<i class="bi bi-toggle-off text-secondary" title="Aktifkan User"></i>';
                $actions =
                    '<button type="button" class="btn btn-warning btn-sm me-1" data-bs-toggle="modal" data-bs-target="#edituser" onclick="editUser(' . $user->id . ')" title="Edit User">' .
                    '<i class="bi bi-pencil-fill"></i> Edit' .
                    '</button>&nbsp;' .
                    '<a href="/dashboard/users/' . $user->id . '/toggleStatus" class="btn btn-info btn-sm me-1" onclick="return confirm(\'Ubah status user ' . e($user->name) . '?\')" title="Toggle Status">' .
                    $toggleIcon .
                    '</a>&nbsp;' .
                    '<form action="/dashboard/users/' . $user->id . '" method="post" class="d-inline">'
                    . '<input type="hidden" name="_method" value="DELETE">'
                    . '<input type="hidden" name="_token" value="' . csrf_token() . '">'
                    . '<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Hapus data user?\')" title="Hapus User">' .
                    '<i class="bi bi-trash-fill"></i> Hapus' .
                    '</button>' .
                    '</form>';
            }

            $data[] = [
                'DT_RowId' => 'row_' . $user->id,
                'id' => $user->id,
                'name' => e($user->name),
                'email' => e($user->email),
                'organization' => e($user->organization ?? '-'),
                'phone' => e($user->phone ?? '-'),
                'address' => e($user->address ?? '-'),
                'role' => e(optional($user->role)->name ?? '-'),
                'status_badge' => $statusBadge,
                'actions' => $actions,
            ];
        }

        // Search
        $search = request()->input('search.value');
        if ($search) {
            $data = array_values(array_filter($data, function ($row) use ($search) {
                return stripos($row['name'], $search) !== false
                    || stripos($row['email'], $search) !== false
                    || stripos($row['organization'], $search) !== false
                    || stripos($row['phone'], $search) !== false
                    || stripos($row['address'], $search) !== false
                    || stripos($row['role'], $search) !== false;
            }));
        }

        // Ordering: default by name asc when requested
        $orderColumnIndex = (int) request()->input('order.0.column', 1);
        $orderDir = request()->input('order.0.dir', 'asc');
        $columns = ['id', 'name', 'email', 'organization', 'phone', 'address', 'role', 'status_badge'];
        if (isset($columns[$orderColumnIndex])) {
            $columnKey = $columns[$orderColumnIndex];
            usort($data, function ($a, $b) use ($columnKey, $orderDir) {
                $va = strip_tags($a[$columnKey]);
                $vb = strip_tags($b[$columnKey]);
                if ($orderDir === 'asc') {
                    return strcmp($va, $vb);
                }
                return strcmp($vb, $va);
            });
        }

        // Pagination
        $start = (int) request()->input('start', 0);
        $length = (int) request()->input('length', 10);
        $totalRecords = count($data);
        $pagedData = array_slice($data, $start, $length);

        return response()->json([
            'draw' => (int) request()->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $pagedData,
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
        // Only allow admins to add users
        if (auth()->user()->role_id != 1) {
            abort(403, 'Unauthorized action. Hanya admin yang dapat menambahkan user.');
        }

        $validatedData = $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|email:dns|unique:users',
            'organization' => 'nullable|max:255',
            'phone' => 'nullable|max:15',
            'address' => 'nullable|max:500',
            'password' => 'required|min:4',
            'role_id' => 'required',
            'status' => 'required|in:active,inactive'
        ], [
            'email.unique' => 'Email ini sudah terdaftar dalam sistem. Silakan gunakan email lain.',
            'email.email' => 'Format email tidak valid. Silakan masukkan email yang benar.',
            'email.required' => 'Email wajib diisi.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'organization.max' => 'Organisasi/Instansi maksimal 255 karakter.',
            'phone.max' => 'Nomor telepon maksimal 15 karakter.',
            'address.max' => 'Alamat maksimal 500 karakter.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 4 karakter.',
            'role_id.required' => 'Role wajib dipilih.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus active atau inactive.'
        ]);

        $validatedData['password'] = bcrypt($validatedData['password']);

        User::create($validatedData);

        return redirect('/dashboard/users')->with('userSuccess', 'Data user berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        // Only allow admins to edit users
        if (auth()->user()->role_id != 1) {
            abort(403, 'Unauthorized action. Hanya admin yang dapat mengedit user.');
        }
        
        try {
            // Debug: Log the incoming request
            Log::info('Edit method called', [
                'requested_user_id' => $user->id,
                'requested_user_name' => $user->name,
                'requested_user_email' => $user->email,
                'request_url' => request()->url(),
                'request_method' => request()->method(),
                'route_parameters' => request()->route()->parameters(),
                'authenticated_user_id' => auth()->user()->id,
                'authenticated_user_email' => auth()->user()->email
            ]);
            
            // Load user with role relationship to prevent N+1 queries
            $userWithRole = User::with('role')->find($user->id);
            
            if (!$userWithRole) {
                Log::error('User not found in edit method', ['requested_id' => $user->id]);
                return response()->json(['error' => 'User tidak ditemukan'], 404);
            }
            
            // Debug: Log user data being sent
            Log::info('User edit data loaded', [
                'user_id' => $userWithRole->id,
                'user_name' => $userWithRole->name,
                'user_email' => $userWithRole->email,
                'user_role' => $userWithRole->role ? $userWithRole->role->name : 'No role',
                'user_data' => $userWithRole->toArray()
            ]);
            
            // Double-check: Verify we're returning the correct user
            $routeUser = request()->route('user');
            Log::info('Final verification before response', [
                'returning_user_id' => $userWithRole->id,
                'returning_user_name' => $userWithRole->name,
                'returning_user_email' => $userWithRole->email,
                'is_correct_user' => $routeUser ? $userWithRole->id == $routeUser->id : 'route_user_null'
            ]);
            
            return response()->json($userWithRole);
        } catch (\Exception $e) {
            Log::error('Error loading user for edit: ' . $e->getMessage(), [
                'requested_user_id' => $user ? $user->id : 'unknown',
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Terjadi kesalahan saat memuat data user'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        // Only allow admins to update users
        if (auth()->user()->role_id != 1) {
            abort(403, 'Unauthorized action. Hanya admin yang dapat mengupdate user.');
        }

        // Debug: Uncomment to see request data
        // dd($request->all(), $user);
        
        // Log the incoming request for debugging
        Log::info('User update request received', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'request_method' => $request->method(),
            'request_data' => $request->all(),
            'user_email' => $user->email
        ]);
        
        // Temporary debugging for Martinus user
        if ($user->name === 'Martinus') {
            Log::info('Martinus user update attempt', [
                'user_id' => $user->id,
                'current_email' => $user->email,
                'request_email' => $request->input('email'),
                'email_changed' => $request->input('email') != $user->email,
                'request_all' => $request->all(),
                'request_has_file' => $request->hasFile('any'),
                'request_content_type' => $request->header('Content-Type')
            ]);
        }
        
        $rules = [
            'name' => 'required|max:100',
            'email' => 'nullable|email:dns',
            'organization' => 'nullable|max:255',
            'phone' => 'nullable|max:15',
            'address' => 'nullable|max:500',
            'role_id' => 'required',
            'status' => 'required|in:active,inactive'
        ];

        // Check if email is provided and actually changed (case-insensitive comparison)
        $emailProvided = $request->filled('email');
        $emailChanged = $emailProvided && strtolower(trim($request->email)) !== strtolower(trim($user->email));
        
        if ($emailProvided && $emailChanged) {
            $rules['email'] = [
                'email:dns',
                function ($attribute, $value, $fail) use ($user) {
                    // Case-insensitive unique check
                    $existingUser = User::whereRaw('LOWER(email) = ?', [strtolower(trim($value))])
                        ->where('id', '!=', $user->id)
                        ->first();
                    
                    if ($existingUser) {
                        $fail('Email ini sudah terdaftar dalam sistem. Silakan gunakan email lain.');
                    }
                }
            ];
            Log::info('Email validation rule added for unique check', [
                'user_id' => $user->id,
                'old_email' => $user->email,
                'new_email' => $request->email,
                'old_email_lower' => strtolower(trim($user->email)),
                'new_email_lower' => strtolower(trim($request->email))
            ]);
        } else if ($request->filled('email') && !$emailChanged) {
            Log::info('Email provided but unchanged', [
                'user_id' => $user->id,
                'email' => $user->email,
                'request_email' => $request->email
            ]);
        } else {
            Log::info('No email provided, keeping existing email', [
                'user_id' => $user->id,
                'current_email' => $user->email
            ]);
        }

        // Add password validation if password is provided
        if ($request->filled('password')) {
            $rules['password'] = 'required|min:4|confirmed';
            $rules['password_confirmation'] = 'required';
        }

        $validatedData = $request->validate($rules, [
            'email.unique' => 'Email ini sudah terdaftar dalam sistem. Silakan gunakan email lain.',
            'email.email' => 'Format email tidak valid. Silakan masukkan email yang benar.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'organization.max' => 'Organisasi/Instansi maksimal 255 karakter.',
            'phone.max' => 'Nomor telepon maksimal 15 karakter.',
            'address.max' => 'Alamat maksimal 500 karakter.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 4 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            'role_id.required' => 'Role wajib dipilih.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus active atau inactive.'
        ]);

        try {
            // Debug: Log the validated data
            Log::info('Update user attempt', [
                'user_id' => $user->id,
                'validated_data' => $validatedData,
                'request_data' => $request->all()
            ]);

            // Hash password if provided
            if ($request->filled('password')) {
                $validatedData['password'] = bcrypt($validatedData['password']);
            }
            
            // Remove password confirmation field as it's not needed for update
            unset($validatedData['password_confirmation']);
            
            // If email is not provided, keep the existing email
            if (!$request->filled('email')) {
                unset($validatedData['email']);
            }

            // Update using model instance for better debugging
            $user->fill($validatedData);
            $updated = $user->save();

            Log::info('User update result', [
                'user_id' => $user->id,
                'save_result' => $updated,
                'final_data' => $validatedData,
                'user_after_save' => $user->toArray()
            ]);

            return redirect('/dashboard/users')->with('userSuccess', 'Data user berhasil diubah');
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data user. Silakan coba lagi.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        // Only allow admins to delete users
        if (auth()->user()->role_id != 1) {
            abort(403, 'Unauthorized action. Hanya admin yang dapat menghapus user.');
        }

        User::destroy($user->id);
        return redirect('/dashboard/users')->with('deleteUser', 'Hapus data user berhasil');
    }

    public function makeSecurity($id)
    {
        $userData = [
            'role_id' => 2,
        ];

        User::where('id', $id)->update($userData);

        return redirect('/dashboard/security')->with('securitySuccess', 'Data security berhasil ditambahkan');
    }

    public function toggleStatus($id)
    {
        // Only allow admins to toggle user status
        if (auth()->user()->role_id != 1) {
            abort(403, 'Unauthorized action. Hanya admin yang dapat mengubah status user.');
        }

        $user = User::findOrFail($id);
        
        // Toggle status
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        
        $user->update(['status' => $newStatus]);

        $statusMessage = $newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        
        return redirect('/dashboard/users')->with('userSuccess', "Status user {$user->name} berhasil {$statusMessage}");
    }
}
