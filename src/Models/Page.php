<?php

declare(strict_types=1);

namespace PrismCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use PrismCms\Body\BodySegment;
use PrismCms\Body\TokenRegistry;
use PrismCms\Contracts\PageTemplate;
use PrismCms\Database\Factories\PageFactory;

/**
 * A page in the site's tree. Its `path` is derived from its parent's path and its slug on save,
 * and kept up to date down the tree.
 *
 * Sites extend this model to add their own columns and relations, and name the subclass in the
 * `prism.models.page` config. A subclass that defines casts() should merge parent::casts().
 *
 * @property int $id
 * @property ?int $parent_id
 * @property string $title
 * @property ?string $slug
 * @property string $path
 * @property PageTemplate $template
 * @property ?string $body
 * @property list<string>|null $bullets
 * @property list<array{label: string, url: string, style: string}>|null $buttons
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    /**
     * A line holding only a token places what the site registered for it in the body:
     * `[[packages]]`, or `[[projects:workflow]]` with an argument.
     */
    public const string TokenPattern = '/^[ \t]*\[\[([a-z][a-z0-9_-]*)(?::([a-z0-9_-]+))?\]\][ \t]*$/m';

    protected $table = 'pages';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'template' => config('prism.page_template'),
            'bullets' => 'array',
            'buttons' => 'array',
            'is_published' => 'boolean',
            'show_in_nav' => 'boolean',
        ];
    }

    /** Uses boot() rather than booted() so a subclass's booted() doesn't replace the path upkeep. */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Page $page): void {
            $page->path = $page->template->isHome()
                ? ''
                : ltrim(($page->parent?->path ?? '').'/'.$page->slug, '/');
        });

        static::saved(function (Page $page): void {
            if ($page->wasChanged('path')) {
                $page->children->each(fn (Page $child): bool => $child->setRelation('parent', $page)->save());
            }
        });
    }

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }

    /** @return BelongsTo<static, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /** @return HasMany<static, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('sort_order');
    }

    public function url(): string
    {
        return $this->path === '' ? '/' : '/'.$this->path.'/';
    }

    /** @return list<int> */
    public function descendantIds(): array
    {
        return $this->children->flatMap(fn (Page $child): array => [$child->id, ...$child->descendantIds()])->all();
    }

    /**
     * The body split at its token lines. The first segment is always Markdown, possibly empty,
     * so a template can always put something before the first token.
     *
     * @return list<BodySegment>
     */
    public function bodySegments(): array
    {
        $body = (string) $this->body;
        preg_match_all(self::TokenPattern, $body, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $segments = [];
        $offset = 0;
        foreach ($matches as $match) {
            [$line, $lineOffset] = $match[0];
            $segments[] = new BodySegment(markdown: trim(substr($body, $offset, $lineOffset - $offset)));
            $segments[] = new BodySegment(token: $match[1][0], argument: $match[2][0] ?? null);
            $offset = $lineOffset + strlen($line);
        }
        $segments[] = new BodySegment(markdown: trim(substr($body, $offset)));

        return array_values(array_filter(
            $segments,
            fn (BodySegment $segment, int $index): bool => $index === 0 || $segment->isToken() || $segment->markdown !== '',
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /**
     * The whole body as HTML: Markdown with raw HTML escaped and unsafe links dropped, and each
     * token replaced by what the site registered for it.
     */
    public function bodyHtml(): HtmlString
    {
        $tokens = app(TokenRegistry::class);

        return new HtmlString(collect($this->bodySegments())
            ->map(fn (BodySegment $segment): string => $segment->isToken()
                ? $tokens->render($segment, $this)
                : Str::markdown($segment->markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]))
            ->implode(''));
    }
}
