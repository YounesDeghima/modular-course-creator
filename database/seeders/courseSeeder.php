<?php

namespace Database\Seeders;

use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class courseSeeder extends Seeder
{
    public function run(): void
    {
        course::factory()->count(10)
        ->has(chapter::factory()
        ->count(10)
        ->sequence(fn ($sequence) =>['chapter_number'=>$sequence->index +1])
            ->has(lesson::factory()
            ->count(10)
            ->sequence(fn($sequence)=>['lesson_number'=>$sequence->index +1])
            )
        )->create();
    }
}
