<?php

declare(strict_types=1);

namespace PrismCms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PrismCms\Contracts\PageTemplate;
use PrismCms\Models\Page;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /** Builds the site's Page class, as named by the `prism.models.page` config. */
    public function modelName(): string
    {
        return config('prism.models.page');
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => ucfirst($title),
            'slug' => str($title)->slug()->toString(),
            'template' => $this->templateWhere(fn (PageTemplate $template): bool => ! $template->isHome() && ! $template->isSystem()),
            'is_published' => true,
        ];
    }

    public function home(): static
    {
        return $this->state(fn (): array => [
            'title' => 'Home',
            'slug' => null,
            'template' => $this->templateWhere(fn (PageTemplate $template): bool => $template->isHome()),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    /** @param callable(PageTemplate): bool $matches */
    private function templateWhere(callable $matches): PageTemplate
    {
        /** @var class-string<PageTemplate> $templates */
        $templates = config('prism.page_template');

        return collect($templates::cases())->first($matches)
            ?? throw new \LogicException("{$templates} has no matching template.");
    }
}
