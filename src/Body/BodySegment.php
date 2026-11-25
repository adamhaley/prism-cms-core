<?php

declare(strict_types=1);

namespace PrismCms\Body;

/**
 * One piece of a page body split at its tokens (Page::bodySegments()): either Markdown, or a
 * `[[token]]` / `[[token:argument]]` line naming what the site places there.
 */
final readonly class BodySegment
{
    public function __construct(
        public string $markdown = '',
        public ?string $token = null,
        public ?string $argument = null,
    ) {}

    public function isToken(): bool
    {
        return $this->token !== null;
    }
}
