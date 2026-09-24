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
lower `position` (the cms rules sit at 1000 and 1100). The migration seeds the `entry` and `category` permissions and
the `author` role. Upgrading from 2.x: see `UPGRADE.md`.

## Configuration

### `modules.cms`

| Property | Default | Meaning |
|---|---|---|
| `categoryCachedQueryDuration` | `60` | seconds the category list is cached; `false` disables |
| `defaultEntryOrderBy` | `['position' => SORT_ASC]` | entry order when neither the type nor the category sets one |
| `defaultEntryType` | `null` | entry type the admin's default links filter by |
| `enableBlockAssets` | `true` | blocks have assets |
| `enableBlockEntries` | `true` | blocks link entries |
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
                SectionType::make(2)->name('Gallery')->hiddenFields('content'),
                BlockSectionType::make(3),
            ],
        ],
        EntryAsset::class => ['translatableAttributes' => ['alt_text']],
    ],
],
```

Every type takes `name()`, `plural()`, `viewFile()`, `cssClass()`, `customAttributes()` and `hiddenFields()`.
Per model:

- **`EntryType`**: `allowCategories()`, `allowSections()`, `allowDescendants()`, `allowAssets()`; `orderBy()` sets the
  index order and turns off manual ordering; `sort()`, `showCategories()`, `showCategoryDropdown()` configure the admin grid.
- **`SectionType`**: `allowBlock()` (off by default, needs `enableBlocks`), `allowEntries()`, `entriesTypes()`,
  `entriesOrderBy()`, `allowAssets()`; for `Widgets\SectionStack` on the site, `visible()`, `group()`, `wrapper()` and
  `collect()`; `gridContent()` replaces the name in the admin grid. `BlockSectionType` is a ready-made type whose sections
  only carry a block.
- **`CategoryType`**: `allowDescendants()`, `allowEntries()`.
- **`BlockType`**: `allowEntries()`, `entriesTypes()`, `entriesOrderBy()`, `allowAssets()`.

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

## Console commands

- `permalink/rebuild` — rewrites every entry's permalinks, inserting the missing ones and deleting those of entries that
  no longer have a URL (after adding a language, for instance)

## On the site

`Controllers\SiteController` resolves the slug to an entry, answers 404 for one without a URL, and renders the type's
`viewFile` or `view`, with the layout `main`. `Widgets\SectionStack::make()->entry($entry)` renders the visible
sections, grouped and wrapped as their types say; `Widgets\Artwork` and `Widgets\Gallery` render assets, and
`Widgets\MetaTags` the page's meta tags and hreflang links. Saving any cms record invalidates the page cache
(`Module::invalidatePageCache()`).
