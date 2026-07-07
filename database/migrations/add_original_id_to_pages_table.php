<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Models\Page;

return new class extends Migration
{
    public function up(): void
    {
        $table = (new Page)->getTable();

        if (! Schema::hasColumn($table, 'original_id')) {
            Schema::table($table, function (Blueprint $blueprint) {
                // Cross-site link: a translation points at its original page on the
                // default site. Distinct from parent_id (intra-site hierarchy); no FK,
                // matching parent_id, so deleting an original does not cascade.
                $blueprint->unsignedBigInteger('original_id')->nullable()->after('parent_id')->index();
            });
        }

        $this->backfill($table);
    }

    public function down(): void
    {
        $table = (new Page)->getTable();

        if (Schema::hasColumn($table, 'original_id')) {
            Schema::table($table, function (Blueprint $blueprint) {
                // Drop the index before the column — SQLite errors otherwise.
                $blueprint->dropIndex(['original_id']);
                $blueprint->dropColumn('original_id');
            });
        }
    }

    /**
     * One-time seed of the explicit link for existing data: a non-default-site page
     * whose url matches a default-site page's url becomes that page's translation.
     * The slug is trusted only here; links are explicit from now on.
     */
    private function backfill(string $table): void
    {
        $sitesTable = (new Site)->getTable();

        $defaultSiteId = null;
        if (Schema::hasColumn($sitesTable, 'is_default')) {
            $defaultSiteId = DB::table($sitesTable)->where('is_active', true)->where('is_default', true)->value('id');
        }
        $defaultSiteId ??= DB::table($sitesTable)->where('is_active', true)->whereNull('prefix')->orderBy('id')->value('id');

        if (! $defaultSiteId) {
            return;
        }

        $originals = DB::table($table)->where('site_id', $defaultSiteId)->whereNotNull('url')->pluck('id', 'url');

        foreach ($originals as $url => $id) {
            if ($url === '') {
                continue;
            }

            DB::table($table)
                ->where('site_id', '!=', $defaultSiteId)
                ->where('url', $url)
                ->whereNull('original_id')
                ->update(['original_id' => $id]);
        }

        // Home pages have url = null (by convention) — link them across sites too.
        $defaultHomeId = DB::table($table)->where('site_id', $defaultSiteId)->whereNull('url')->value('id');
        if ($defaultHomeId) {
            DB::table($table)
                ->where('site_id', '!=', $defaultSiteId)
                ->whereNull('url')
                ->whereNull('original_id')
                ->update(['original_id' => $defaultHomeId]);
        }
    }
};
