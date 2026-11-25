<?php

declare(strict_types=1);

namespace PrismCms\Tests\Body;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PrismCms\Body\BodySegment;
use PrismCms\Body\TokenRegistry;
use PrismCms\Models\Page;
use PrismCms\Tests\TestCase;

#[CoversClass(TokenRegistry::class)]
class TokenRegistryTest extends TestCase
{
    #[Test]
    public function it_escapes_plain_text_from_a_renderer(): void
    {
        $tokens = (new TokenRegistry)->register('note', fn (): string => '<b>hi</b>');

        $html = $tokens->render(new BodySegment(token: 'note'), new Page);

        $this->assertSame('&lt;b&gt;hi&lt;/b&gt;', $html);
    }

    #[Test]
    public function it_lists_registered_token_names(): void
    {
        $tokens = (new TokenRegistry)->register('packages', fn (): null => null)->register('projects', fn (): null => null);

        $this->assertSame(['packages', 'projects'], $tokens->names());
    }
}
