<?php

declare(strict_types=1);

namespace PrismCms\Contracts;

/**
 * A user model with an admin role. Only admins may change the site's structure (system
 * templates, and moving or re-templating their pages). Users whose model doesn't implement this
 * are all treated as admins, as on a site without roles.
 */
interface HasAdminRole
{
    public function isAdmin(): bool;
}
