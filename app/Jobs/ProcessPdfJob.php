<?php

namespace App\Jobs;

use App\Models\AiJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class ProcessPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * No timeout — the job runs until Ollama finishes.
     * Set this high enough for large PDFs (phi4 can be slow).
     */
    public int $timeout = 0;

    /**
     * Do not retry on failure — a failed AI call should surface cleanly.
     */
    public int $tries = 1;

    public function __construct(public int $aiJobId) {}

    public function handle(): void
    {
        $aiJob = AiJob::findOrFail($this->aiJobId);
        $aiJob->update(['status' => 'processing']);

        try {
            // ── 1. Extract text from PDF ───────────────────────────────
            $pdfPath    = Storage::disk('local')->path($aiJob->pdf_path);
            $parser     = new Parser();
            $pdf        = $parser->parseFile($pdfPath);
            $rawText    = $pdf->getText();

            if (empty(trim($rawText))) {
                throw new \RuntimeException('PDF appears to be empty or image-only (no extractable text).');
            }

            // ── 2. Build the prompt ────────────────────────────────────
            //
            // IMPORTANT RULES given to the model:
            //  • Copy text VERBATIM — no rewording, no summaries.
            //  • Choose block type from the allowed list.
            //  • One chapter  = one logical section of the PDF.
            //  • One lesson   = one topic within a chapter.
            //  • One block    = one paragraph / heading / code snippet.
            //
            $prompt = <<<PROMPT
You are a course-structure extractor.
Your ONLY job is to convert the raw PDF text below into a JSON object.

STRICT RULES:
1. Copy every word VERBATIM — do NOT summarise, paraphrase, or add anything.
2. Each heading becomes a block with type "header".
3. Each paragraph becomes a block with type "description".
4. Indented / monospaced text becomes a block with type "code".
5. Short callout / tip / warning lines become type "note".
6. Group blocks into lessons by topic, lessons into chapters by section.
7. Assign sequential block_number, lesson_number, chapter_number starting at 1.
8. status is always "draft".
9. Return ONLY the JSON — no prose, no markdown fences, no explanation.

REQUIRED JSON SHAPE (follow exactly):
{
  "title": "<course title>",
  "year": {$aiJob->year},
  "branch": "{$aiJob->branch}",
  "description": "<first sentence of the document>",
  "status": "draft",
  "chapters": [
    {
      "title": "<chapter title>",
      "description": "<chapter first sentence>",
      "chapter_number": 1,
      "status": "draft",
      "lessons": [
        {
          "title": "<lesson title>",
          "description": "<lesson first sentence>",
          "lesson_number": 1,
          "status": "draft",
          "blocks": [
            { "content": "<verbatim text>", "block_number": 1, "type": "header" },
            { "content": "<verbatim text>", "block_number": 2, "type": "description" }
          ]
        }
      ]
    }
  ]
}

Allowed block types: header, description, note, code, math, ext

--- BEGIN PDF TEXT ---
{$rawText}
--- END PDF TEXT ---
PROMPT;

            // ── 3. Call Ollama ─────────────────────────────────────────
            $response = Http::timeout(0)          // no HTTP-level timeout
            ->withOptions(['connect_timeout' => 10])
                ->post('http://localhost:11434/api/generate', [
                    'model'  => 'phi4',
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                    'options' => [
                        'temperature' => 0,       // deterministic — we want exact copy
                        'num_predict' => -1,       // no token limit
                    ],
                ]);

            if ($response->failed()) {
                throw new \RuntimeException('Ollama HTTP error: ' . $response->status());
            }

            $body = $response->json();

            // Ollama returns { "response": "<json string>" } when format=json
            $jsonString = $body['response'] ?? null;

            if (!$jsonString) {
                throw new \RuntimeException('Ollama returned empty response.');
            }

            // Validate it is actually parseable JSON
            $decoded = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try to strip any stray markdown fences the model may have added
                $jsonString = preg_replace('/^```json\s*/i', '', trim($jsonString));
                $jsonString = preg_replace('/```\s*$/', '', $jsonString);
                $decoded    = json_decode($jsonString, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('AI output is not valid JSON: ' . json_last_error_msg());
                }
            }

            // ── 4. Persist result ──────────────────────────────────────
            $aiJob->update([
                'status'      => 'done',
                'result_json' => json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]);

        } catch (\Throwable $e) {
            $aiJob->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
