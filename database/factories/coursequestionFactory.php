<?php

namespace Database\Factories;

use App\Models\course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\coursequestion>
 */
class coursequestionFactory extends Factory
{

    public function definition(): array
    {
        $course = course::inRandomOrder()->first();
        return [
            'content'=>$this->faker->sentence(),
            'course_id'=>$course->id,
            'question_number'=>$this->faker->randomDigit(),
        ];
    }
}
