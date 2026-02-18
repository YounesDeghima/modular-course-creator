<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('exercisesolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();

            $table->string('content')->nullable();
            $table->integer('solution_number');


            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('exercisesolutions');
    }
};
