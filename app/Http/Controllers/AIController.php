<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    public function jsonification(Request $request)
    {
        set_time_limit(600);

        if ($request->input('test_mode') === 'hi') {
            $prompt = 'Say hello in one sentence.';
        } else {
            $request->validate(['pdf_file' => 'required|file|mimes:pdf|max:20000']);

            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($request->file('pdf_file')->getPathname());
            $text   = $pdf->getText();

            $prompt = <<<PROMPT
You are an academic course content parser. Convert the following course text into a JSON object.

The JSON MUST follow this EXACT structure — no extra keys, no missing keys:

{
  "title": "string — course title",
  "year": 1,
  "branch": "string —  mi, st",
  "description": "string — short course description",
  "status": "published",
  "chapters": [
    {
      "title": "string",
      "description": "string",
      "chapter_number": 1,
      "status": "published",
      "lessons": [
        {
          "title": "string",
          "description": "string",
          "lesson_number": 1,
          "status": "published",
          "blocks": [
            { "content": "string", "block_number": 1, "type": "header" },
            { "content": "string", "block_number": 2, "type": "description" },
            { "content": "string", "block_number": 3, "type": "code" },
            { "content": "string", "block_number": 4, "type": "note" }
          ]
        }
      ]
    }
  ]
}

Rules:
- "branch" must be one of: "mi", "st", "none",
- block "type" must be one of: "header", "description", "code", "note"
- "header" = section title inside a lesson
- "description" = explanatory text or bullet points
- "code" = code snippets, formulas, equations
- "note" = warnings, tips, quoted definitions
- block_number, lesson_number, chapter_number all start at 1 and increment
- Extract as many chapters, lessons, and blocks as the text contains
- Return ONLY the JSON object. No markdown. No explanation. No extra text.

Course text:
{$text}
PROMPT;
        }

        try {
            $response = Http::timeout(600)->post('http://127.0.0.1:11434/api/generate', [
                'model'  => 'llama3.2',
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'error'  => 'Ollama returned an error',
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ], 500);
            }

            return response()->json($response->json());

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Could not reach Ollama. Is it running?',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Increase time limit for database-heavy operations on your RTX 3060 setup
        set_time_limit(600);

        $data = $request->all();

        try {
            return \DB::transaction(function () use ($data) {
                // 1. Create the Course (Matching your CourseController logic)
                $course = \App\Models\course::create([
                    'title'       => $data['title'] ?? 'Untitled Course',
                    'year'        => $data['year'] ?? 1,
                    'branch'      => ($data['year'] == 1) ? 'none' : ($data['branch'] ?? 'mi'),
                    'description' => $data['description'] ?? '',
                    'status'      => 'published',
                ]);

                // 2. Loop through Chapters (Matching your ChaptersController logic)
                if (!empty($data['chapters'])) {
                    foreach ($data['chapters'] as $cData) {
                        $chapter = $course->chapters()->create([
                            'title'          => $cData['title'],
                            'description'    => $cData['description'] ?? '',
                            'chapter_number' => $cData['chapter_number'],
                            'status'         => 'published',
                        ]);

                        // 3. Loop through Lessons (Matching your LessonController logic)
                        if (!empty($cData['lessons'])) {
                            foreach ($cData['lessons'] as $lData) {
                                $lesson = $chapter->lessons()->create([
                                    'title'         => $lData['title'],
                                    'description'   => $lData['description'] ?? '',
                                    'lesson_number' => $lData['lesson_number'],
                                    'status'        => 'published',
                                ]);

                                // 4. Loop through Blocks (Matching your BlockController logic)
                                if (!empty($lData['blocks'])) {
                                    foreach ($lData['blocks'] as $bData) {
                                        $lesson->blocks()->create([
                                            'content'      => $bData['content'],
                                            'type'         => $bData['type'], // header, description, code, note
                                            'block_number' => $bData['block_number'],
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Course, Chapters, Lessons, and Blocks imported successfully!',
                    'course_id' => $course->id
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database Sync Failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
