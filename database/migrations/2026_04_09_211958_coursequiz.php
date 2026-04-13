<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("coursequestions", function (Blueprint $table) {
            $table->id();
            $table->string("content");
            $table->foreignId("course_id")->constrained()->onDelete("cascade");
            $table->integer("question_number")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        schema::dropIfExists("course_quizzes");
    }
};
