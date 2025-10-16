<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['username' => 'admin'], [
            'mat_no' => 'ADMIN/000',
            'password' => Hash::make('ChangeMe123'),
            'user_type' => 'admin',
        ]);
    }
}
