<?php

namespace Database\Seeders;

use App\Models\PointsSetting;
use Illuminate\Database\Seeder;

class PointsSettingSeeder extends Seeder
{
    public function run(): void
    {
        PointsSetting::getOrCreate();
    }
}
