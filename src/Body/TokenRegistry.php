<?php

declare(strict_types=1);

namespace PrismCms\Body;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use PrismCms\Models\Page;

/**
 * The `[[tokens]]` a page body can hold. Sites and modules register a renderer per token name,
 * typically in a service provider's boot():
 *
 *     app(TokenRegistry::class)->register('packages', fn (Page $page, ?string $argument) => view('...', [...]));
 *
 * A renderer returns HTML (a view or other Htmlable), plain text (escaped) or null for nothing.
 * Unknown tokens render nothing.
 */
class TokenRegistry
{
    /** @var array<string, Closure(Page, ?string): (Htmlable|string|null)> */
    private array $renderers = [];

    /** @param Closure(Page, ?string): (Htmlable|string|null) $renderer */
    public function register(string $name, Closure $renderer): static
    {
        $this->renderers[$name] = $renderer;

        return $this;
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->renderers);
    }

    public function render(BodySegment $segment, Page $page): string
    {
        $renderer = $this->renderers[$segment->token] ?? null;
        $output = $renderer ? $renderer($page, $segment->argument) : null;

        return $output instanceof Htmlable ? $output->toHtml() : e((string) $output);
    }
}
