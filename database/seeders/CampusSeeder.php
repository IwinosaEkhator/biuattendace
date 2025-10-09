<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campus;

class CampusSeeder extends Seeder
{
    public function run(): void
    {
        Campus::upsert([
            ['code' => 'LEGACY',  'name' => 'Legacy Campus'],
            ['code' => 'HERITAGE', 'name' => 'Heritage Campus'],
        ], ['code'], ['name']);
    }
}
