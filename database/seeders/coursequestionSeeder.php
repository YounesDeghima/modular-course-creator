<?php

namespace Database\Seeders;

use App\Models\course;
use App\Models\coursequestion;
use App\Models\questionchoice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class coursequestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        coursequestion::factory()->count(100)->has(questionchoice::factory()->count(4))->create();
    }
}
