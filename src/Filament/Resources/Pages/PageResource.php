<?php

declare(strict_types=1);

namespace PrismCms\Filament\Resources\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use PrismCms\Body\TokenRegistry;
use PrismCms\Contracts\PageTemplate;
use PrismCms\Enums\ButtonStyle;
use PrismCms\Enums\PageSection;
use PrismCms\Filament\Resources\Pages\Pages\CreatePage;
use PrismCms\Filament\Resources\Pages\Pages\EditPage;
use PrismCms\Filament\Resources\Pages\Pages\ListPages;
use PrismCms\Filament\Resources\Pages\RelationManagers\ChildPagesRelationManager;
use PrismCms\Models\Page;
use PrismCms\Policies\PagePolicy;
use UnitEnum;

/**
 * The page editor. Which sections and fields it shows is up to the selected template
 * (PageTemplate::sections() and fields()), so a site adds templates without changing this form.
 */
class PageResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 10;

    /** @return class-string<Page> */
    public static function getModel(): string
    {
        return config('prism.models.page');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Page')->columns(2)->schema([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->alphaDash()
                    ->prefix(fn (Get $get): string => self::getModel()::query()->find($get('parent_id'))?->url() ?? '/')
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('parent_id', $get('parent_id')))
                    ->hidden(fn (Get $get): bool => (bool) self::template($get)?->isHome())
                    ->disabled(fn (?Page $record): bool => ! self::canChangeStructure($record)),
                Select::make('parent_id')
                    ->label('Parent page')
                    ->options(fn (?Page $record): array => self::getModel()::query()
                        ->whereNotIn('id', $record ? [$record->id, ...$record->descendantIds()] : [])
                        ->where('path', '!=', '')
                        ->orderBy('path')
                        ->pluck('title', 'id')
                        ->all())
                    ->placeholder('None (top level)')
                    ->live()
                    ->hidden(fn (Get $get): bool => (bool) self::template($get)?->isHome())
                    ->disabled(fn (?Page $record): bool => ! self::canChangeStructure($record)),
                Select::make('template')
                    ->options(fn (?Page $record): array => self::templateOptions($record))
                    ->required()
                    ->live()
                    ->disabled(fn (?Page $record): bool => ! self::canChangeStructure($record)),
                Toggle::make('is_published')
                    ->label('Published')
                    ->default(true)
                    ->helperText('Unpublished pages are only visible while logged in to the admin.'),
                Toggle::make('show_in_nav')
                    ->label('Show in main navigation')
                    ->helperText('Top-level pages only; child pages appear in their parent\'s dropdown.'),
            ]),

            Section::make('Hero')
                ->visible(fn (Get $get): bool => self::uses($get, PageSection::Hero))
                ->schema([
                    TextInput::make('hero_heading')->label('Heading')->placeholder('Defaults to the page title'),
                    TextInput::make('hero_subheading')->label('Subheading'),
                    self::imageUpload('hero_image')
                        ->label('Hero image')
                        ->visible(fn (Get $get): bool => self::uses($get, PageSection::HeroImage)),
                ]),

            Section::make('Content')
                ->visible(fn (Get $get): bool => self::uses($get, PageSection::Content))
                ->schema([
                    TextInput::make('section_eyebrow')->label('Eyebrow'),
                    TextInput::make('section_heading')->label('Heading'),
                    MarkdownEditor::make('body')
                        ->helperText(fn (): string => self::bodyHelp())
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('pages'),
                    Repeater::make('bullets')
                        ->simple(TextInput::make('bullet')->required())
                        ->reorderable()
                        ->defaultItems(0)
                        ->visible(fn (Get $get): bool => self::uses($get, PageSection::Bullets)),
                    Repeater::make('buttons')
                        ->schema([
                            TextInput::make('label')->required(),
                            TextInput::make('url')->label('Link')->required()->placeholder('/services/ or /contact/'),
                            Select::make('style')->options(ButtonStyle::class)->default(ButtonStyle::Secondary->value)->required(),
                        ])
                        ->columns(3)
                        ->reorderable()
                        ->defaultItems(0)
                        ->visible(fn (Get $get): bool => self::uses($get, PageSection::Buttons)),
                    Group::make([
                        self::imageUpload('circle_image')->label('Circle image'),
                        TextInput::make('circle_image_alt')->label('Circle image alt text'),
                    ])->visible(fn (Get $get): bool => self::uses($get, PageSection::CircleImage)),
                ]),

            Section::make('Card')
                ->description('How this page appears as a card in its parent\'s grid.')
                ->visible(fn (Get $get): bool => self::uses($get, PageSection::Card))
                ->schema([
                    Textarea::make('card_excerpt')->label('Card text')->rows(2),
                    self::imageUpload('card_image')
                        ->label('Card image')
                        ->helperText('Shown at the top of the card. Leave empty to use the hero image.')
                        ->imageEditorAspectRatios(['4:3']),
                ]),

            ...self::templateFields(),

            Section::make('Call to action')
                ->description('The banner at the bottom of the page. Leave the heading empty to hide it.')
                ->visible(fn (Get $get): bool => self::uses($get, PageSection::CallToAction))
                ->columns(3)
                ->schema([
                    TextInput::make('cta_eyebrow')->label('Eyebrow'),
                    TextInput::make('cta_heading')->label('Heading'),
                    TextInput::make('cta_button_label')->label('Button label'),
                ]),

            Section::make('Search engines')->collapsed()->schema([
                TextInput::make('meta_title')->placeholder(fn (): string => 'Defaults to "Page title | '.config('app.name').'"'),
                Textarea::make('meta_description')->rows(2)->maxLength(500),
                self::imageUpload('og_image')
                    ->label('Social sharing image')
                    ->helperText('Shown when this page is shared on Facebook, LinkedIn, etc. Leave empty to use the hero image.')
                    ->imageEditorAspectRatios(['1200:630']),
                TextInput::make('nav_anchor')
                    ->label('Homepage navigation anchor')
                    ->helperText('When set, this page\'s nav link jumps to that homepage section (e.g. #about) instead of opening the page.')
                    ->visible(fn (): bool => PagePolicy::isAdmin(auth()->user())),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('path')
            ->persistFiltersInSession()
            ->deferFilters(false)
            ->columns([
                TextColumn::make('title')
                    ->description(fn (Page $record): string => $record->url())
                    ->searchable(),
                TextColumn::make('template')->badge(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->falseIcon(Heroicon::OutlinedMinusCircle)
                    ->falseColor('gray'),
                IconColumn::make('show_in_nav')
                    ->label('In nav')
                    ->boolean()
                    ->falseIcon(Heroicon::OutlinedMinusCircle)
                    ->falseColor('gray'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('template')->options(config('prism.page_template')),
                TernaryFilter::make('is_published')->label('Published'),
            ])
            ->recordActions([
                // Not named 'view': Filament's ListRecords wires an action named 'view' or 'edit'
                // that has a url() up as the row click, which would open the live page.
                Action::make('viewLive')
                    ->label('View')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Page $record): string => $record->url(), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->recordAction('edit');
    }

    public static function getRelations(): array
    {
        return [
            ChildPagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    private static function imageUpload(string $name): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->imageEditor()
            ->disk('public')
            ->directory('pages')
            ->visibility('public');
    }

    /** @return class-string<PageTemplate> */
    private static function templates(): string
    {
        return config('prism.page_template');
    }

    private static function template(Get $get): ?PageTemplate
    {
        $template = $get('template');

        return $template instanceof PageTemplate ? $template : self::templates()::tryFrom((string) $template);
    }

    private static function uses(Get $get, PageSection $section): bool
    {
        return in_array($section, self::template($get)?->sections() ?? [], true);
    }

    /**
     * Each template's own fields, shown only while that template is selected.
     *
     * @return list<Group>
     */
    private static function templateFields(): array
    {
        return collect(self::templates()::cases())
            ->filter(fn (PageTemplate $template): bool => $template->fields() !== [])
            ->map(fn (PageTemplate $template): Group => Group::make($template->fields())
                ->visible(fn (Get $get): bool => self::template($get) === $template))
            ->values()
            ->all();
    }

    private static function bodyHelp(): string
    {
        $help = 'Markdown: ## headings, **bold**, lists, links, tables and images.';
        $tokens = app(TokenRegistry::class)->names();

        return $tokens === []
            ? $help
            : $help.' To place a section in the body, put its token on a line of its own: '
                .collect($tokens)->map(fn (string $token): string => "[[{$token}]]")->implode(', ').'.';
    }

    private static function canChangeStructure(?Page $record): bool
    {
        return $record === null || Gate::allows('changeStructure', $record);
    }

    /**
     * Ordinary templates, the record's current one, and for admins the system templates no other
     * page uses yet.
     *
     * @return array<string, string>
     */
    private static function templateOptions(?Page $record): array
    {
        $usedSystemTemplates = self::getModel()::query()
            ->when($record, fn ($query) => $query->whereKeyNot($record->id))
            ->pluck('template')
            ->filter(fn (PageTemplate $template): bool => $template->isSystem());

        return collect(self::templates()::cases())
            ->filter(fn (PageTemplate $template): bool => $template === $record?->template
                || ! $template->isSystem()
                || (PagePolicy::isAdmin(auth()->user()) && ! $usedSystemTemplates->contains($template)))
            ->mapWithKeys(fn (PageTemplate $template): array => [$template->value => $template->getLabel()])
            ->all();
    }
}
