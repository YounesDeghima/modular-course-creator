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
You are a course-structure extractor. Convert PDF content into structured educational blocks.

BLOCK TYPE GUIDE — use exactly these types:

1. header
   - Chapter/section titles, lesson titles, major headings
   - Content: plain text title

2. description
   - Regular paragraphs, explanations, theory
   - Content: plain text (reconstruct math with Unicode: α β φ ∈ ℝ → ∞ ∑ ∫)

3. note
   - Tips, warnings, important callouts, "remember that..."
   - Content: short plain text (1-2 sentences)

4. code
   - Algorithms, pseudocode, formulas in monospace, structured steps
   - Content: indented text, step-by-step procedures
   - Example: "Step 1: Calculate x_n = (a+b)/2"

5. math
   - Standalone equations, theorems with formulas, important identities
   - Content: math notation using Unicode symbols OR simple notation
   - USE: x^2, (a/b), √x, x_n, α, β, φ, ∈, ℝ, →, ∞, ∑, ∫, ≠, ≈, ≤, ≥
   - AVOID: backslashes like \frac, \alpha, \sqrt (breaks JSON)
   - Example: "x_n = (a_n + b_n)/2 → α as n → ∞"

6. exercise
   - Practice problems, examples to solve, "Exercise:", "Example:"
   - Content: problem statement only (solutions added later by teacher)
   - Auto-creates empty solution slot

7. photo / video
   - Visual content references
   - Content: leave EMPTY string "" (teacher uploads file later)
   - Use when PDF shows: diagrams, photos, illustrations, video references

8. graph
   - Charts with data points: line charts, bar charts, pie charts
   - Content: JSON string with this exact shape:
     {"type": "line", "labels": ["Jan", "Feb", "Mar"], "data": [10, 20, 15]}
   - Types allowed: "line", "bar", "pie"
   - Extract data from tables or text descriptions

9. table
   - Structured data, comparison tables, iteration tables
   - Content: JSON 2D array as string:
     [["Column1", "Column2"], ["Row1Data1", "Row1Data2"], ["Row2Data1", "Row2Data2"]]
   - First row = headers

10. function
    - Function plots, graphs of f(x)
    - Content: JSON string with this exact shape:
      {"function": "sin(x)", "x_min": -10, "x_max": 10, "y_min": -5, "y_max": 5, "color": "#4f46e5", "step": 0.1}
    - Extract function expression from text

11. list
    - Bulleted lists, numbered steps, checklists
    - Content: JSON string with this exact shape:
      {"style": "bullet", "items": ["First item", "Second item", "Third item"]}
    - Styles: "bullet", "numbered", "checklist"

12. separator
    - Page breaks, section dividers, visual breaks
    - Content: JSON string: {"type": "divider"}
    - Types: "divider", "page_break", "section_break"

13. ext
    - External links, embeds, references
    - Content: URL or reference text

PDF EXTRACTION RULES:
- Reconstruct broken math: "an+ n 2" → "(a_n + b_n) / 2"
- Fix accents: "3\`eme ann\'ee" → "3ème année"
- Greek letters: use φ not \varphi, α not \alpha, β not \beta
- Images in PDF → use type "photo" with empty content (teacher adds file later)
- Tables in PDF → convert to type "table" with JSON content
- Graphs/diagrams → use type "photo" (visual) or "function" (if it's a plot)

REQUIRED JSON OUTPUT:
{
  "title": "<course title>",
  "year": {$aiJob->year},
  "branch": "{$aiJob->branch}",
  "description": "<brief summary>",
  "status": "draft",
  "chapters": [
    {
      "title": "<chapter title>",
      "description": "<chapter summary>",
      "chapter_number": 1,
      "status": "draft",
      "lessons": [
        {
          "title": "<lesson title>",
          "description": "<lesson summary>",
          "lesson_number": 1,
          "status": "draft",
          "blocks": [
            {"type": "header", "content": "Introduction", "block_number": 1},
            {"type": "description", "content": "We study numerical methods for finding roots...", "block_number": 2},
            {"type": "math", "content": "f(x) = 0, x ∈ [a,b]", "block_number": 3},
            {"type": "code", "content": "Algorithm:\nStep 1: Set x_0 = (a+b)/2\nStep 2: Evaluate f(x_0)", "block_number": 4},
            {"type": "table", "content": "[[\"n\", \"a_n\", \"b_n\", \"x_n\"], [\"0\", \"0\", \"1\", \"0.5\"], [\"1\", \"0\", \"0.5\", \"0.25\"]]", "block_number": 5},
            {"type": "exercise", "content": "Apply dichotomy to f(x) = x^2 - 2 on [0,2]", "block_number": 6},
            {"type": "photo", "content": "", "block_number": 7}
          ]
        }
      ]
    }
  ]
}

--- BEGIN PDF TEXT ---
{$rawText}
--- END PDF TEXT ---

Return ONLY valid JSON. No markdown, no explanation.
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
