<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Models\Page;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table((new Page)->getTable(), function (Blueprint $table) {
            $table->foreignIdFor(Site::class)
                ->nullable()
                ->after('id');

            Page::query()->update([
                'site_id' => Site::query()->first()->id,
            ]);

            $table->foreign('site_id')
                ->references('id')
                ->on((new Site)->getTable())
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table((new Page)->getTable(), function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });
    }
};
