<?php

declare(strict_types=1);

namespace PrismCms\Tests\Policies;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PrismCms\Models\Page;
use PrismCms\Policies\PagePolicy;
use PrismCms\Tests\Fixtures\PageTemplate;
use PrismCms\Tests\Fixtures\User;
use PrismCms\Tests\TestCase;

#[CoversClass(PagePolicy::class)]
class PagePolicyTest extends TestCase
{
    public static function structureProvider(): Generator
    {
        yield 'admin, system template' => [true, PageTemplate::Contact, true];
        yield 'editor, system template' => [false, PageTemplate::Contact, false];
        yield 'editor, ordinary template' => [false, PageTemplate::Standard, true];
    }

    #[Test]
    #[DataProvider('structureProvider')]
    public function it_lets_only_admins_change_system_pages(bool $admin, PageTemplate $template, bool $expected): void
    {
        $user = new User;
        $user->admin = $admin;

        $this->assertSame($expected, (new PagePolicy)->changeStructure($user, new Page(['template' => $template])));
    }

    #[Test]
    public function it_refuses_to_delete_a_page_with_children(): void
    {
        $parent = Page::factory()->create();
        Page::factory()->for($parent, 'parent')->create();

        $this->assertFalse((new PagePolicy)->delete(new User, $parent));
    }
}
