<?php

namespace Database\Factories;

use App\Models\CourseEnrollment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class courseenrollmentFactory extends Factory
{
    protected $model = CourseEnrollment::class;

    public function definition(): array
    {
        return [
            'user_id'  => User::factory(),
            'course_id' => Course::factory(),
            'forced'   => false,
        ];
    }

    public function forced(): static
    {
        return $this->state(fn (array $attributes) => [
            'forced' => true,
        ]);
    }
}
