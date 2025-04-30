<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zoker\FilamentStaticPages\Models\Content;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create((new Content)->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->longText('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists((new Content)->getTable());
    }
};
