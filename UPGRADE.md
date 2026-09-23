# Upgrade Guide

## 3.0 — A page's title stays on the record that owns it

A section page is titled with its **entry** and says "Section #3" beneath; a section's asset adds
"Asset #1" to that same line, each item linking to its own page. Which record owns the title is
`Skeleton\Models\Interfaces\AdminModelInterface::getAdminSubtitle()` — a record that answers one is edited
*through* another — and `Modules\Admin\Widgets\Navs\Traits\EntryHeaderTrait` is deleted, no header knowing
another header's class any more.

A project view rendering an asset page passes the **asset** to the media `AssetHeader`, not its owner to the
owner's header, and gives it the nearest frontend URL as its subheading:

```php
echo AssetHeader::make()
    ->model($asset)
    ->subheading(FrontendLink::findInChain($asset)?->addClass('hidden-sticky'))
    ->content(AssetActionDropdown::make()->model($asset));

echo SectionSubmenu::make()
    ->model($asset->model);
```

A project header extending `EntryHeader`, `SectionHeader`, `BlockHeader` or `CategoryHeader` that called
`addEntryBreadcrumbs()`, `addSectionBreadcrumbs()`, `addCategoryBreadcrumbs()` or `addEntriesBreadcrumb()`
drops the call; a project model with a page of its own declares `getAdminIndexBreadcrumb()` instead, and one
edited through another declares `getAdminParent()` and `getAdminSubtitle()` as well. A project subclass of
`SectionSubmenu` that overrode `getSectionsItem()` drops it — a submenu holds one record's views.

## 3.0 — `Widgets\AdminLink` moved to `yii2-skeleton`

The frontend overlay link is `Hirtz\Skeleton\Widgets\AdminLink` — it never referenced a cms class, and the CSS
it renders into belongs to the skeleton's `Widgets\Buttons\AdminButton`. Rewrite the `use` statement; the
`AdminLink::tag($model)` calls are unchanged. Its default class lost `overlay`; see
`bundles/yii2-skeleton/UPGRADE.md` for what that costs a project that styled it.

**A project overriding `resources/views/site/_sections.php` has to add `relative` to its `<section>` itself.**
The shipped view names it now, because the overlay is absolutely positioned and fills the nearest positioned
ancestor; the class is the project's to define, through Tailwind or a rule of its own. Almost every project
overrides this view, so the shipped change does not reach them — and the symptom is an overlay over the whole
viewport rather than over the section.

It also **links a section, an entry and a category again.** It asked `method_exists($model,
'getPermissionName')` and rendered nothing when that was false, which was every cms model but the asset, so the
overlay had quietly survived on assets alone. `Models\Entry`, `Category` and `Block` answer their own `AUTH_*`
constant now, `Section` and `EntryCategory` answer `Entry::AUTH_ENTRY`.

## 3.0 — Global sections

Nothing changes for an installation that leaves `modules.cms.enableBlocks` off (the default). To turn the
feature on:

```php
'modules' => [
    'cms' => [
        'enableBlocks' => true,
    ],
],
```

and give the section type family a type that carries a block:

```php
'container' => [
    'definitions' => [
        Section::class => [
            'types' => fn (): array => [
                SectionType::make(Section::TYPE_DEFAULT)->name(Yii::t('app', 'Text')),
                BlockSectionType::make(2),
            ],
        ],
    ],
],
```

`BlockSectionType` allows the block and nothing else — no assets, no linked entries, no `name`, `content` or
`slug` — which is what makes the section a placeholder. A type of a project's own opts in with
`SectionType::allowBlock()` instead; the default is `false` on both the module and the type, so no existing
type grows a block field.

A view that renders sections needs no change as long as it reads `getVisibleAssets()` and the new
`getVisibleEntries()`: a section carrying a block answers with the block's, and `Widgets\SectionStack` renders
the block's `viewFile`. **A view reading `$section->assets` or `$section->entries` directly does need the
change**, or a block section renders the section's own (empty) relations. Content is the one thing the section
cannot delegate through a magic property — read it off `$section->getVisibleBlock() ?? $section`, as the
bundle's own `resources/views/site/_sections.php` does.

`Block::AUTH_BLOCK` is granted to `admin` and `manager` by `Migrations\M260916110000Block`. A project that
wants its editors to manage blocks adds it to `author` itself.

A model extending `Models\ActiveRecord` may now omit the `position` column — `setDefaultPosition()` returns
early when there is none, rather than throwing on an unknown attribute. `Models\Block` is the first to do it.

## 3.0 — The section-entry link is polymorphic

