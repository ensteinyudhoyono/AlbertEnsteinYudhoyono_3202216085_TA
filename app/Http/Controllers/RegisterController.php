<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function index()
    {
        return view('register.index', [
            'title' => 'Register',
            'active' => 'register'
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email:dns|unique:users',
            'organization' => 'nullable|max:255',
            'phone' => 'required|max:15',
            'address' => 'required',
            'password' => 'required|min:5|max:255',
            'password_confirmation' => 'required|same:password'
        ], [
            'email.unique' => 'Email ini sudah terdaftar dalam sistem. Silakan gunakan email lain atau hubungi admin untuk bantuan.',
            'email.email' => 'Format email tidak valid. Silakan masukkan email yang benar.',
            'email.required' => 'Email wajib diisi.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'organization.max' => 'Organisasi/Instansi maksimal 255 karakter.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 5 karakter.',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            'password_confirmation.same' => 'Konfirmasi password tidak cocok dengan password.'
        ]);

        // Hash password
        $validatedData['password'] = Hash::make($validatedData['password']);
        
        // Set role_id = 3 (user) dan status = inactive
        $validatedData['role_id'] = 3;
        $validatedData['status'] = 'inactive';

        User::create($validatedData);

        return redirect('/login')->with('success', 'Registrasi berhasil! Akun Anda sedang menunggu aktivasi dari admin.');
    }

    public function checkEmail(Request $request)
    {
        $email = $request->email;
        $userId = $request->user_id; // For editing users
        
        $query = User::where('email', $email);
        
        // If editing a user, exclude the current user from the check
        if ($userId) {
            $query->where('id', '!=', $userId);
        }
        
        $exists = $query->exists();
        
        return response()->json(['exists' => $exists]);
    }
}
