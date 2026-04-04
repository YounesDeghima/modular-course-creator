<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AISeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/course.json')),
            true
        );


    }
}
