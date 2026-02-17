<?php

namespace Database\Seeders;

use App\Models\user;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */


    public function run(): void
    {
        $this->call([
            userSeeder::class,
            courseSeeder::class,
            adminSeeder::class,
            chapterSeeder::class,
            lessonSeeder::class,
            blockSeeder::class,
        ]);
    }
}

