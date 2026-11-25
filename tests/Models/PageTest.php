<?php

declare(strict_types=1);

namespace PrismCms\Tests\Models;

use Generator;
use Illuminate\Support\HtmlString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PrismCms\Body\BodySegment;
use PrismCms\Body\TokenRegistry;
use PrismCms\Models\Page;
use PrismCms\Tests\TestCase;

#[CoversClass(Page::class)]
class PageTest extends TestCase
{
    #[Test]
    public function it_gives_the_home_page_an_empty_path(): void
    {
        $home = Page::factory()->home()->create();

        $this->assertSame('', $home->path);
    }

    #[Test]
    public function it_derives_a_child_path_from_its_parent(): void
    {
        $parent = Page::factory()->create(['slug' => 'services']);

        $child = Page::factory()->for($parent, 'parent')->create(['slug' => 'websites']);

        $this->assertSame('services/websites', $child->path);
    }

    #[Test]
    public function it_updates_descendant_paths_when_a_slug_changes(): void
    {
        $parent = Page::factory()->create(['slug' => 'services']);
        $child = Page::factory()->for($parent, 'parent')->create(['slug' => 'websites']);
        $grandchild = Page::factory()->for($child, 'parent')->create(['slug' => 'shops']);

        $parent->update(['slug' => 'offers']);

        $this->assertSame('offers/websites/shops', $grandchild->fresh()->path);
    }

    #[Test]
    public function it_builds_urls_with_a_trailing_slash(): void
    {
        $page = Page::factory()->create(['slug' => 'about']);

        $this->assertSame('/about/', $page->url());
    }

    #[Test]
    public function it_lists_every_descendant_id(): void
    {
        $parent = Page::factory()->create();
        $child = Page::factory()->for($parent, 'parent')->create();
        $grandchild = Page::factory()->for($child, 'parent')->create();

        $this->assertSame([$child->id, $grandchild->id], $parent->descendantIds());
    }

    public static function bodySegmentsProvider(): Generator
    {
        yield 'empty body' => [null, [new BodySegment]];
        yield 'plain markdown' => ["Hello\n\nWorld", [new BodySegment(markdown: "Hello\n\nWorld")]];
        yield 'token between text' => [
            "Intro\n[[packages]]\nOutro",
            [new BodySegment(markdown: 'Intro'), new BodySegment(token: 'packages'), new BodySegment(markdown: 'Outro')],
        ];
        yield 'leading token keeps an empty first segment' => [
            "[[packages]]\nOutro",
            [new BodySegment, new BodySegment(token: 'packages'), new BodySegment(markdown: 'Outro')],
        ];
        yield 'token with an argument' => [
            '  [[projects:workflow]] ',
            [new BodySegment, new BodySegment(token: 'projects', argument: 'workflow')],
        ];
        yield 'token inside a sentence stays text' => [
            'See [[packages]] here',
            [new BodySegment(markdown: 'See [[packages]] here')],
        ];
    }

    /** @param list<BodySegment> $expected */
    #[Test]
    #[DataProvider('bodySegmentsProvider')]
    public function it_splits_the_body_at_token_lines(?string $body, array $expected): void
    {
        $page = new Page(['body' => $body]);

        $this->assertEquals($expected, $page->bodySegments());
    }

    #[Test]
    public function it_escapes_raw_html_in_the_body(): void
    {
        $page = new Page(['body' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>', $page->bodyHtml()->toHtml());
    }

    #[Test]
    public function it_renders_registered_tokens_in_the_body(): void
    {
        app(TokenRegistry::class)->register('packages', fn (Page $page, ?string $argument): HtmlString => new HtmlString("<ul data-type=\"{$argument}\"></ul>"));
        $page = new Page(['body' => "**Intro**\n[[packages:website]]"]);

        $this->assertSame("<p><strong>Intro</strong></p>\n<ul data-type=\"website\"></ul>", $page->bodyHtml()->toHtml());
    }

    #[Test]
    public function it_renders_unknown_tokens_as_nothing(): void
    {
        $page = new Page(['body' => "[[nonsense]]\nText"]);

        $this->assertSame("<p>Text</p>\n", $page->bodyHtml()->toHtml());
    }
}
