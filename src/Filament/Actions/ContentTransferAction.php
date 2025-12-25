<?php

namespace Zoker\FilamentStaticPages\Filament\Actions;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zoker\FilamentMultisite\Models\Site;
use Zoker\FilamentStaticPages\Models\Content;
use Zoker\FilamentStaticPages\Models\Page;
use Zoker\FilamentStaticPages\Services\BlocksExportImportService;

class ContentTransferAction extends AbstractTransferAction
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->name('contentTransfer');
        $this->label(__('fsp::lang.actions.transfer_content'));
    }

    /**
     * @return array<string, string>
     */
    protected function getActionOptions(): array
    {
        return [
            'export' => __('fsp::lang.actions.export_to_json'),
            'import' => __('fsp::lang.actions.import_from_json'),
            'copy_existing' => __('fsp::lang.actions.copy_to_existing_content'),
        ];
    }

    /**
     * @return array<Field>
     */
    protected function getAdditionalFormFields(): array
    {
        return [
            Select::make('source_locale')
                ->label(__('fsp::lang.form.source_language'))
                ->options($this->getLocaleOptions())
                ->required(fn ($get) => in_array($get('action_type'), ['export', 'copy_existing']))
                ->hidden(fn ($get) => $get('action_type') === 'import')
                ->default($this->getCurrentLocale()),

            Select::make('target_content_id')
                ->label(__('fsp::lang.form.target_content'))
                ->options(Content::pluck('code', 'id')->toArray())
                ->required(fn ($get) => $get('action_type') === 'copy_existing')
                ->hidden(fn ($get) => $get('action_type') !== 'copy_existing')
                ->searchable(),

            Select::make('target_locale')
                ->label(__('fsp::lang.form.target_language'))
                ->options(fn () => $this->getLocaleOptions())
                ->required(fn ($get) => in_array($get('action_type'), ['import', 'copy_existing']))
                ->hidden(fn ($get) => $get('action_type') === 'export')
                ->default($this->getCurrentLocale()),

            Radio::make('copy_mode')
                ->label(__('fsp::lang.form.import_mode'))
                ->options([
                    'append' => __('fsp::lang.form.append_to_existing_blocks'),
                    'replace' => __('fsp::lang.form.replace_existing_blocks'),
                ])
                ->default('append')
                ->required(fn ($get) => in_array($get('action_type'), ['import', 'copy_existing']))
                ->hidden(fn ($get) => $get('action_type') === 'export'),
        ];
    }

    protected function getRecordType(): string
    {
        return 'content';
    }

    protected function getRecordIdentifier(Content|Page $record): string
    {
        /** @var Content $record */
        return $record->code;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleCustomAction(array $data, BlocksExportImportService $service): ?StreamedResponse
    {
        return $this->handleCopyToExisting($data, $service);
    }

    /**
     * @param  array<string, mixed>  $importData
     * @param  array<string, mixed>  $data
     */
    protected function processImport(array $importData, array $data, BlocksExportImportService $service): ?StreamedResponse
    {
        /** @var Content $record */
        $record = $this->getRecord();
        $targetLocale = $data['target_locale'] ?? $this->getCurrentLocale();
        $replaceContent = ($data['copy_mode'] ?? 'append') === 'replace';

        $service->importBlocksToExistingContent($importData, $record, $targetLocale, $replaceContent);

        $this->showSuccessNotification(
            __('fsp::lang.messages.blocks_imported_to_code', [
                'code' => $record->code,
                'locale' => strtoupper($targetLocale),
            ])
        );

        /** @phpstan-ignore-next-line */
        $this->redirect($this->getLivewire()->getResource()::getUrl('edit', ['record' => $record->id]));

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleCopyToExisting(array $data, BlocksExportImportService $service): ?StreamedResponse
    {
        /** @var Content $record */
        $record = $this->getRecord();
        $targetContent = Content::find((int) $data['target_content_id']);

        if (! $targetContent) {
            $this->showErrorNotification(__('fsp::lang.messages.target_content_not_found'));

            return null;
        }

        if ($record->id === $targetContent->id && $data['source_locale'] === $data['target_locale']) {
            $this->showErrorNotification(__('fsp::lang.messages.cannot_copy_to_same_content'));

            return null;
        }

        $replaceContent = ($data['copy_mode'] ?? 'append') === 'replace';
        $sourceLocale = $data['source_locale'] ?? $this->getCurrentLocale();
        $targetLocale = $data['target_locale'] ?? $this->getCurrentLocale();

        $service->copyBlocksToExisting($record, $targetContent, $replaceContent, $sourceLocale, $targetLocale);

        $this->showSuccessNotification(
            __('fsp::lang.messages.blocks_copied_to_content', [
                'code' => $targetContent->code,
                'locale' => strtoupper($targetLocale),
            ])
        );

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function getLocaleOptions(): array
    {
        $locales = Site::getUsingLocales();

        return array_combine($locales, array_map(fn ($locale) => strtoupper($locale), $locales));
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'contentTransfer');
    }
}
