<?php

namespace Database\Factories;

use App\Models\lesson;
use App\Models\user;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\lesson_progress>
 */
class lesson_progressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lesson=lesson::inRandomOrder()->first();
        $lesson_id = $lesson->id;
        $user = user::inRandomOrder()->first();
        return [
            'lesson_id'=> $lesson_id,
            'user_id'=> $user->id,
            'progress'=> $this->faker->numberBetween(0, 100),
        ];
    }
}
