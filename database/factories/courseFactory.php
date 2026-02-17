<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\course;
class courseFactory extends Factory
{
    public function definition(): array
{   $year = $this->faker->numberBetween(1,3);
    return [
        'title' => $this->faker->sentence(),
        'year'=> $year,
        'branch'=>$year <= 1 ? 'none': $this->faker->randomElement(['mi','st']),
        'description'=>$this->faker->paragraph(2),
        'status'=>'draft',

    ];
}
}
