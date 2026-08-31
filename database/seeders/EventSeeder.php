<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $registrationEnd = now()->addDays(30);

        $event = Event::query()->updateOrCreate(
            ['slug' => 'city-fun-run-2026'],
            [
                'name' => 'City Fun Run 2026',
                'description' => 'Event lari tahunan terbuka untuk seluruh kalangan. Pilih kategorimu — 5K, 10K, atau Half Marathon 21K — dan rasakan pengalaman lari paling seru di kota ini. Setiap peserta mendapatkan jersey finisher, medali, race pack, dan e-certificate.',
                'event_date' => now()->addDays(60)->startOfDay(),
                'location' => 'Lapangan Monas, Jakarta Pusat',
                'registration_start' => today(),
                'registration_end' => $registrationEnd,
                'status' => EventStatus::Open,
            ]
        );

        $categories = [
            ['name' => '5K Fun Run', 'code' => '5K', 'distance' => 5, 'price' => 150000, 'quota' => 500],
            ['name' => '10K Run', 'code' => '10K', 'distance' => 10, 'price' => 250000, 'quota' => 450],
            ['name' => 'Half Marathon', 'code' => '21K', 'distance' => 21.0975, 'price' => 375000, 'quota' => 300],
        ];

        foreach ($categories as $category) {
            $event->raceCategories()->updateOrCreate(
                ['event_id' => $event->getKey(), 'code' => $category['code']],
                [...$category, 'status' => 'active']
            );
        }
    }
}
