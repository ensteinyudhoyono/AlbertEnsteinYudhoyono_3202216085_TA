<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Room;
use App\Models\Role;
use App\Models\Rent;
use App\Models\Item;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 1) Roles with fixed IDs used by app logic
        DB::table('roles')->truncate();
        DB::table('roles')->insert([
            [ 'id' => 1, 'name' => 'admin',    'created_at' => now(), 'updated_at' => now() ],
            [ 'id' => 2, 'name' => 'security', 'created_at' => now(), 'updated_at' => now() ],
            [ 'id' => 3, 'name' => 'loaner',   'created_at' => now(), 'updated_at' => now() ],
        ]);

        // 2) Baseline users
        $baselineUsers = [
            ['name' => 'Admin',        'email' => 'admin@gmail.com',   'password' => bcrypt('admin'),   'role_id' => 1, 'status' => 'active'],
            ['name' => 'Penjaga',      'email' => 'penjaga@gmail.com', 'password' => bcrypt('penjaga'), 'role_id' => 2, 'status' => 'active'],
        ];
        foreach ($baselineUsers as $data) {
            User::updateOrCreate(['email' => $data['email']], $data);
        }

        // 3) Items (diselaraskan dengan kk.json default)
        Item::updateOrCreate(['name' => 'Kursi'], [
            'quantity' => 15,
            'description' => 'Kursi untuk ruangan',
        ]);
        Item::updateOrCreate(['name' => 'Meja'], [
            'quantity' => 5,
            'description' => 'Meja untuk ruangan',
        ]);

        // 4) Rooms: keep only Aula
        $rooms = [
            ['code' => '3001', 'name' => 'Aula Elisabet', 'img' => 'room-image/roomdefault.jpg', 'floor' => 3, 'status' => false, 'capacity' => 100, 'description' => 'Aula serbaguna'],
        ];
        foreach ($rooms as $data) {
            Room::updateOrCreate(['code' => $data['code']], $data);
        }

        // Ensure only Aula remains
        Room::where(function ($q) {
            $q->where('code', '!=', '3001')
              ->where('name', 'NOT LIKE', '%Aula%');
        })->delete();

        // 6) Example rents (safe, minimal)
        $room = Room::first();
        $user = User::where('role_id', 3)->first();
        if ($room && $user) {
        Rent::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'time_start_use' => now()->addDay()->setTime(9, 0),
                'time_end_use'   => now()->addDay()->setTime(12, 0),
                'purpose' => 'Contoh peminjaman',
            'transaction_start' => now(),
                'transaction_end'   => null,
            'status' => 'pending',
        ]);
        }
    }

    
}
