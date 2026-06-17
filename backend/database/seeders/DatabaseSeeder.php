<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlansSeeder::class,
        ]);

        // Konto administratora (demo)
        $admin = User::create([
            'name'     => 'Administrator',
            'email'    => 'admin@itchat.local',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
            'language' => 'pl',
            'currency' => 'PLN',
            'email_verified_at' => now(),
        ]);
        Wallet::create([
            'user_id'  => $admin->id,
            'balance'  => 0,
            'currency' => 'PLN',
        ]);
    }
}
