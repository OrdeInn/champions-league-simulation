<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('short_name', 3)->unique();
            $table->unsignedSmallInteger('power');
            $table->unsignedTinyInteger('home_advantage');
            $table->unsignedTinyInteger('goalkeeper_factor');
            $table->unsignedTinyInteger('supporter_strength');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
