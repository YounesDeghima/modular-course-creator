<?php

namespace Database\Factories;

use App\Models\chapter;
use App\Models\course;
use Illuminate\Database\Eloquent\Factories\Factory;


class chapterFactory extends Factory
{

    public function definition(): array
    {
        $course=course::inRandomOrder()->first();
        $course_id = $course->id;
        $chapter_count = $course->chapters()->count();
        return [
            'course_id'=> course::factory(),
            'title'=>$this->faker->sentence(),
            'description'=>$this->faker->paragraph(2),
            'chapter_number'=> null,
            'status'=>$this->faker->randomElement(['draft','published']),
        ];
    }
}
