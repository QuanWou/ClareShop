<?php

namespace Database\Seeders;

use App\Modules\Content\Models\SiteContent;
use App\Modules\Content\Support\SiteContentRegistry;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (app(SiteContentRegistry::class)->definitions() as $key => $definition) {
            SiteContent::query()->firstOrCreate(
                ['key' => $key],
                [
                    'type' => $definition['type'],
                    'value' => $definition['default'] ?? null,
                ],
            );
        }
    }
}
