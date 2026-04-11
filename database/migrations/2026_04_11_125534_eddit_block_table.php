<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Convert enum to string to support new types
        Schema::table('blocks', function (Blueprint $table) {
            $table->string('type')->change();
        });
    }

    public function down(): void {
        Schema::table('blocks', function (Blueprint $table) {
            $table->enum('type', ['header','description','note','code','exercise'])->change();
        });
    }
};
