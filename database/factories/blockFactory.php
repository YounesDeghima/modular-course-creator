<?php

namespace Database\Factories;

use App\Models\lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\block>
 */
class blockFactory extends Factory
{
    public function definition(): array
    {
        $lesson=lesson::inRandomOrder()->first();
        $lesson_id = $lesson->id;
        $block_count = $lesson->blocks()->count();
        return [
            'lesson_id'=> $lesson_id,
            'title'=>$this->faker->sentence(),
            'content'=>$this->faker->paragraph(2),
            'type'=>$this->faker->randomElement(['header','description','note','code','exercise']),
            'block_number'=>$block_count+1,

        ];
    }
}
