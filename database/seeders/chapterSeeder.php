<?php

namespace Database\Seeders;

use App\Models\chapter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class chapterSeeder extends Seeder
{

    public function run(): void
    {
        chapter::factory()->count(10)->create();
    }
}
