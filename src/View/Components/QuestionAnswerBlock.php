<?php

namespace Zoker\FilamentStaticPages\View\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Zoker\FilamentStaticPages\Classes\BlockComponent;

class QuestionAnswerBlock extends BlockComponent
{
    public static string $label = 'Question & Answer';

    public static string $viewTemplate = 'components.question-answer';

    public static string $viewNamespace = 'fsp';

    public static string $icon = 'heroicon-o-question-mark-circle';

    public static function getSchema(): array
    {
        return [
            Repeater::make('categories')
                ->label('Categories of questions')
                ->columnSpanFull()
                ->itemLabel(fn (array $state) => $state['title'])
                ->addActionLabel('Add category')
                ->schema([
                    TextInput::make('title')
                        ->label('Title of category')
                        ->required(),

                    Repeater::make('questions')
                        ->label('Questions')
                        ->columns(2)
                        ->itemLabel(fn (array $state) => strip_tags($state['question']))
                        ->addActionLabel('Add question')
                        ->schema([
                            Textarea::make('question')
                                ->label('Question')
                                ->required(),

                            Textarea::make('answer')
                                ->label('Answer')
                                ->required(),
                        ]),
                ]),
        ];
    }
}
