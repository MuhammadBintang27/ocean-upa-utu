<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row table: global exam switches.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_open')->default(false);
            $table->unsignedInteger('max_violations')->default(3);
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('directions')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->decimal('correct_points', 6, 2)->default(1);
            $table->decimal('wrong_points', 6, 2)->default(0);
            $table->decimal('blank_points', 6, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('passages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('passage_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->text('text');
            $table->string('option_a');
            $table->string('option_b');
            $table->string('option_c');
            $table->string('option_d');
            $table->char('answer', 1);
            $table->timestamps();
        });

        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            // nullable: MySQL/MariaDB gives the first NOT NULL timestamp ON UPDATE CURRENT_TIMESTAMP,
            // which would overwrite the start time whenever the attempt is finished.
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'section_id']);
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->char('choice', 1);
            $table->timestamps();
            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
        Schema::dropIfExists('attempts');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('passages');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('settings');
    }
};
