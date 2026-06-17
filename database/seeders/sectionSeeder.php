<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class sectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = [];
        for($year = 1;$year <=3 ; $year ++)
        {
            for($compagnie = 1; $compagnie<=5; $compagnie++){

                for($section = 1; $section<=3; $section++){
                    $sections[] = $year*100 + $compagnie*10 + $section;
                }
            }
        }
        $dataToInsert = array_map(fn($val) => ['section_number' => $val], $sections);
        DB::table('sections')->insert($dataToInsert);
    }
}
