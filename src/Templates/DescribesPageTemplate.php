<?php

declare(strict_types=1);

namespace PrismCms\Templates;

use Filament\Schemas\Components\Component;
use Illuminate\Http\Request;
use PrismCms\Contracts\PageTemplate;
use PrismCms\Models\Page;

/**
 * Defaults for a PageTemplate enum: the view is `pages.{value}`, the `home` case is the home
 * page, and a template adds no fields or view data until it overrides those methods.
 *
 * @phpstan-require-implements PageTemplate
 */
trait DescribesPageTemplate
{
    public function view(): string
    {
        return 'pages.'.$this->value;
    }

    public function isHome(): bool
    {
        return $this->value === 'home';
    }

    /** @return list<Component> */
    public function fields(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function data(Page $page, Request $request): array
    {
        return [];
    }
}
