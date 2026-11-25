<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_unique(array_merge(...array_values(config('permissions')))) as $name) {
            Resource::firstOrCreate(['name' => $name]);
        }
    }
}
