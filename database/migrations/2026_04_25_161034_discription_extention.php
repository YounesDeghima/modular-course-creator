<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen any varchar description columns to text so Ollama output never truncates
        foreach (['courses', 'chapters', 'lessons'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->text('description')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['courses', 'chapters', 'lessons'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('description', 255)->nullable()->change();
            });
        }
    }
};
