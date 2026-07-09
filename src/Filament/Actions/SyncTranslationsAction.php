<?php

declare(strict_types=1);

namespace Zoker\FilamentStaticPages\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Zoker\FilamentStaticPages\Actions\SyncPageTranslationsAction;
use Zoker\FilamentStaticPages\Models\Page;

class SyncTranslationsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('syncTranslations');
        $this->label(__('fsp::lang.actions.sync_translations'));
        $this->icon('heroicon-o-language');
        $this->color('warning');

        $this->requiresConfirmation();
        $this->modalHeading(__('fsp::lang.actions.sync_translations'));
        $this->modalDescription(__('fsp::lang.messages.sync_translations_warning'));
        $this->modalSubmitActionLabel(__('fsp::lang.actions.sync_translations'));

        // Visible only on an eligible original that actually has translations to sync.
        $this->visible(fn (?Page $record): bool => SyncPageTranslationsAction::eligible($record)
            && $record?->translations()->exists() === true);

        $this->action(function (Page $record): void {
            $result = app(SyncPageTranslationsAction::class)->handle($record);

            if ($result->synced === 0) {
                Notification::make()
                    ->title(__('fsp::lang.messages.success'))
                    ->body(__('fsp::lang.messages.sync_no_translations'))
                    ->info()
                    ->send();

                return;
            }

            // "Translation queued" is claimed only when jobs were actually
            // dispatched — same-locale translations are synced without AI.
            $message = $result->queued > 0
                ? __('fsp::lang.messages.sync_translations_queued', ['count' => $result->synced])
                : __('fsp::lang.messages.sync_translations_synced', ['count' => $result->synced]);

            Notification::make()
                ->title(__('fsp::lang.messages.success'))
                ->body($message)
                ->success()
                ->send();
        });
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'syncTranslations');
    }
}
