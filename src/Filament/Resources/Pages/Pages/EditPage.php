<?php

declare(strict_types=1);

namespace PrismCms\Filament\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use PrismCms\Filament\Resources\Pages\PageResource;
use PrismCms\Models\Page;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (Page $record): string => $record->url(), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
