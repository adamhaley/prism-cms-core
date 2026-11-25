<?php

declare(strict_types=1);

namespace PrismCms\Enums\Concerns;

/**
 * Labels for Prism CMS's own enums, from the package's `prism::enums` translations.
 */
trait TranslatesLabels
{
    public function getLabel(): string
    {
        return trans('prism::enums.'.static::class.'.'.$this->value);
    }
}
