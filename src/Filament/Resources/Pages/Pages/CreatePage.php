<?php

declare(strict_types=1);

namespace PrismCms\Filament\Resources\Pages\Pages;

use Filament\Resources\Pages\CreateRecord;
use PrismCms\Filament\Resources\Pages\PageResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function afterFill(): void
    {
        if ($parentId = request()->integer('parent')) {
            $this->data['parent_id'] = $parentId;
        }
    }
}
