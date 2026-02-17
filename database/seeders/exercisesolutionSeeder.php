<?php

namespace Database\Seeders;

use App\Models\exercisesolution;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class exercisesolutionSeeder extends Seeder
{

    public function run(): void
    {
        exercisesolution::factory()->count(10)->create();
    }
}
