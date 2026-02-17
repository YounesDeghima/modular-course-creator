<?php

namespace Database\Seeders;

use App\Models\user;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class adminSeeder extends Seeder
{
    public function run(): void
    {
        user::create([
        'name' => 'younes',
        'last_name' => 'deghima',
        'email' => '2@2',
        'password' => '$2y$12$p1P4OgrC0.sWX/fPgDRMa.VL0GXjwM4p5.YVBx908XXwMBA0Gxf.K',
        'role' => 'admin'
    ]);
    }
}
