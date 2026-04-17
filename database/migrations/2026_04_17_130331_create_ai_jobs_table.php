<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('queued'); // queued|processing|done|failed|saved
            $table->string('pdf_path');                  // local storage path
            $table->tinyInteger('year')->default(1);
            $table->string('branch')->default('none');
            $table->longText('result_json')->nullable(); // AI output
            $table->text('error_message')->nullable();   // if failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_jobs');
    }
};
