<?php

namespace Database\Seeders;

use App\Models\exercisesolution;
use App\Models\lesson_progress;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LessonProgressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        lesson_progress::factory()->count(200)->create();
    }
}
