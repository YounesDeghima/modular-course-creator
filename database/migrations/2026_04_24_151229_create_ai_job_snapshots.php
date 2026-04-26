<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_job_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_job_id')->index();
            $table->foreign('ai_job_id')->references('id')->on('ai_jobs')->onDelete('cascade');

            // ── MinerU snapshot ──────────────────────────────────────────────
            $table->unsignedTinyInteger('md_index');          // 1, 2, 3 … (per job)
            $table->longText('markdown')->nullable();          // raw extracted markdown
            $table->string('images_path')->nullable();        // storage/ai_images/{job_id}/{md_index}/
            $table->json('image_urls')->nullable();           // array of public URLs found in markdown
            $table->string('md_status')->default('done');     // done | failed
            $table->text('md_error')->nullable();
            $table->timestamp('md_created_at')->nullable();

            // ── Ollama results (multiple per md) ─────────────────────────────
            // stored as JSON array of result objects
            $table->json('results')->nullable();
            /*
             * results structure:
             * [
             *   {
             *     "index": 1,
             *     "model": "phi4",
             *     "status": "done|failed",
             *     "result_json": "{...}",
             *     "error": null,
             *     "created_at": "2026-04-25 12:00:00",
             *     "duration_seconds": 42
             *   }
             * ]
             */

            $table->timestamps();
        });

        // Add model column to ai_jobs for per-job model selection
        Schema::table('ai_jobs', function (Blueprint $table) {
            $table->string('model')->default('phi4')->after('branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_job_snapshots');
        Schema::table('ai_jobs', function (Blueprint $table) {
            $table->dropColumn('model');
        });
    }
};
