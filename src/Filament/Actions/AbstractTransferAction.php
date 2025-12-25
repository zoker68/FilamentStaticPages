<?php

namespace Zoker\FilamentStaticPages\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zoker\FilamentMultisite\Facades\FilamentSiteManager;
use Zoker\FilamentStaticPages\Models\Content;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Services\BlocksExportImportService;

abstract class AbstractTransferAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-arrow-right-circle');
        $this->color('info');

        $this->schema($this->getFormSchema());

        $this->action(function (array $data): ?StreamedResponse {
            $service = new BlocksExportImportService;

            return match ($data['action_type']) {
                'export' => $this->handleExport($data, $service),
                'import' => $this->handleImport($data, $service),
                default => $this->handleCustomAction($data, $service),
            };
        });
    }

    /**
     * @return array<Field>
     */
    protected function getFormSchema(): array
    {
        return [
            Radio::make('action_type')
                ->label(__('fsp::lang.form.action'))
                ->options($this->getActionOptions())
                ->default('export')
                ->required()
                ->live(),

            FileUpload::make('file')
                ->label(__('fsp::lang.form.json_file'))
                ->acceptedFileTypes(['application/json'])
                ->required(fn (callable $get): bool => $get('action_type') === 'import')
                ->hidden(fn (callable $get): bool => $get('action_type') !== 'import')
                ->disk('local')
                ->directory('imports')
                ->visibility('private'),

            ...$this->getAdditionalFormFields(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleExport(array $data, BlocksExportImportService $service): StreamedResponse
    {
        /** @var Content|Page $record */
        $record = $this->getRecord();
        $locale = $data['source_locale'] ?? $this->getCurrentLocale();
        $json = $service->exportToJson($record, $locale);
        $filename = $this->generateExportFilename($record, $locale);

        return response()->streamDownload(
            fn () => print $json,
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleImport(array $data, BlocksExportImportService $service): ?StreamedResponse
    {
        $filePath = $data['file'];
        $json = Storage::disk('local')->get($filePath);
        Storage::disk('local')->delete($filePath);

        if (! $json) {
            $this->showErrorNotification(__('fsp::lang.messages.could_not_read_file'));

            return null;
        }

        $importData = $service->parseJsonImport($json);

        if (! $importData) {
            $this->showErrorNotification(__('fsp::lang.messages.invalid_json_format'));

            return null;
        }

        return $this->processImport($importData, $data, $service);
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

    protected function generateExportFilename(Content|Page $record, string $locale): string
    {
        $type = $this->getRecordType();
        $identifier = $this->getRecordIdentifier($record);

        return "{$type}-{$identifier}-{$locale}-" . now()->format('Y-m-d-His') . '.json';
    }

    abstract protected function getRecordType(): string;

    /**
     * @return array<string, string>
     */
    abstract protected function getActionOptions(): array;

    /**
     * @return array<Field>
     */
    abstract protected function getAdditionalFormFields(): array;

    abstract protected function getRecordIdentifier(Content|Page $record): string;

    /**
     * @param  array<string, mixed>  $data
     */
    abstract protected function handleCustomAction(array $data, BlocksExportImportService $service): ?StreamedResponse;

    /**
     * @param  array<string, mixed>  $importData
     * @param  array<string, mixed>  $data
     */
    abstract protected function processImport(array $importData, array $data, BlocksExportImportService $service): ?StreamedResponse;
}
