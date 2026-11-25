<?php

declare(strict_types=1);

namespace PrismCms\Tests\Filament\Resources\Pages;

use Livewire\Livewire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PrismCms\Filament\Resources\Pages\PageResource;
use PrismCms\Filament\Resources\Pages\Pages\CreatePage;
use PrismCms\Filament\Resources\Pages\Pages\EditPage;
use PrismCms\Filament\Resources\Pages\Pages\ListPages;
use PrismCms\Models\Page;
use PrismCms\Tests\Fixtures\PageTemplate;
use PrismCms\Tests\Fixtures\User;
use PrismCms\Tests\TestCase;

#[CoversClass(PageResource::class)]
class PageResourceTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::query()->create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'x']);
        $this->actingAs($this->user);
    }

    #[Test]
    public function it_lists_pages(): void
    {
        $pages = Page::factory()->count(2)->create();

        Livewire::test(ListPages::class)->assertCanSeeTableRecords($pages);
    }

    #[Test]
    public function it_creates_a_page(): void
    {
        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'About us', 'template' => PageTemplate::Standard->value])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', ['path' => 'about-us', 'template' => 'standard']);
    }

    #[Test]
    public function it_hides_sections_the_template_does_not_use(): void
    {
        $page = Page::factory()->create(['template' => PageTemplate::Contact]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormFieldHidden('bullets');
    }

    #[Test]
    public function it_shows_a_templates_own_fields_for_that_template(): void
    {
        $page = Page::factory()->create(['template' => PageTemplate::Contact, 'slug' => 'contact']);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormFieldVisible('contact_note');
    }

    #[Test]
    public function it_hides_a_templates_own_fields_for_other_templates(): void
    {
        $page = Page::factory()->create();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormFieldHidden('contact_note');
    }

    #[Test]
    public function it_offers_editors_no_system_templates(): void
    {
        $this->user->admin = false;

        Livewire::test(CreatePage::class)
            ->assertFormFieldExists('template', fn ($field): bool => array_keys($field->getOptions()) === ['service', 'standard']);
    }
}
