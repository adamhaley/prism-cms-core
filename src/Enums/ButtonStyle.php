<?php

declare(strict_types=1);

namespace PrismCms\Enums;

use Filament\Support\Contracts\HasLabel;
use PrismCms\Enums\Concerns\TranslatesLabels;

enum ButtonStyle: string implements HasLabel
{
    use TranslatesLabels;

    case Primary = 'primary';
    case Secondary = 'secondary';
}
