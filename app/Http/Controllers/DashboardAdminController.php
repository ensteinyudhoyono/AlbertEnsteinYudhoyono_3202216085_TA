<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DashboardAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard.admin.index', [
            'title' => "Admin",
            'security' => User::where('role_id', 2)->get(),
            'loaners' => User::where('role_id', 3)->get(),
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
            'name' => 'required|max:100',
            'nomor_induk' => 'required|min:7|max:18|unique:users,nomor_induk',
            'email' => 'required|email:dns',
            'password' => 'required|min:4'
        ]);

        $validatedData['role_id'] = 2; // security role

        $validatedData['password'] = bcrypt($validatedData['password']);

        User::create($validatedData);

        return redirect('/dashboard/admin')->with('adminSuccess', 'Data security berhasil ditambahkan');
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
        return json_encode($user);
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

        return redirect('/dashboard/admin')->with('adminSuccess', 'Data security berhasil diubah');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        User::destroy($user->id);
        return redirect('/dashboard/admin')->with('deleteAdmin', 'Hapus data security berhasil');
    }

    public function removeSecurity($id)
    {
        $securityData = [
            'role_id' => 3 // downgrade to loaner
        ];

        User::where('id', $id)->update($securityData);

        return redirect('/dashboard/admin');
    }
}