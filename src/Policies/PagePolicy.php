<?php

declare(strict_types=1);

namespace PrismCms\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use PrismCms\Contracts\HasAdminRole;
use PrismCms\Models\Page;

class PagePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, Page $page): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Page $page): bool
    {
        return true;
    }

    public function delete(Authenticatable $user, Page $page): bool
    {
        return ! $page->children()->exists() && $this->changeStructure($user, $page);
    }

    public function changeStructure(Authenticatable $user, Page $page): bool
    {
        return self::isAdmin($user) || ! $page->template->isSystem();
    }

    public static function isAdmin(?Authenticatable $user): bool
    {
        return ! $user instanceof HasAdminRole || $user->isAdmin();
    }
}
