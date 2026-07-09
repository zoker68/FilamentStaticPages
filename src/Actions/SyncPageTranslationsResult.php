<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Actions;

/**
 * Outcome of a translations sync: how many linked translations received the
 * original's content, and for how many of them an AI translation was queued
 * (same-locale translations are synced but never translated).
 */
final readonly class SyncPageTranslationsResult
{
    public function __construct(
        public int $synced,
        public int $queued,
    ) {}
}
