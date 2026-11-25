<?php

declare(strict_types=1);

namespace PrismCms\Enums;

/**
 * The core page form sections and fields a template can use. The page editor shows only the
 * ones the current template lists in sections(). Title, slug, template, publishing and the
 * search engine fields are always shown.
 */
enum PageSection: string
{
    case Hero = 'hero';
    case HeroImage = 'hero_image';
    case Content = 'content';
    case Bullets = 'bullets';
    case Buttons = 'buttons';
    case CircleImage = 'circle_image';
    case Card = 'card';
    case CallToAction = 'call_to_action';
}
