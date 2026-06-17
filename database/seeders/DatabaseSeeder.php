<?php

namespace Database\Seeders;

use App\Models\user;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;



    public function run(): void
    {
        $this->call([


            AISeeder::class,
            coursequestionSeeder::class,
            sectionSeeder::class,
            userSeeder::class,
            adminSeeder::class,
//            blockSeeder::class,
//            exercisesolutionSeeder::class,
        ]);
    }
}

