<?php

namespace Database\Factories;

use App\Models\chapter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\lesson>
 */
class lessonFactory extends Factory
{
    public function definition(): array
    {
        $chapter=chapter::inRandomOrder()->first();
        $chapter_id = $chapter->id;
        $lesson_count = $chapter->lessons()->count();
        return [
            'chapter_id'=> $chapter_id,
            'title'=>$this->faker->sentence(),
            'description'=>$this->faker->paragraph(2),
            'lesson_number'=>$lesson_count+1,
            'status'=>$this->faker->randomElement(['draft','published']),
        ];
    }
}
