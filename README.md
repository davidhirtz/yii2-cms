# yii2-cms

Content management for the [yii2-skeleton](https://github.com/davidhirtz/yii2-skeleton) admin: *entries* (pages,
articles, anything with a URL), optionally nested, put into *categories* and *menus*, and built from ordered *sections*;
*blocks* are sections shared between entries. Entries, sections and blocks carry assets from
[yii2-media](https://github.com/davidhirtz/yii2-media), and every entry belongs to a tenant of
[yii2-tenant](https://github.com/davidhirtz/yii2-tenant). The bundle ships the admin and a frontend controller; the site's
layout and views are the project's.

## Installation

```bash
composer require davidhirtz/yii2-cms
./yii migrate
./yii search/rebuild
```

The bundle bootstraps itself through `extra.bootstrap` (`Hirtz\Cms\Bootstrap`): it registers the `cms` module, the
`admin/cms` submodule, the `permalink` console command, the `@cms` alias, the `cms` message category, the block,
category, entry and section models plus their assets on the `search` component, the `BlockAsset`, `EntryAsset` and
`SectionAsset` classes on `modules.media.assets`, and two URL rules: `''` → `cms/site/index` and the catch-all
`<slug:.+>` → `cms/site/view`. The catch-all claims every path no other rule matches, so a project's own rules need a
lower `position` (the cms rules sit at 1000 and 1100). The migrations seed the `block`, `category` and `entry`
permissions, granted to `admin` and `manager`, and the `author` role, which holds those three, the media library's
`file` and `folder` and the skeleton's `redirect`. Upgrading from 2.x: see `UPGRADE.md`.

## Configuration

### `modules.cms`

| Property | Default | Meaning |
|---|---|---|
| `categoryCachedQueryDuration` | `60` | seconds the category list is cached; `false` disables |
| `defaultEntryOrderBy` | `['position' => SORT_ASC]` | entry order when neither the type nor the category sets one |
| `defaultEntryType` | `null` | entry type the admin's default links filter by |
| `enableBlockAssets` | `true` | blocks have assets |
| `enableBlockEntries` | `null` | blocks link entries, `null` follows `enableSectionEntries` |
| `enableBlocks` | `false` | sections may carry a shared block |
| `enableCategories` | `false` | entries are put into categories |
| `enableEntryAssets` | `true` | entries have assets |
| `enableNestedCategories` | `true` | categories form a tree |
| `enableNestedEntries` | `false` | entries form a tree |
| `enableSectionAssets` | `true` | sections have assets |
| `enableSectionEntries` | `false` | sections link entries |
| `enableSections` | `true` | entries have sections |
| `enableUrlRules` | `true` | register the two frontend URL rules |
| `entryIndexSlug` | `'home'` | slug of the top-level entry served at `/`; `false` serves nothing there |
| `entryRelations` | `BlockEntry`, `SectionEntry` | `Models\EntryRelation` subclasses, one per model that links entries |
| `inheritNestedCategories` | `true` | an entry is also in the ancestors of its categories |
| `menus` | `[]` | `Models\Menus\Menu` definitions, see below |
| `sectionSets` | `[]` | `Models\Sets\SectionSet` definitions, see below |

The flags cascade in `init()`: without sections there are no section assets and no blocks, without blocks no block
assets or block entries, without categories no nested categories, and without nested categories nothing is inherited.
A type only ever narrows what the module turned on.

### Types

Entries, sections, categories and blocks are typed. Their types are fluent definitions set on the container as a closure,
because a name is usually a `Yii::t()` result and the configuration is read before the application has an `i18n`
component:

```php
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Types\BlockSectionType;
use Hirtz\Cms\Models\Types\EntryType;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;

'container' => [
    'definitions' => [
        Entry::class => [
            'i18nAttributes' => ['name', 'slug'],
            'types' => fn (): array => [
                EntryType::make(1)->name('Page')->plural('Pages')->allowCategories(false),
                EntryType::make(2)->name('Article')->plural('Articles')
                    ->orderBy(['publish_date' => SORT_DESC])
                    ->viewFile('article')
                    ->customAttributes([TextCustomAttribute::make('author')]),
            ],
        ],
        Section::class => [
            'types' => fn (): array => [
                SectionType::make(1)->name('Text'),
                SectionType::make(2)->name('Gallery')->hiddenFields('content')->shareWithBlocks(false),
                BlockSectionType::make(Section::TYPE_BLOCK)->name('Shared block'),
            ],
        ],
        EntryAsset::class => ['translatableAttributes' => ['alt_text']],
    ],
],
```

An entry asset is the entry's preview image, so `EntryAsset` declares only the `alt_text`, `loading` and
`fetchpriority` custom attributes; section and block assets keep all of them. A project needing a caption or a link
on an entry asset overrides `getDefaultCustomAttributes()` in its own subclass.

Every type takes `name()`, `plural()`, `viewFile()`, `cssClass()`, `customAttributes()` and `hiddenFields()`.
Per model:

- **`EntryType`**: `allowCategories()`, `allowSections()`, `allowDescendants()`, `allowAssets()`; `orderBy()` sets the
  index order and turns off manual ordering; `sort()`, `showCategories()`, `showCategoryDropdown()` configure the admin grid.
- **`SectionType`**: `allowBlock()` (off by default, needs `enableBlocks`), `allowEntries()`, `entriesTypes()`,
  `entriesOrderBy()`, `allowAssets()`; for `Widgets\SectionStack` on the site, `visible()`, `group()`, `wrapper()` and
  `collect()`; `gridContent()` replaces the name in the admin grid. With `enableBlocks` every section type is a block
  type too (`shareWithBlocks(false)` keeps it to sections; one allowing a block or naming a `modelClass` never is), and
  a section of it can be converted into a block from its action dropdown. Its `available()` and `customAttributes()`
  closures then receive a `Block` as well, and its view a block whose `entry` is the entry it is rendered on. `BlockSectionType`, whose sections
  only carry a block, is added as `Section::TYPE_BLOCK` with `enableBlocks`, unless one is declared. It shows the
  status, the type and the block (`visibleFields()`) plus its own `customAttributes()`, and takes the `group()` of the
  block's type, or else that type's view file.
- **`CategoryType`**: `allowDescendants()`, `allowEntries()`.
- **`BlockType`**: `allowEntries()`, `entriesTypes()`, `entriesOrderBy()`, `allowAssets()`. The shared section types
  join the block types, a block type winning over a section type of its value; without block types configured, they
  replace the default one.

A block carries the custom attributes of the `Section` class the container resolves, its `slug` and `name` aside (the
block's `name` is a required column), and a block asset those of `SectionAsset`. A project's fields therefore need no
`Block` class of their own, but their closures receive a `Block` or a `BlockAsset` too: type them to what both share,
such as `Hirtz\Cms\Models\ActiveRecord` or `Hirtz\Media\Models\Asset`.

### Menus and section sets

Both are declared on the module, as closures for the same reason:

```php
use Hirtz\Cms\Models\Menus\Menu;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Cms\Models\Sets\SectionTemplate;

'modules' => [
    'cms' => [
        'enableCategories' => true,
        'menus' => fn (): array => [
            Menu::make(1)->name('Main menu'),
            Menu::make(2)->name('Legal')->available(fn (Entry $entry): bool => $entry->type === 1)->autoload(false),
        ],
        'sectionSets' => fn (): array => [
            SectionSet::make(1)->name('Landing page')->sections(
                SectionTemplate::make(1)->attribute('name', 'Intro'),
                SectionTemplate::make(2),
            ),
        ],
    ],
],
```

A menu an entry is put into is stored in `entry.menu_ids`; `Models\Collections\MenuCollection` loads every `autoload`
menu in one query. A section set creates its sections on an entry in one go and must name declared section types.
`available()` limits either to some entries; a menu an entry is already in stays valid.

### Setup

`./yii setup` inserts the categories of `config/setup.php` while there are none, then its entries while there are none,
and creates the default folder. Without the file, it inserts a homepage named after the application. The records are
attributed to `--user` (an email address), the owner by default; `--file` reads another file. An entry may carry
`sections`, `categories` (`EntryCategory` attributes) and child `entries`; a `type` picks the class. The entries
closure is called after the categories exist:

```php
return [
    'categories' => [
        ['name' => 'News', 'slug' => 'news'],
    ],
    'entries' => fn (): array => [
        ['name' => 'Home', 'slug' => 'home', 'title' => Yii::$app->name, 'sections' => [['type' => 1]]],
        ['name' => 'Imprint', 'menu_ids' => [2]],
    ],
];
```

The file is only needed until the first deploy has run it.

### Publishing

An enabled entry is on the site from its `publish_date`: the frontend's status query (`EntryQuery::whereStatus()`,
behind `enabled()`, the menus, the relations and the sitemap) leaves out one dated later, to the minute; the draft
domain previews it and the admin marks it *scheduled*. A type whose date means something else, an event's day, says
so with `EntryType::schedule(false)`: its entries are listed whatever their date, and the form drops the scheduling
hint. Unset, a type schedules unless its form hides the date (`hiddenFields('publish_date')`). A cached page
expires when the next scheduled entry goes live. An entry's children follow its `status` through `parent_status`, not
its date: the child of a scheduled entry is reachable at its own URL, which its form warns about
(`Modules\Admin\Widgets\ScheduledAncestorAlert`, `Entry::findScheduledAncestor()`).

### Admin

The aside lists every entry type under *Entries* when its item is told to, the type of the page being edited
marked active:

```php
'container' => ['definitions' => [
    CmsNavItem::class => ['showEntryTypes' => true],
]],
```

The entry index takes a `status` parameter (`admin/cms/entry/index?status=1`), which also disables sorting. The grid
offers no status dropdown by default; a project that wants one adds it to the header:

```php
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\StatusFilterDropdown;
use Hirtz\Skeleton\Widgets\Widget;
use yii\base\Event;

Event::on(EntryGridView::class, Widget::EVENT_CONFIGURE, function (Event $event): void {
    $event->sender->header(fn (array $header): array => [
        ...$header,
        StatusFilterDropdown::make()->model(Entry::instance()),
    ]);
});
```

## MCP

The bundle registers the [MCP](https://github.com/davidhirtz/yii2-skeleton#mcp-server) resources `entry` (optionally under a `parent`
entry, reorderable), `section` (created under its `entry`, reorderable), `category` (optionally under a `parent`,
reorderable), `block`, the assets `entry_asset`, `section_asset` and `block_asset`, with `enableCategories` the tool `assign_categories`, a paragraph of instructions on entries
and sections, and the prompts `getting_started` (what the connected user can do, in plain language), `translate_entry` and
`write_meta_descriptions`.

## Console commands

- `permalink/rebuild` — rewrites every entry's permalinks, inserting the missing ones and deleting those of entries that
  no longer have a URL (after adding a language, for instance)

## On the site

`Controllers\SiteController` resolves the slug to an entry, answers 404 for one without a URL, and renders the type's
`viewFile` or `view`, with the layout `main`. `Widgets\SectionStack::make()->entry($entry)` renders the visible
sections, grouped and wrapped as their types say; a section view reads content, fields, assets and entries off
`Section::getContentModel()`, the block a section carries or else the section, and the HTML id and admin link off the
section; `Widgets\Artwork` and `Widgets\Gallery` render assets, and
`Widgets\MetaTags` the page's meta tags, canonical link, hreflang links and its structured data (below). Saving any
cms record invalidates the page cache (`Module::invalidatePageCache()`).

### Tailwind

`Gallery`'s default viewports (`TailwindViewportsTrait`) are Tailwind classes. Tailwind scans no `vendor/`, so the
project's stylesheet imports them:

```css
@import "tailwindcss";
@import "../../vendor/davidhirtz/yii2-cms/resources/css/tailwind.css";
```

### Structured data

`MetaTags` adds schema.org nodes to the page's graph, which the head renders as one JSON-LD script: the skeleton's
`WebSite`, its `Organization` once configured, a `WebPage` (a category's is a `CollectionPage`) with the title,
description, share image and dates, and the ancestors of a nested entry or category as a `BreadcrumbList`.
`enableStructuredData(false)` turns it off.

```php
'container' => ['definitions' => [
    \Hirtz\Skeleton\Widgets\StructuredData\Organization::class => [
        'name' => 'Example GmbH',
        'logo' => '/images/logo.png',
        'sameAs' => ['https://www.instagram.com/example'],
    ],
]],
```

A type changes the page node with `structuredData()`. The closure gets the entry (or category) and the node
`MetaTags` derived, and answers the node changed, `null` for none, or a skeleton `Thing` that becomes the page's
`mainEntity`, taking the page's name, description, image and URL unless it sets its own. Specialised data is built
from the type's custom attributes; an event takes its day from `publish_date`, which it then does not schedule:

```php
use Hirtz\Skeleton\Models\CustomAttributes\DateTimeCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Widgets\StructuredData\Event;

EntryType::make(self::TYPE_EVENT)
    ->name('Event')
    ->schedule(false)
    ->customAttributes([
        DateTimeCustomAttribute::make('end_date'),
        TextCustomAttribute::make('venue'),
        TextCustomAttribute::make('address'),
    ])
    ->structuredData(fn (Entry $entry): Event => Event::make()
        ->startDate($entry->publish_date)
        ->endDate($entry->getVisibleAttribute('end_date'))
        ->location($entry->getVisibleAttribute('venue'), $entry->getVisibleAttribute('address'))),
```

An event without a start or a place is left out, so an incomplete entry never ships invalid data. Any other type is
a `Thing::make()->type('…')->properties([...])`; a closure that registers more nodes itself calls their `register()`.

A path with a trailing slash is redirected (301) to the one without; `SiteController::$redirectTrailingSlash = false`
serves the entry at both:

```php
'container' => ['definitions' => [
    \Hirtz\Cms\Controllers\SiteController::class => ['redirectTrailingSlash' => false],
]],
```

Nothing is added to the sitemap by itself: `Sitemap\EntrySitemap` and `Sitemap\CategorySitemap` are registered on the
skeleton's `sitemap` component.

```php
use Hirtz\Cms\Sitemap\CategorySitemap;
use Hirtz\Cms\Sitemap\EntrySitemap;

'components' => [
    'sitemap' => [
        'sitemaps' => [
            'entries' => ['class' => EntrySitemap::class, 'enableImages' => true],
            'categories' => CategorySitemap::class,
        ],
    ],
],
```
