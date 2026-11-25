<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hh_vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('hh_id')->unique();
            $table->string('name');
            $table->string('employer_id')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('area_id')->nullable();
            $table->string('area_name')->nullable();
            $table->json('salary')->nullable();
            $table->json('experience')->nullable();
            $table->json('employment')->nullable();
            $table->json('schedule')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->string('alternate_url')->nullable();
            $table->longText('description')->nullable();
            $table->string('search_query')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestampTz('fetched_at')->nullable();
            $table->timestamps();
            $table->index('published_at');
            $table->index('area_id');
            $table->index('employer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hh_vacancies');
    }
};
