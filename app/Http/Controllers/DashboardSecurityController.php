<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DashboardSecurityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.security.index', [
            'title' => "Security",
            'loaners' => User::where('role_id', 3)->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:100',
            'nomor_induk' => 'required|min:7|max:18|unique:users,nomor_induk',
            'email' => 'required|email:dns',
            'password' => 'required|min:4'
        ]);

        $validatedData['role_id'] = 3; // loaner role
        $validatedData['password'] = bcrypt($validatedData['password']);

        User::create($validatedData);

        return redirect('/dashboard/security')->with('securitySuccess', 'Data loaner berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return json_encode($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|max:100',
            'email' => 'required|email:dns',
            'role_id' => 'required'
        ];

        if ($request->nomor_induk != $user->nomor_induk) {
            $rules['nomor_induk'] = 'required|min:7|max:18|unique:users,nomor_induk';
        }

        $validatedData = $request->validate($rules);
        User::where('id', $user->id)
            ->update($validatedData);

        return redirect('/dashboard/security')->with('securitySuccess', 'Data loaner berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        User::destroy($user->id);
        return redirect('/dashboard/security')->with('deleteSecurity', 'Hapus data loaner berhasil');
    }

    public function makeLoaner($id)
    {
        $loanerData = [
            'role_id' => 3
        ];

        User::where('id', $id)->update($loanerData);

        return redirect('/dashboard/security');
    }
}
