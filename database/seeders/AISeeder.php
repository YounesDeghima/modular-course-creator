<?php

namespace Database\Seeders;

use App\Models\block;
use App\Models\chapter;
use App\Models\course;
use App\Models\lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;


class AISeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = File::get(database_path('seeders/course.json'));
        $data = json_decode($json, true);

        // Create Course
        $course = course::create([
            'title' => $data['title'],
            'year' => $data['year'],
            'branch' => $data['branch'],
            'description' => $data['description'],
            'status' => $data['status'],
        ]);

        foreach ($data['chapters'] as $chapterData) {

            $chapter = chapter::create([
                'course_id' => $course->id,
                'title' => $chapterData['title'],
                'description' => $chapterData['description'] ?? null,
                'chapter_number' => $chapterData['chapter_number'],
                'status' => $chapterData['status'],
            ]);

            foreach ($chapterData['lessons'] as $lessonData) {

                $lesson = lesson::create([
                    'chapter_id' => $chapter->id,
                    'title' => $lessonData['title'],
                    'description' => $lessonData['description'] ?? null,
                    'lesson_number' => $lessonData['lesson_number'],
                    'status' => $lessonData['status'],
                ]);

                foreach ($lessonData['blocks'] as $blockData) {

                    block::create([
                        'lesson_id' => $lesson->id,
                        'content' => $blockData['content'],
                        'block_number' => $blockData['block_number'],
                        'type' => $blockData['type'],
                    ]);
                }
            }
        }
    }
}
