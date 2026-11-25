<?php

declare(strict_types=1);

namespace PrismCms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PrismCms\Models\Page;

/**
 * Resolves a URL to a page by its path and renders the page's template with the data the
 * template asks for. Drafts are visible only to signed-in users.
 */
class PageController
{
    public function __invoke(Request $request): Response
    {
        /** @var class-string<Page> $pageModel */
        $pageModel = config('prism.models.page');

        $page = $pageModel::query()
            ->where('path', trim($request->path(), '/'))
            ->when(! $request->user(), fn ($query) => $query->where('is_published', true))
            ->with('parent')
            ->firstOrFail();

        return response()->view($page->template->view(), [
            'page' => $page,
            ...$page->template->data($page, $request),
        ]);
    }
}
