<?php

declare(strict_types=1);

namespace PrismCms\Tests\Http\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PrismCms\Http\Controllers\PageController;
use PrismCms\Models\Page;
use PrismCms\Tests\Fixtures\User;
use PrismCms\Tests\TestCase;

#[CoversClass(PageController::class)]
class PageControllerTest extends TestCase
{
    #[Test]
    public function it_renders_the_home_page_with_its_template_data(): void
    {
        Page::factory()->home()->create();

        $this->get('/')->assertOk()->assertSee('Welcome home');
    }

    #[Test]
    public function it_resolves_a_nested_page_by_its_path(): void
    {
        $parent = Page::factory()->create(['slug' => 'services']);
        Page::factory()->for($parent, 'parent')->create(['slug' => 'websites', 'title' => 'Websites']);

        $this->get('/services/websites/')->assertOk()->assertSee('<h1>Websites</h1>', false);
    }

    #[Test]
    public function it_hides_drafts_from_guests(): void
    {
        Page::factory()->draft()->create(['slug' => 'secret']);

        $this->get('/secret/')->assertNotFound();
    }

    #[Test]
    public function it_shows_drafts_to_signed_in_users(): void
    {
        Page::factory()->draft()->create(['slug' => 'secret']);

        $this->actingAs(User::query()->create(['name' => 'Editor', 'email' => 'editor@example.com', 'password' => 'x']))
            ->get('/secret/')
            ->assertOk();
    }
}
