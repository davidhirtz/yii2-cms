# Upgrade Guide

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
| `nameColumn(Closure\|string\|null)`    | `getNameColumn()`     | `Section::getNameColumnContent()`   |

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
