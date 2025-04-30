<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zoker\FilamentStaticPages\Models\Menu;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create((new Menu)->getTable(), function (Blueprint $table) {
            $table->id();

            $table->string('code')->index();
            $table->text('items')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists((new Menu)->getTable());
    }
};
