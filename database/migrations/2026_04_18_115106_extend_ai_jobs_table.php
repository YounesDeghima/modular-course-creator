<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table) {
            // Already exists check handled by ->nullable()
            if (!Schema::hasColumn('ai_jobs', 'logs')) {
                $table->json('logs')->nullable()->after('error_message');
            }
            $table->unsignedTinyInteger('attempt')->default(0)->after('logs');
            $table->unsignedTinyInteger('max_attempts')->default(3)->after('attempt');
            $table->unsignedTinyInteger('priority')->default(5)->after('max_attempts'); // 1=highest,10=lowest
            $table->string('original_filename')->nullable()->after('pdf_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('original_filename');
            $table->string('started_by')->nullable()->after('file_size');   // user name
            $table->unsignedBigInteger('started_by_id')->nullable()->after('started_by');
            $table->timestamp('started_at')->nullable()->after('started_by_id');
            $table->timestamp('finished_at')->nullable()->after('started_at');
            $table->unsignedInteger('duration_seconds')->nullable()->after('finished_at');
            $table->string('note')->nullable()->after('duration_seconds'); // admin freetext note
        });
    }

    public function down(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'logs','attempt','max_attempts','priority',
                'original_filename','file_size',
                'started_by','started_by_id',
                'started_at','finished_at','duration_seconds','note',
            ]);
        });
    }
};
