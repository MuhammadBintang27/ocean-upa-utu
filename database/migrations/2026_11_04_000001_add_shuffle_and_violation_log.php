<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('shuffle_questions')->default(false);
        });

        Schema::create('violation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_logs');
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('shuffle_questions');
        });
    }
};
