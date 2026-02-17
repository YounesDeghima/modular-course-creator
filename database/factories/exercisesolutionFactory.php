<?php

namespace Database\Factories;

use App\Models\block;
use Illuminate\Database\Eloquent\Factories\Factory;

class exercisesolutionFactory extends Factory
{


    public function definition(): array
    {

        $block = block::inrandomOrder()->where('type','=','exercise')->first();
        $solution_count=$block->solutions()->count();
        return [
            'block_id'=>$block->id,
            'title'=>$this->faker->sentence(),
            'content'=>$this->faker->paragraph(2),
            'solution_number'=>$solution_count+1,
        ];
    }
}
