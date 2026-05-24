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
        schema::create('event_section',function (Blueprint $table){
           $table->id();
           $table->foreignId('event_id')->constrained('events');
           $table->foreignId('section_id')->constrained('sections');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        schema::dropIfExists('event_section');
    }
};
