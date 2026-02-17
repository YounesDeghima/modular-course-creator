<?php

namespace Database\Seeders;

use App\Models\block;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class blockSeeder extends Seeder
{
    public function run(): void
    {
        block::factory()->count(10)->create();
    }
}
