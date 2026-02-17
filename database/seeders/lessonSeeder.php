<?php

namespace Database\Seeders;

use App\Models\lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class lessonSeeder extends Seeder
{
    public function run(): void
    {
        lesson::factory()->count(10)->create();
    }
}
