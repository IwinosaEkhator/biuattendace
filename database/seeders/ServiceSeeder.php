<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Campus;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure campuses already exist (see CampusSeeder below)
        $campusIds = Campus::pluck('id');
        if ($campusIds->isEmpty()) {
            $this->command->warn('No campuses found. Seed campuses first.');
            return;
        }

        $services = [
            ['slug' => 'sunday', 'name' => 'Sunday Service'],
            ['slug' => 'chapel', 'name' => 'Chapel Service'],
            ['slug' => 'cfi',    'name' => 'CFI Service'],
            ['slug' => 'cell',   'name' => 'Cell Meetings'],
        ];

        $now  = now();
        $rows = [];

        foreach ($campusIds as $campusId) {
            foreach ($services as $s) {
                $rows[] = [
                    'campus_id'  => $campusId,
                    'slug'       => $s['slug'],
                    'name'       => $s['name'],
                    'active'     => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Unique by (campus_id, slug), update these columns on conflict
        // Requires a DB unique index on (campus_id, slug)
        DB::transaction(function () use ($rows) {
            Service::upsert(
                $rows,
                ['campus_id', 'slug'],
                ['name', 'active', 'updated_at']
            );
        });
    }
}
