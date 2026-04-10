<?php

namespace Database\Factories;

use App\Models\coursequestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\questionchoice>
 */
class questionchoiceFactory extends Factory
{

    public function definition(): array
    {
        $question = coursequestion::inRandomOrder()->first();

        return [
            'content'=> $this->faker->sentence(),
            'coursequestion_id' => $question->id,
            'value'=>$this->faker->boolean(),
        ];
    }
}
