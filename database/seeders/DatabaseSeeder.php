<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'nama' => 'Administrator',
            'email' => 'admin@admin.com',
            'role' => 'superadmin',
            'status' => 1,
            'hp' => '081234567891',
            'password' => bcrypt('P@ssword'),
        ]);

        User::create([
            'nama' => 'Sopian Aji',
            'email' => 'sopianaji@admin.com',
            'role' => 1,
            'status' => 1,
            'hp' => '081234567892',
            'password' => bcrypt('P@ssword'),
        ]);
    }
}
