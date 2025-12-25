<?php

namespace Zoker\FilamentStaticPages\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Zoker\FilamentMultisite\Facades\FilamentSiteManager;
use Zoker\FilamentStaticPages\Services\BlocksExportImportService;

abstract class AbstractImportExportAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('fsp::lang.actions.import'));
        $this->icon('heroicon-o-arrow-down-tray');
        $this->color('gray');

        $this->schema($this->getFormSchema());

        $this->action(function (array $data): void {
            $this->handleAction($data);
        });
    }

    /**
     * @return array<Field>
     */
    protected function getFormSchema(): array
    {
        return [
            FileUpload::make('file')
                ->label(__('fsp::lang.form.json_file'))
                ->acceptedFileTypes(['application/json'])
                ->required()
                ->disk('local')
                ->directory('imports')
                ->visibility('private'),

            ...$this->getAdditionalFormFields(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleAction(array $data): void
    {
        $filePath = $data['file'];
        $json = Storage::disk('local')->get($filePath);

        Storage::disk('local')->delete($filePath);

        if (! $json) {
            $this->showErrorNotification(
                __('fsp::lang.messages.could_not_read_file')
            );

            return;
        }

        $service = new BlocksExportImportService;
        $importData = $service->parseJsonImport($json);

        if (! $importData) {
            $this->showErrorNotification(
                __('fsp::lang.messages.invalid_json_format')
            );

            return;
        }

        $this->processImportData($importData, $data, $service);
    }

    protected function showErrorNotification(string $message): void
    {
        Notification::make()
            ->title(__('fsp::lang.messages.error'))
            ->body($message)
            ->danger()
            ->send();
    }

    protected function showSuccessNotification(string $message): void
    {
        Notification::make()
            ->title(__('fsp::lang.messages.success'))
            ->body($message)
            ->success()
            ->send();
    }

    protected function getCurrentLocale(): string
    {
        return FilamentSiteManager::getCurrentSiteLocale();
    }

    /**
     * @param  array<string, mixed>  $importData
     * @param  array<string, mixed>  $data
     */
    abstract protected function processImportData(array $importData, array $data, BlocksExportImportService $service): void;

    /**
     * @return array<Field>
     */
    abstract protected function getAdditionalFormFields(): array;
}
