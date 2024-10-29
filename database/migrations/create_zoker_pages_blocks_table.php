<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoker_pages_blocks', function (Blueprint $table) {
            $table->id();
            $table->morphs('blockable');
            $table->string('component');
            $table->unsignedInteger('sort')->default(0);
            $table->text('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoker_pages_blocks');
    }
};
