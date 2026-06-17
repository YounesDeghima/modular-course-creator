<?php

namespace Database\Seeders;

use App\Models\lesson;
use App\Models\lesson_progress;
use App\Models\user;
use Illuminate\Database\Seeder;

class lessonProgressSeeder extends Seeder
{
    public function run(): void
    {
        $users   = user::where('role','=','user')->get();
        $lessons = lesson::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Run userSeeder first.');
            return;
        }

        if ($lessons->isEmpty()) {
            $this->command->warn('No lessons found. Run lessonSeeder first.');
            return;
        }

        $rows = [];

        foreach ($users as $user) {
            foreach ($lessons as $lesson) {
                $rows[] = [
                    'user_id'   => $user->id,
                    'lesson_id' => $lesson->id,
                    'progress'  => fake()->numberBetween(80, 100),
                ];
            }

            // Insert in chunks to avoid hitting DB query size limits
            if (count($rows) >= 500) {
                lesson_progress::insert($rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            lesson_progress::insert($rows);
        }

        $total = $users->count() * $lessons->count();
        $this->command->info(
            "Created {$total} lesson progress records " .
            "({$users->count()} users × {$lessons->count()} lessons)."
        );
    }
}
