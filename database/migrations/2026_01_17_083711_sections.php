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
        schema::create('sections', function (Blueprint $table) {
           $table->id();
           $table->integer('section_number');

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        schema::dropIfExists('sections');
    }
};
