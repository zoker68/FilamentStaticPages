<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('filament-static-pages.table_prefix') . 'pages', function (Blueprint $table) {
            $table->integer('parent_id')->nullable()->after('id');
        });
        Schema::table(config('filament-static-pages.table_prefix') . 'pages', function (Blueprint $table) {
            $table->string('url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table(config('filament-static-pages.table_prefix') . 'pages', function (Blueprint $table) {
            $table->dropColumn('parent_id');
        });
        Schema::table(config('filament-static-pages.table_prefix') . 'pages', function (Blueprint $table) {
            $table->string('url')->nullable(false)->change();
        });
    }
};