`section_entry` is `entry_relation` (monorepo issue #109). The table is keyed by `model_class` / `model_id`, so a
model other than a section can link entries through it — which is what the block feature needs.
`Migrations\M260916100000EntryRelation` renames the table and the column, fills `model_class` and replaces the
index; nothing has to be migrated by hand.

`model_id` points at more than one table, so it carries **no foreign key**. A model that links entries deletes its
own rows: `Models\Traits\EntryRelationModelTrait::deleteEntryRelations()` does it from `beforeDelete()`, the way
`Media\Models\Folder` deletes its files.

What a project renames:

| was                                          | is                                                   |
|----------------------------------------------|------------------------------------------------------|
| `$section->sectionEntries`                   | `$section->entryRelations`                            |
| `$entry->sectionEntry`                       | `$entry->entryRelation`                               |
| `SectionEntry::populateSectionRelation()`    | `EntryRelation::populateModelRelation()`              |
| `Models\Traits\SectionRelationTrait`        | — (the owner is polymorphic)                          |
| `EntryQuery::whereSection()`                 | `EntryQuery::whereRelatedModel()`                     |
| `EntryActiveDataProvider::$section`          | `$relatedModel`                                       |
| `EntryActiveDataProvider::$innerJoinSection` | `$innerJoinRelatedModel`                              |
| `Models\Actions\ReorderSectionEntries`      | `ReorderEntryRelations`                               |
| `Grids\SectionEntryGridView`                 | `Grids\EntryRelationGridView`                         |
| `Grids\SectionLinkedEntryGridView`           | `Grids\LinkedEntryGridView`                           |
| `Grids\Columns\SectionEntryCountColumn`      | `Grids\Columns\EntryRelationCountColumn`              |
| `Buttons\SectionEntryCreateButton`           | `Buttons\EntryRelationCreateButton`                   |

`SectionEntryController` keeps its route and its access rule, and moves onto
`Modules\Admin\Controllers\Traits\EntryRelationControllerTrait`; a subclass overriding one of its actions
follows the trait's `renderEntryRelationIndex()`, `createEntryRelation()`, `deleteEntryRelation()` and
`reorderEntryRelations()`. The reorder body parameter is `entry-relation`, since `EntryRelation::formName()` is
what names it.

`SectionType`'s `allowEntries()`, `entriesTypes()` and `entriesOrderBy()` moved into
`Models\Types\Traits\EntryRelationTypeTrait` behind `Models\Interfaces\EntryRelationTypeInterface`. The
declarations are unchanged; a type class of a project's own that redeclared them can drop them.

The message keys `SECTION_ENTRY_ADD_TO_SECTION`, `SECTION_ENTRY_CREATE_BUTTON`, `SECTION_ENTRY_ENTRY_ID_LABEL`,
`SECTION_ENTRY_GRID_SUMMARY_EMPTY`, `SECTION_ENTRY_REMOVE_TITLE`, `SECTION_ENTRY_SECTION_ID_LABEL`,
`SECTION_ENTRY_SUCCESS_ADDED`, `SECTION_ENTRY_SUCCESS_REMOVED`, `SECTION_ENTRY_UPDATED_AT_LABEL` and
`REORDER_SECTION_ENTRIES_LINKED` are replaced by `ENTRY_RELATION_*` and `REORDER_ENTRY_RELATIONS_LINKED`, whose
copy names no model.

## 3.0 — The entry site preloader is renamed

`Models\Builders\EntrySiteRelationsBuilder` is `Models\Actions\PreloadEntrySiteRelations` (monorepo issue #136),
and `Models\Builders\` is gone. The class builds nothing — it loads everything an entry's site view needs in one
pass and populates the relations — so it belongs with the other verb-first classes in `Models\Actions\`.

| was                                                      | is                                                       |
|----------------------------------------------------------|----------------------------------------------------------|
| `Hirtz\Cms\Models\Builders\EntrySiteRelationsBuilder`      | `Hirtz\Cms\Models\Actions\PreloadEntrySiteRelations`      |
| `Hirtz\Cms\Models\Events\EntrySiteRelationsBuilderEvent`   | `Hirtz\Cms\Models\Events\EntrySiteRelationsEvent`         |
| `Cms\Hotspot\Events\HotspotEntrySiteRelationsBuilderEventHandler` | `Cms\Hotspot\Events\HotspotEntrySiteRelationsEventHandler` |
| `Cms\Shopify\Events\ProductEntrySiteRelationsBuilderEventHandler` | `Cms\Shopify\Events\ProductEntrySiteRelationsEventHandler` |

The event drops the sender's name on purpose, so the next rename of the class leaves every subscriber alone.

**Nothing else changes.** The three `EVENT_AFTER_LOAD_*` constants keep their names and their values, the public
`$entry`, `$assets`, `$entries`, `$files`, `$fileIds` and `$autoloadEntryAncestors` properties are unchanged, and
the work still happens in `init()` — so a project only rewrites the class names it spells out itself: an
`Event::on()` or `EventHelper::on()` call, a `Yii::$container` definition or a subclass.

```php
// was
Event::on(
    EntrySiteRelationsBuilder::class,
    EntrySiteRelationsBuilder::EVENT_AFTER_LOAD_ENTRIES,
    new MyEntrySiteRelationsBuilderEventHandler(),
);

// is
Event::on(
    PreloadEntrySiteRelations::class,
    PreloadEntrySiteRelations::EVENT_AFTER_LOAD_ENTRIES,
    new MyEntrySiteRelationsEventHandler(),
);
```

## 3.0 — The entry form's tenant row moved into the declaration

`Modules\Admin\Widgets\Forms\EntryActiveForm::getRowsAsGroups()` is gone with the ambiguous row shape it existed
for (see the skeleton's `Widgets\Forms\ActiveForm`). The tenant row used to be spliced into `$rows` after the
defaults, whatever the caller had passed; it is part of `getDefaultRows()` now.

**So a caller replacing the entry form's rows wholesale owns the tenant field too.** With more than one tenant the
field is what decides which parents and which URL the rest of the form shows, so a replacement that drops it leaves
the form unable to switch tenant:

```php
EntryActiveForm::make()
    ->model($entry)
    ->rows([
        [$form->getTenantIdField()],
        [...],
    ]);
```

Adding to the form rather than replacing it — `Widget::EVENT_CONFIGURE`, or `prepare()` — is unaffected: both run
after the defaults, so the tenant row is already there.

`EntryActiveForm`, `CategoryActiveForm` and `SectionActiveForm` declare their fields in `getDefaultRows()` now. A
subclass overriding `configure()` to change the fields moves to that hook.

## 3.0 — One vocabulary for what a type has

Three mechanisms answered the same question. The module's flag reached `Entry::hasAssetsEnabled()` and its kind; a
type's `hiddenFields(Entry::FIELD_ASSETS)` reached whichever caller remembered to ask `isAttributeVisible()`; and
`EntryType::showsCategories()` reached one grid. So the frontend hid the assets of a type that declared none while
the admin still accepted them, and a route stayed open behind a submenu tab that was gone.

**The model is the single reader**, and it resolves all three: the installation's flag, the type's declaration, and
whatever the record itself says. Nothing else has to consult a type.

### Renames

| Before | After |
|---|---|
| `Entry::hasAssetsEnabled()` | `Entry::allowsAssets()` |
| `Entry::hasCategoriesEnabled()` | `Entry::allowsCategories()` |
| `Entry::hasSectionsEnabled()` | `Entry::allowsSections()` |
| `Entry::hasDescendantsEnabled()` | `Entry::allowsDescendants()` |
| `Entry::hasParentEnabled()` | `Entry::allowsParent()` |
| `Section::hasAssetsEnabled()` | `Section::allowsAssets()` |
| `Section::hasEntriesEnabled()` | `Section::allowsEntries()` |
| `Category::hasDescendantsEnabled()` | `Category::allowsDescendants()` |
| `Category::hasEntriesEnabled()` | `Category::allowsEntries()` |
| `Category::hasParentEnabled()` | `Category::allowsParent()` |

`EntryType::showsCategories()` and `showsCategoryDropdown()` keep their names. They are tri-state *grid* settings
with a default of their own to fall through to, where an `allow*()` is a plain `bool` — a type narrows what the
installation turned on and can never widen it.

### The markers are methods

```php
// before
EntryType::make(2)->hiddenFields('content', Entry::FIELD_ASSETS);
SectionType::make(2)->hiddenFields(Section::FIELD_ENTRIES);

// after
EntryType::make(2)->hiddenFields('content')->allowAssets(false);
SectionType::make(2)->allowEntries(false);
```

`Section::FIELD_ENTRIES` and `Media\Models\Interfaces\AssetModelInterface::FIELD_ASSETS` are gone.
`hiddenFields()` is a list of **attribute** names again — `parent_id` still belongs there, which is why
`allowsParent()` has no `allowParent()` on the type to go with it.

### A project's own per-type flag

Subclass the type and the model, which is how a project extends here anyway — it declares its types in the
container and re-points `Entry::class`:

```php
class EntryType extends \Hirtz\Cms\Models\Types\EntryType
{
    protected bool $allowsNewsletter = true;

    public function allowNewsletter(bool $allowNewsletter = true): static
    {
        $this->allowsNewsletter = $allowNewsletter;
        return $this;
    }

    public function allowsNewsletter(): bool
    {
        return $this->allowsNewsletter;
    }
}

class Entry extends \Hirtz\Cms\Models\Entry
{
    public function allowsNewsletter(): bool
    {
        return $this->getType()?->allowsNewsletter() ?? true;
    }
}
```

Then the submenu item is `->visible($entry->allowsNewsletter())` and the controller behind it answers
`isEntryAllowed()` with the same call. Where the type class belongs to another bundle and cannot be subclassed,
`hiddenFields()` with a marker of your own is still the way — the hotspot bundle does exactly that for
`Cms\Hotspot\Module::FIELD_HOTSPOTS`.

### The site honours it too

`Widgets\SectionStack` read `$entry->sections` directly, so a type declaring no sections still rendered them — only
the asset side went through a `getVisible*()`. The stack defaults to `Entry::getVisibleSections()` now. A project
that hands it a list with `sections()` decides for itself, as before.

### A route refuses what the admin does not offer

`Modules\Admin\Controllers\SectionController` and `EntryCategoryController` now answer `404` for an entry whose
type has no sections or no categories, through `Traits\EntryControllerTrait::isEntryAllowed()`. The module flags
were not checked there either, so `enableSections => false` used to leave the section routes open. A project with a
controller of its own overrides the hook.

`Traits\SectionControllerTrait::isSectionAllowed()` is the same hook one level down. `SectionEntryController`
answers it with `Section::allowsEntries()` — with `enableSectionEntries` defaulting to `false`, that controller's
routes were reachable on every default installation — and `SectionController::actionEntries()`, the picker behind
the same tab, refuses it too.

**Record-scoped actions stay open on purpose.** `SectionController::actionUpdate()` and `actionDelete()` still reach
a section whose entry's type has since stopped allowing sections, the same exemption `Type::isAvailableOrStored()`
makes for a stored value: a record the configuration no longer allows must stay deletable, or it is invisible *and*
immortal. `Section::validateEntryId()` refuses the save anyway. The media bundle's `findAsset()` works the same way,
where `findAssetModel()` is the gate.

## 3.0 — The tenant select reloads the page

`Assets\TenantDropdownAssetBundle` is gone, with the `resources/assets` tree behind it — the cms ships no
JavaScript at all now. `Modules\Admin\Widgets\Forms\Fields\TenantIdField` uses
`Skeleton\Widgets\Forms\Fields\Field::reloadsForm()` instead, so a tenant change re-renders the page rather than
fetching it and replacing the parent select's `innerHTML`. The slug field's host follows the tenant now, which it
never did.

A project that subclassed the field to point `registerClientScript()` at a bundle of its own drops the override.
The field no longer writes `data-id="tenant"` on the select or a `data-value` per option, and
`Modules\Admin\Widgets\Forms\Fields\EntryParentIdSelectField` renders nothing where it used to render a hidden
row for that script to fill — a stylesheet or test keyed on either changes.

**`EntryActiveForm::setTenantFromRequest()` is `setTenant()`, and the record wins over the request.** An entry
belonging to one tenant, opened on the admin host of another, was silently re-tenanted by the form before it
rendered; the request is now only what seeds a *new* entry. A subclass overriding the old name has to be renamed,
or its tenant is never applied.

## 3.0.0 — Entry menus replace `show_in_menu` and `show_in_footer`

Two checkboxes could never answer the question a real project asks. Every installation that needed a third
navigation — a copyright row, the left and the right half of a header — had to add a column of its own, and the
two that shipped cost two columns where one does the job.

`entry.menu_ids` is that one column: a JSON list of the menus an entry is in, added by
`Migrations\M260915200000MenuIds`, which also **upgrades a v2 installation in place**. Where the project had
`show_in_menu`, its entries land in menu `1`; where it had `show_in_footer`, in menu `2`; and both columns, with
the index over them, are dropped. Neither is rebuilt on the way down — they were a project's own columns, added
by migration traits the bundle no longer ships.

So the upgrade is one declaration, and the two values are what keeps the migrated data where it was:

```php
'modules' => [
    'cms' => [
        'menus' => fn (): array => [
            Menu::make(1)->name(Yii::t('app', 'Main menu')),
            Menu::make(2)->name(Yii::t('app', 'Footer')),
        ],
    ],
],
```

A closure, for the same reason `sectionSets` takes one: a menu's name is a `Yii::t()` result and a configuration
file is read before the application has an `i18n` component. A menu declaring no name is refused, as are two
menus sharing a value. Nothing is declared by default, so an installation that never used the checkboxes
configures nothing and renders no menu field.

`Models\Menus\Menu` takes an int backed enum as well, which is what a project addressing its menus by name
wants — `Menu::make(SiteMenu::Main)`, then `MenuCollection::getItems(SiteMenu::Main)`.

### What is gone

| removed                                                | replacement                                            |
|--------------------------------------------------------|--------------------------------------------------------|
| `Migrations\Traits\MenuColumnTrait`                    | `Migrations\M260915200000MenuIds`, which ships with the bundle |
| `Migrations\Traits\FooterColumnTrait`                  | the same                                               |
| `Models\Traits\MenuAttributeTrait`                     | `Entry::$menu_ids` and `Entry::isMenuItem()`, which every entry has |
| `Models\Traits\FooterAttributeTrait`                   | the same                                               |
| `Modules\Admin\Widgets\Forms\Traits\MenuFieldTrait`   | `Modules\Admin\Widgets\Forms\Fields\MenuIdsField`, in the entry form by default |
| `Modules\Admin\Widgets\Forms\Traits\FooterFieldTrait` | the same                                               |
| `Widgets\NavItems`                                      | `Models\Collections\MenuCollection`                    |
| `Models\Types\EntryType::showInMenu()` / `showInFooter()` | `Menu::available(Closure\|bool)`                       |

An entry's menus are ordinary attributes now, so the `rules()` and `attributeLabels()` spreads those traits
needed are gone with them — and with them the trap of forgetting one, which made the checkbox vanish from the
form without an error.

`EntryType::showInMenu(false)` said "this type is never in a menu", and it said it once for both menus. The menu
answers now, per entry, and `Modules\Admin\Widgets\Grids\Columns\MenuColumn` is unchanged in name only —
its tooltip names every menu the entry is in rather than reading a label:

```php
Menu::make(1)
    ->name(Yii::t('app', 'Main menu'))
    ->available(fn (Entry $entry): bool => $entry->type !== Entry::TYPE_ARTICLE),
```

A menu an entry is **already in** stays offered and stays valid even once it is unavailable, so a rule that stops
matching records cannot lock them out of every later save — the same rule `Models\Types\Type::isAvailableOrStored()`
follows. An id no menu declares is dropped by `Validators\MenuIdsValidator` rather than reported: the declaration
is project configuration a record cannot answer for.

### Reading the menus

`Models\Collections\MenuCollection` replaces `Widgets\NavItems`, which was a static class under `Widgets\` that
was never a widget. It resets from `Bootstrap` like the other collections, and loads **every autoloaded menu in
one query**, so a layout rendering a header and a footer pays for one:

| removed                             | replacement                                    |
|-------------------------------------|------------------------------------------------|
| `NavItems::getMenuItems()`          | `MenuCollection::getItems($menu)`              |
| `NavItems::getMainMenuItems()`      | `MenuCollection::getRootItems($menu)`          |
| `NavItems::getSubmenuItems($parent)`| `MenuCollection::getSubmenuItems($parent, $menu)` |
| `NavItems::getFooterItems()`        | `MenuCollection::getItems($menu)`              |
| `NavItems::getIsMenuItem($entry)`   | `$entry->isMenuItem($menu)`                    |
| `NavItems::getIsFooterItem($entry)` | the same                                       |

A menu declaring `autoload(false)` is kept out of that query and loaded on its own, on first use — for one that
is rarely rendered, or holds far more entries than a navigation does.

`Models\Queries\EntryQuery::andWhereMenu(int|BackedEnum ...)` is the condition behind both. Neither MySQL nor
MariaDB can index a JSON list usefully, so asking for a *subset* of the declared menus is a `JSON_CONTAINS()` per
menu, while asking for all of them — what the collection does — is the cheap `menu_ids IS NOT NULL`.

## 3.0.0 — The category's meta title and description are custom attributes

`Migrations\M260915120000CategoryMeta` drops `category.title` and `category.description` into the
`custom_attributes` column, translation rows included, the same way the free text moved. `Category` declares both
itself, so a project configures nothing — unless it translated them, in which case they move from
`Category::$i18nAttributes` to `translatableAttributes` (see the guide below for why). Neither can be a query
condition or a sort any more.

**`entry.title` and `entry.description` are unchanged.** An entry has a URL of its own and its meta pair is read
on every page it renders; only the category's moved.

## 3.0.0 — The free text of the cms models is a custom attribute

`Migrations\M260915100000CustomAttributes` drops `entry.content`, `category.content` and `section.name`,
`section.slug` and `section.content`, moving each value into the `custom_attributes` column under its own name,
and every `translation` row of those attributes under its suffixed one (`content_de`). A `content_de` column a
project still had is read the same way.

`Models\ActiveRecord::$contentType` and `$htmlValidator` are gone. `$contentType = false` — the default for an
entry and a category — meant "no content field", and that is now simply an attribute nobody declares, so a
project that used the entry or category content declares it:

```php
'container' => ['definitions' => [
    Entry::class => [
        'customAttributes' => [
            HtmlCustomAttribute::make('content')
                ->translatable(),
        ],
    ],
]],
```

`Models\Entry::getSearchAttributes()` still names `content`, so a declared one is indexed; an undeclared one is
skipped rather than failing.

A section declares `name`, `content` and `slug` itself, so nothing has to be configured for it — but **a project
that had them among `Section::$i18nAttributes` has to move them to `translatableAttributes`**, or the model
throws `Custom attribute "name" collides with a translated attribute`:

```php
Section::class => ['translatableAttributes' => ['name', 'slug', 'content']],
```

`i18nAttributes` names columns; a translatable custom attribute keeps its `_de` value inside the JSON. The
migration writes the values where the new configuration reads them either way.

**The collision is reported from the media file index, not from a section form.**
`Media\Module::ensureTypeTransformations()` resolves the type definitions of every registered asset class *and
of each one's model class*, so the first media thumbnail any admin page renders builds an `Entry` and a
`Section` through the container. The misconfiguration is also latent until something forces `attributes()` while
the model is constructed: a definition carrying nothing but `i18nAttributes` builds cleanly, while one that also
sets `types` throws, since `Yii::configure()` assigns that through `__set()`, which asks `hasAttribute()` first.
So check the configuration of every model that has assets, rather than trusting that the section pages still
open.

A section's slug is its HTML id, so it is only checked against the sections of the same entry now, by
`Section::validateSlug()` rather than by `UniqueValidator`. `Models\Traits\SlugAttributeTrait` is off `Section`
with it: `$slugTargetAttribute`, `$slugUniqueValidator`, `$slugMaxLength`, `$customSlugBehavior`, `ensureSlug()`
and `isSlugRequired()` are gone there (`Entry` and `Category` keep all of them), `Section::SLUG_MAX_LENGTH`
replaces `$slugMaxLength`, and `generateUniqueSlug()` is the section's own — it no longer runs a full validation
per attempt. The inflection moved into `Models\CustomAttributes\SlugCustomAttribute::normalize()`, which runs
as the definition's filter rule and therefore normalizes a translated slug too, where `beforeValidate()` only
ever normalized the source language.

`Modules\Admin\Widgets\Forms\Traits\ActiveFormFieldsTrait::getContentField()` and `getLinkField()` are gone;
a form renders the definitions with `getCustomAttributeFields()`. `SectionActiveForm` keeps its own slug field —
the one with the URL in front of it — and excludes the definition from that list:

```php
protected function getDefaultRows(): array
{
    return [
        $this->getStatusField(),
        $this->getTypeField(),
        ...$this->getCustomAttributeFields(except: ['slug']),
        $this->getSlugField(),
    ];
}
```

A type's `hiddenFields()` is unchanged and still hides `name` or `content`.

**`section.name` is no longer a column**, so it cannot be a query condition, an `orderBy` or a grid sort. Read it
off the record.

## 3.0.0 — `author` is a role to assign, not one `admin` holds

`Migrations\M260914210000AuthorRole` removes `author` from `admin`, which lists the permissions themselves now
(see the skeleton's upgrade guide), and gives it `File::AUTH_FILE` and `Folder::AUTH_FOLDER` — the two permissions
the `media` role carried before `yii2-media` dropped it — so an author can still use the media library. An
`AccessRule` or a nav item that named `author` to mean "an editor or an administrator" has to name both.


## 3.0.0 — The sitemap moved out of the models

Read the skeleton's sitemap guide first. `Models\Traits\SitemapTrait` is gone, and with it
`Entry::getSitemapQuery()`, `Entry::getSitemapUrl()`, `Category::getSitemapQuery()` and
`ActiveRecord::includeInSitemap()`. The entry and category sitemaps are `Sitemap\EntrySitemap` and
`Sitemap\CategorySitemap`, registered on the component:

```php
'sitemap' => [
    'sitemaps' => [
        'entries' => ['class' => EntrySitemap::class, 'enableImages' => true],
        'categories' => CategorySitemap::class,
    ],
],
```

`Module::$enableImageSitemaps` is `EntrySitemap::$enableImages`. A project that customised a model's sitemap
overrides `getQuery()` or `getRecordUrl()` on its own subclass of `Sitemap\RecordSitemap` instead of the model.

## 3.0.0 — The entry, section and category types

Read the skeleton's guide on typed type definitions first, and the media one for `sizes` and `transformations`.

`Models\Types\Type` is the cms base and carries what `Models\ActiveRecord` reads for every cms model:

| Setter                | Getter            | Read by                                      |
|-----------------------|-------------------|----------------------------------------------|
| `viewFile(?string)`   | `getViewFile()`   | `Entry::getViewFile()`, `Section::getViewFile()`, `Widgets\SectionStack` |
| `cssClass(?string)`   | `getCssClass()`   | `Models\ActiveRecord::getCssClass()`         |

`Models\Types\EntryType`, on top of that and of the media `sizes()` / `transformations()`:

| Setter                        | Getter                      | Read by                                |
|-------------------------------|-----------------------------|----------------------------------------|
| `orderBy(?array)`             | `getOrderBy()`              | `Modules\Admin\Data\EntryActiveDataProvider` |
| `sort(?array)`                | `getSort()`                 | the same                               |
| `showCategories(?bool)`       | `showsCategories()`         | `Modules\Admin\Widgets\Grids\EntryGridView` |
| `showCategoryDropdown(?bool)` | `showsCategoryDropdown()`   | the same                               |

`Models\Types\SectionType`, likewise:

| Setter                                 | Getter                | Read by                             |
|----------------------------------------|-----------------------|-------------------------------------|
| `visible(Closure\|bool)`               | `getVisible()`        | `Widgets\SectionStack`, stage 1     |
| `collect(?Closure)`                    | `getCollect()`        | stage 2                             |
| `group(Closure\|string\|null)`         | `getGroup()`          | stage 3                             |
| `wrapper(Closure\|string\|null)`       | `getWrapper()`        | stage 4                             |
| `entriesOrderBy(?array)`               | `getEntriesOrderBy()` | `Section::getEntriesOrderBy()`      |
| `entriesTypes(int ...)`                | `getEntriesTypes()`   | `Section::getEntriesTypes()`, `Modules\Admin\Widgets\Grids\SectionEntryGridView` |
| `gridContent(Closure\|string\|null)`   | `getGridContent()`    | `Section::getGridContent()`         |

`visible()` keeps the meaning the section stack gave it — whether the section is *rendered on the site*. Whether
a type is offered in the admin is the base class's `available()`.

`entriesTypes()` is validated against `Entry`'s own declarations now, so a type that names one the entry model
does not declare throws instead of filtering the dropdown down to nothing.

`Models\Traits\MetaImageTrait::getMetaImageTypeOptions()` is `getMetaImageTypes()` and returns a list, so
`getTypes()` composes with the spread operator rather than `+`, which discarded a colliding key without a word.
Its `visible` key — which nothing in v3 read — is `available()`, and `Widgets\Forms\Fields\TypeSelectField`
honours it again.


## 3.0.0 — `Widgets\Canvas` is `Widgets\Artwork`

`Widgets\Artwork` renders an entry, section or block asset where `Widgets\Canvas` did, configured through setters
rather than public properties. The template is gone: the parts render in a fixed order, and each is handed to a
closure before it is placed, which is where a project changes it or drops it.

| v2 `Canvas`                                               | v3 `Artwork`                                                                     |
|-----------------------------------------------------------|----------------------------------------------------------------------------------|
| `Widgets\Canvas`                                          | `Widgets\Artwork`                                                                |
| `Canvas::widget(['asset' => $asset])`                     | `Artwork::make()->asset($asset)`; the asset is required                          |
| `$template`, `$parts`, `renderAdmin()` and the `{…}` tokens | removed; the closures below, `adminLink(false)` for the admin link             |
| `$wrapperOptions` (`['class' => 'canvas']`)               | `attributes()` / `addClass()`, or `wrapper(fn (Div $div) => …)`; no default class |
| `$pictureOptions`                                         | `media(fn (Media $media) => …)`, the media `Widgets\Media` in place of `Picture` |
| `$linkOptions`                                            | `link(fn (?A $a) => …)`; `url()` replaces the asset's `link`                     |
| `$enableLinkWrapper`                                      | removed; the link wraps the media, never the caption                             |
| `$captionOptions` (with `encode`)                         | `caption(fn (?Figcaption $caption) => …)`; encoded unless `content` is an `HtmlCustomAttribute` |
| `$embedViewFile`                                          | `embedViewFile(string\|false)`                                                   |
| `$enableMaxWidth`, `$defaultMaxWidth`                     | `maxWidth(true)`, `maxWidth(int $max)`                                           |
| `$enableWrapperHeight`, `$setWrapperHeightWithAspectRatio` | `aspectRatio(bool)`, on the image rather than the wrapper; no `padding-top` fallback |
| `$lazyLoadingParentPosition` (`2`, the asset's position)  | `lazyLoadingPosition(int\|false)` (`5`, artworks rendered in the request so far; `false` leaves every image lazy) |

The markup changed with it. The wrapper is always a `div`; the media sits in a `figure` beside a `figcaption` when
the asset has a caption or an embed, or when `figure()` is set; an embed and the media share a `div.relative`; the
admin link is appended to the wrapper, which then carries `relative` too. A project's CSS keyed on `.canvas`
targets the class it now passes itself:

```php
echo Artwork::make()
    ->asset($asset)
    ->addClass('canvas')
    ->media(fn (Media $media) => $media->transformations(['md', 'lg']))
    ->caption(fn (?Figcaption $caption) => $caption?->addClass('caption'));
```

The closures stack: each runs in turn on what the previous one answered, and `null` drops the caption or the
link. A subclass sets its media defaults in `makeMedia()`, before the caller's closures. The lazy-loading counter belongs to
the request, reset by `Bootstrap` — `resetCounter()` restarts it where a page renders a second, independent run of
artworks. The hotspot bundle's `Widgets\Artwork` extends this one; its own guide covers `hotspotViewFile()`.

`Gallery` renders an `Artwork` per asset, so its v2 `canvasOptions` are `artwork()` closures:

```php
echo Gallery::make()
    ->assets($entry->getVisibleAssets())
    ->artwork(fn (Artwork $artwork) => $artwork
        ->media(fn (Media $media) => $media->sizes('min(100vw, 320px)')->transformations(['w_320', 'w_640'])));
```

The shipped `widgets/_assets` view is gone. A `viewFile()` or a `content()` closure, whichever is set last, takes
over the markup and builds each artwork with `$gallery->makeArtwork($asset)`, which keeps the caller's closures; the
view receives `$gallery` beside the parameters `viewFile()` was given, the closure the assets and the gallery.

## 3.0.0 — Sections

`Widgets\Sections` is gone. `Widgets\SectionStack` renders an entry's sections and `Widgets\SectionGroup`
renders one run of them; there is no deprecation alias, because the v3 namespace rename breaks every `use` line
anyway. Three defects of the old class are the reason the API changed rather than just the name:

1. **The three `render*()` helpers were unreachable.** v2 rendered a group view with the widget as `$context`
   (`$this->getView()->render($viewFile, $params, $this)`); the port dropped the third argument, so
   `$this->context` in `_sections.php` was the `SiteController` and `$context->renderAdjacentSectionsByType()`
   — the only way the helpers were ever called — was a fatal.
2. **`visible` meant two things.** `prepareSections()` read `VisibilityTrait::$visible` as the per-section
   default and called it with a `Section`, while `Widget::render()` calls the same closure with the widget, so
   `Sections::make()->visible(fn (Section $s) => …)` was a `TypeError` under `strict_types`.
3. **`position` was, and still is, the index of the visible list** — not the neighbour information issue #48
   asked for, which is why the neighbours live on the stack instead of on the model.

| v2 / v3 before                                                | v3                                                                                                                                                                                    |
|---------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Sections::widget(['entry' => $entry])`                       | `SectionStack::make()->entry($entry)`                                                                                                                                                 |
| `Sections::class => \app\widgets\Sections::class` (container) | `SectionStack::class => …` — or no subclass at all, see below                                                                                                                         |
| `init()` / `run()` override registering scripts               | `SectionStack::make()->…->prepare(fn ($stack) => $stack->view->registerJs(…))`, or a subclass `configure()`                                                                            |
| `hasSameViewFile()` override                                  | type option `'group'`, or `->groupKey(Closure)`                                                                                                                                       |
| `renderSectionsInternal()` wrapping each group                | the wrapper element in the group view; across views, type option `'wrapper'`; for every group centrally, `Event::on(SectionGroup::class, Widget::EVENT_CONFIGURE, …)`                  |
| `getSectionViewFile()` override                               | type option `'viewFile'`, or `->sectionViewFile(Closure)` (the method stays protected)                                                                                                |
| `$context->renderAdjacentSectionsByType($section, $view)`     | `$group->renderAdjacent($section, $view)` — or type option `'collect' => SectionStack::collectAdjacent()`                                                                              |
| `$context->renderSectionsByType($types, $view)`               | `'collect' => SectionStack::collectAll()` on the head type, or `$group->renderWhere()`                                                                                                 |
| `$context->renderSectionsByCallback($cb, $view)`              | `'collect' => fn (Section $head, array $rest) => …`, or `$group->renderWhere($cb)`                                                                                                     |
| `Sections::$isVisible` / `->visible(fn (Section) …)`          | `->filter(Closure)`; `->visible()` is the widget's own visibility                                                                                                                     |
| `$section->position` as the visible index                     | unchanged; neighbours are `$stack->getPrevious()` / `getNext()`                                                                                                                       |
| `$this->context` in `_sections.php` is the controller         | it is the `SectionGroup` again                                                                                                                                                        |

### The type options

A section declares how it renders in its own type options, beside `viewFile`, `visible` and `cssClass`:

| option    | type                                        | what it does                                                                       |
|-----------|---------------------------------------------|------------------------------------------------------------------------------------|
| `viewFile`| `string`                                    | the view of the group this section heads, falling back to the stack's `viewFile`   |
| `visible` | `bool\|Closure(Section): bool`              | drops the section, falling back to the stack's `filter()`                          |
| `group`   | `string\|Closure(Section): string`          | the group key, defaulting to the section's view file                               |
| `wrapper` | `string\|Closure(Section): ?string`         | the wrapper key; consecutive groups sharing it are rendered inside one element     |
| `collect` | `Closure(Section $head, Section[] $rest): Section[]` | takes the sections it returns out of the stack and into this section's group |

Consecutive sections with the same group key are one `SectionGroup`, rendered through the head's view file with
`$sections` and `$group`. A head and what it collected are always a closed group of their own. Collection is
greedy and first come: an earlier head wins a section a later one would also have taken.

The wrapper element is `Html\Div::make()->class($key)`; `SectionStack::wrapper(Closure)` replaces it. Wrapping a
group's *own* sections needs none of this — the group view has all of them and puts its element around the
`foreach`.

### View resolution

A relative group view resolves against the directory of the view that renders the stack, not against
`@views/<controller id>/`, which is what `Widget::getViewPath()` would answer: the cms site views live in the
bundle, where the application's view path does not reach. An absolute name (`@views/site/_tabs`) is unaffected.

## 3.0.0 — Trait rules are no longer discovered

`Models\ActiveRecord::rules()` and `attributeLabels()` no longer call the skeleton's `ModelTrait::getTraitRules()`
/ `getTraitAttributeLabels()`, which discovered trait hooks by reflection and naming convention. Both are removed
from the skeleton; see `bundles/yii2-skeleton/UPGRADE.md`. A project trait that relied on them is spread into
`rules()` and `attributeLabels()` by the model using it. The menu and footer traits are gone altogether, see
"Entry menus replace `show_in_menu` and `show_in_footer`".
