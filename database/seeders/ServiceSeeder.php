<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::upsert([
            ['slug' => 'sunday', 'name' => 'Sunday Service'],
            ['slug' => 'chapel',  'name' => 'Chapel Service'],
            ['slug' => 'cfi', 'name' => 'CFI Service'],
            ['slug' => 'cell',    'name' => 'Cell Meetings'],
        ], ['slug'], ['name']);
    }
}
