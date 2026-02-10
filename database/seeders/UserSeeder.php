<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Superadmin
        User::updateOrCreate(
            ['email' => 'superadmin@sihafalan.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
            ]
        );

        // Admin (Kepala Departemen)
        User::updateOrCreate(
            ['email' => 'admin@sihafalan.test'],
            [
                'name' => 'Admin Departemen',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Musyrif
        User::updateOrCreate(
            ['email' => 'musyrif@sihafalan.test'],
            [
                'name' => 'Ustadz Musyrif',
                'password' => Hash::make('password'),
                'role' => 'musyrif',
            ]
        );

        // Santri
        User::updateOrCreate(
            ['email' => 'santri@sihafalan.test'],
            [
                'name' => 'Santri Contoh',
                'password' => Hash::make('password'),
                'role' => 'santri',
            ]
        );

        // Pimpinan Pondok
        User::updateOrCreate(
            ['email' => 'pimpinan@sihafalan.test'],
            [
                'name' => 'Pimpinan Pondok',
                'password' => Hash::make('password'),
                'role' => 'pimpinan',
            ]
        );
    }
}
