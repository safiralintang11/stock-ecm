<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'monica@stockecm.test'],
            [
                'name' => 'Monica',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'novan@stockecm.test'],
            [
                'name' => 'Novan',
                'password' => Hash::make('password'),
                'role' => 'gudang',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'fan@stockecm.test'],
            [
                'name' => 'Fan',
                'password' => Hash::make('password'),
                'role' => 'gudang',
                'is_active' => true,
            ]
        );
    }
}
