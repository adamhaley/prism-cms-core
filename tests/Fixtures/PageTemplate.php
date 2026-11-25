<?php

declare(strict_types=1);

namespace PrismCms\Tests\Fixtures;

use Filament\Forms\Components\TextInput;
use Illuminate\Http\Request;
use PrismCms\Contracts\PageTemplate as PageTemplateContract;
use PrismCms\Enums\PageSection;
use PrismCms\Models\Page;
use PrismCms\Templates\DescribesPageTemplate;

enum PageTemplate: string implements PageTemplateContract
{
    use DescribesPageTemplate;

    case Home = 'home';
    case Contact = 'contact';
    case Service = 'service';
    case Standard = 'standard';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function sections(): array
    {
        return match ($this) {
            self::Home => [PageSection::Hero],
            self::Contact => [PageSection::Hero, PageSection::HeroImage, PageSection::Content],
            self::Service => [PageSection::Hero, PageSection::HeroImage, PageSection::Content, PageSection::Bullets, PageSection::Buttons, PageSection::Card, PageSection::CallToAction],
            self::Standard => [PageSection::Hero, PageSection::HeroImage, PageSection::Content, PageSection::Bullets, PageSection::Buttons, PageSection::CircleImage, PageSection::CallToAction],
        };
    }

    public function fields(): array
    {
        return match ($this) {
            self::Contact => [TextInput::make('contact_note')],
            default => [],
        };
    }

    public function data(Page $page, Request $request): array
    {
        return match ($this) {
            self::Home => ['greeting' => 'Welcome home'],
            default => [],
        };
    }

    public function isSystem(): bool
    {
        return ! in_array($this, [self::Service, self::Standard], true);
    }
}
