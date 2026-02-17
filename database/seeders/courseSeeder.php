<?php

namespace Database\Seeders;

use App\Models\course;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class courseSeeder extends Seeder
{
    public function run(): void
    {
        course::factory()->count(10)->create();
    }
}
