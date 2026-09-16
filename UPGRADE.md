# Upgrade Guide

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

### A route refuses what the admin does not offer

`Modules\Admin\Controllers\SectionController` and `EntryCategoryController` now answer `404` for an entry whose
type has no sections or no categories, through `Traits\EntryControllerTrait::isEntryAllowed()`. The module flags
were not checked there either, so `enableSections => false` used to leave the section routes open. A project with a
controller of its own overrides the hook.

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
$this->rows ??= [
    $this->getStatusField(),
    $this->getTypeField(),
    ...$this->getCustomAttributeFields(except: ['slug']),
    $this->getSlugField(),
];
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
| `showInMenu(bool)`            | `hasShowInMenuEnabled()`    | `Models\Traits\MenuAttributeTrait`    |
| `showInFooter(bool)`          | `hasShowInFooterEnabled()`  | `Models\Traits\FooterAttributeTrait`  |

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

Two magic strings among the hidden fields became constants: `Models\Section::FIELD_ENTRIES` and the media
`Models\Interfaces\AssetModelInterface::FIELD_ASSETS`.

`Models\Traits\MetaImageTrait::getMetaImageTypeOptions()` is `getMetaImageTypes()` and returns a list, so
`getTypes()` composes with the spread operator rather than `+`, which discarded a colliding key without a word.
Its `visible` key — which nothing in v3 read — is `available()`, and `Widgets\Forms\Fields\TypeSelectField`
honours it again.


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

## 3.0.0 — Menu and footer attributes are wired up by hand

`Models\ActiveRecord::rules()` and `attributeLabels()` no longer call the skeleton's `ModelTrait::getTraitRules()`
/ `getTraitAttributeLabels()`, which discovered trait hooks by reflection and naming convention. Both are removed
from the skeleton; see `bundles/yii2-skeleton/UPGRADE.md`.

`Models\Traits\MenuAttributeTrait` and `Models\Traits\FooterAttributeTrait` keep their methods under shorter
names and are called by the model that uses them:

| removed                                   | replacement                   |
|-------------------------------------------|-------------------------------|
| `getMenuAttributeTraitRules()`            | `getMenuAttributeRules()`     |
| `getMenuAttributeTraitAttributeLabels()`  | `getMenuAttributeLabels()`    |
| `getFooterAttributeTraitRules()`          | `getFooterAttributeRules()`   |
| `getFooterAttributeTraitAttributeLabels()`| `getFooterAttributeLabels()`  |

```php
class Entry extends \Hirtz\Cms\Models\Entry
{
    use MenuAttributeTrait;

    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getMenuAttributeRules(),
        ];
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            ...$this->getMenuAttributeLabels(),
        ];
    }
}
```

Without the `rules()` spread `show_in_menu` is neither safe nor validated, so it is not loaded from a form post
and `Skeleton\Widgets\Forms\Fields\Field` renders nothing for it — the checkbox added by
`Modules\Admin\Widgets\Forms\Traits\MenuFieldTrait` disappears from the entry form without an error. Grep for
`use MenuAttributeTrait` and `use FooterAttributeTrait` and check every hit.
