<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoker_pages_pages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('layout');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoker_pages_pages');
    }
};
