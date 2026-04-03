<?php

namespace Database\Seeders;

use App\Models\user;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class adminSeeder extends Seeder
{
    public function run(): void
    {
        user::create([
        'name' => 'younes',
        'last_name' => 'deghima',
        'email' => '2@2',
        'password' => Hash::make('s'),
        'role' => 'admin'
    ]);
    }
}
