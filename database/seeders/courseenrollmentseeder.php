<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class courseenrollmentseeder extends Seeder
{
    public function run(): void
    {
        // Fetch only users with the 'student' role.
        // Adjust the filter below if your role system differs
        // (e.g. ->where('role', 'student') or ->whereHas('roles', ...)).
        $students = User::where('role','=', 'user')->get();
        $courses  = Course::where('status','=','published')->get();

        if ($students->isEmpty() || $courses->isEmpty()) {
            $this->command->warn(
                'No students or courses found. Run the User and Course seeders first.'
            );
            return;
        }

        $rows = [];
        $now  = now();

        foreach ($students as $student) {
            foreach ($courses as $course) {
                $rows[] = [
                    'user_id'    => $student->id,
                    'course_id'  => $course->id,
                    'forced'     => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Insert in chunks to avoid hitting DB placeholder limits
        // and to keep memory usage flat on large datasets.
        foreach (array_chunk($rows, 500) as $chunk) {
            CourseEnrollment::insertOrIgnore($chunk);
        }

        $this->command->info(
            sprintf(
                'Enrolled %d student(s) × %d course(s) = %d enrollments.',
                $students->count(),
                $courses->count(),
                $students->count() * $courses->count()
            )
        );
    }
}
