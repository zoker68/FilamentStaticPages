<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('filament-static-pages.table_prefix') . 'pages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('layout');
            $table->longText('content')->nullable();
            $table->boolean('published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-static-pages.table_prefix') . 'pages');
    }
};
