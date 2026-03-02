<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Order;
use App\Models\School;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $campaign = Campaign::query()->firstOrCreate(
            ['year' => '2026'],
            ['description' => 'Campagna 2026', 'status' => Campaign::STATUS_PLANNED],
        );

        $schools = School::query()
            ->whereBetween('id', [3, 7])
            ->get();

        $quantities = [500, 100, 3500, 250, 1000];

        foreach ($schools as $i => $school) {
            $quantity = $quantities[$i] ?? 1;

            Order::query()->updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'school_id' => $school->id,
                ],
                [
                    'status' => Order::STATUS_NEW,
                    'quantity' => $quantity,
                    'external_id' => null,
                    'template_id' => null,
                    'deadline_collection' => null,
                    'deadline_annotation' => null,
                ],
            );
        }
    }
}
