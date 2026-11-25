<?php

declare(strict_types=1);

namespace PrismCms\Contracts;

use BackedEnum;
use Filament\Schemas\Components\Component;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Http\Request;
use PrismCms\Enums\PageSection;
use PrismCms\Models\Page;

/**
 * A site's page templates, as a backed enum named by the `prism.page_template` config. Each
 * template describes itself, so adding one touches only the site: the page editor and the page
 * controller ask the template instead of listing templates.
 *
 * The DescribesPageTemplate trait supplies defaults for everything except sections(),
 * isSystem() and getLabel().
 */
interface PageTemplate extends BackedEnum, HasLabel
{
    /** The Blade view that renders a page with this template. */
    public function view(): string;

    /**
     * The core form sections this template uses.
     *
     * @return list<PageSection>
     */
    public function sections(): array;

    /**
     * Extra, site-specific form fields shown only for this template.
     *
     * @return list<Component>
     */
    public function fields(): array;

    /**
     * Extra view data for a page with this template.
     *
     * @return array<string, mixed>
     */
    public function data(Page $page, Request $request): array;

    /** The site's root page: it lives at `/` and has no slug or parent. */
    public function isHome(): bool;

    /**
     * Whether the site's structure depends on this template. Only admins may apply, re-template
     * or delete system templates, and each one is used by one page at most.
     */
    public function isSystem(): bool;
}
