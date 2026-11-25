<?php

declare(strict_types=1);

namespace PrismCms\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PrismCms\Contracts\HasAdminRole;

class User extends Authenticatable implements FilamentUser, HasAdminRole
{
    protected $table = 'users';

    protected $guarded = [];

    public bool $admin = true;

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
