<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->boolean('is_practice')->default(false);
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('practice_open')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('is_practice');
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('practice_open');
        });
    }
};
