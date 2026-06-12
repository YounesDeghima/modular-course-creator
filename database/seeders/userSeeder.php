<?php

namespace Database\Seeders;

use App\Models\section;
use App\Models\user;
use Illuminate\Database\Seeder;

class userSeeder extends Seeder
{
    /**
     * Number of students to create per section.
     */
    private int $studentsPerSection = 10;

    public function run(): void
    {
        $sections = section::all();

        if ($sections->isEmpty()) {
            $this->command->warn('No sections found. Run sectionSeeder first.');
            return;
        }

        foreach ($sections as $section) {
            user::factory()
                ->count($this->studentsPerSection)
                ->create(['section_id' => $section->id]);
        }

        $total = $sections->count() * $this->studentsPerSection;
        $this->command->info("Created {$total} students across {$sections->count()} sections.");
    }
}
