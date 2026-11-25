# Prism CMS core

The shared core of Prism CMS for Laravel 13 and Filament 5 sites: a page tree with catch-all
routing, self-describing page templates, a Markdown body with site-defined `[[tokens]]`, and a
Filament page editor.

Sites keep their own templates, views, CSS and content. Optional modules (chatbot, leads, and
others) are separate packages.

## Install

```bash
composer require adamhaley/prism-cms-core
php artisan migrate
```

The `pages` migration skips a `pages` table that already exists, so a site with its own table
keeps it unchanged.

Add the plugin to your Filament panel:

```php
use PrismCms\Filament\PrismCmsPlugin;

$panel->plugin(PrismCmsPlugin::make());
```

## Templates

Each page has a template. The site defines its templates as a backed enum implementing
`PrismCms\Contracts\PageTemplate`, and names it in `config/prism.php`
(`php artisan vendor:publish --tag=prism-config`):

```php
use PrismCms\Contracts\PageTemplate as PageTemplateContract;
use PrismCms\Enums\PageSection;
use PrismCms\Templates\DescribesPageTemplate;

enum PageTemplate: string implements PageTemplateContract
{
    use DescribesPageTemplate; // view `pages.{value}`, `home` is the home page, no extras

    case Home = 'home';
    case Standard = 'standard';

    public function getLabel(): string { /* ... */ }

    /** Which core form sections the page editor shows. */
    public function sections(): array
    {
        return match ($this) {
            self::Home => [PageSection::Hero],
            self::Standard => [PageSection::Hero, PageSection::HeroImage, PageSection::Content, PageSection::CallToAction],
        };
    }

    /** Only admins may apply, re-template or delete system templates. */
    public function isSystem(): bool
    {
        return $this === self::Home;
    }
}
```

Override `fields()` to add site form fields shown only for that template, and
`data(Page $page, Request $request)` to pass extra data to its view. Adding a template never
changes the package.

## Extending the Page model

Extend `PrismCms\Models\Page` to add the site's own columns (with the site's own migrations) and
relations, and set `prism.models.page` to your class. If you override `casts()`, merge
`parent::casts()`.

## Body tokens

A line holding only `[[name]]` or `[[name:argument]]` places whatever the site registered for
that token. Register renderers in a service provider:

```php
use PrismCms\Body\TokenRegistry;

app(TokenRegistry::class)->register('packages', fn (Page $page, ?string $argument) => view('partials.packages', ['page' => $page]));
```

`$page->bodyHtml()` renders the whole body (Markdown with raw HTML escaped, tokens replaced;
unknown tokens render nothing). Templates that lay segments out themselves use
`$page->bodySegments()`.

## Roles

If your user model implements `PrismCms\Contracts\HasAdminRole`, non-admins can't change the
site's structure. Without it every user is treated as an admin.

## Routes

The package registers `/` (`page.home`) and a fallback route (`page.show`) that resolves every
other URL to a page by its path. Drafts are visible only to signed-in users. Set
`prism.routes` to `false` to register `PrismCms\Http\Controllers\PageController` yourself.

## Development

Tests run against MySQL (database `prism_cms_core_test`, user `root`, no password):

```bash
composer test
```

Releases are cut by semantic-release from Conventional Commit titles on `main`. Versions come
from git tags; `composer.json` has no `version` field.

## License

MIT
