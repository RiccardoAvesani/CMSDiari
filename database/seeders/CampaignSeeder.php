<?php

namespace Database\Seeders;

use App\Models\Campaign;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        Campaign::query()->updateOrCreate(
            ['year' => '2026'],
            ['description' => 'Campagna Diari 2026', 'status' => Campaign::STATUS_PLANNED],
        );

        Campaign::query()->updateOrCreate(
            ['year' => '2027'],
            ['description' => 'Campagna Diari 2027', 'status' => Campaign::STATUS_PLANNED],
        );
    }
}
