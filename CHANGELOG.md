## 3.0 (in development)

- **The block pages explain what a block is** (monorepo issue #163). `resources/views/admin/block/index.php`
  and `block-section/index.php` render the skeleton's new `Modules\Admin\Widgets\HintAlert` with
  `BLOCK_INDEX_HINT` and `BLOCK_SECTION_INDEX_HINT`; an account that turned its hints off sees neither.

- **A create action takes the type from the query** (monorepo issue #161).
  `Modules\Admin\Controllers\CategoryController::actionCreate()` and `SectionController::actionCreate()` take a
  `?int $type` and hand it to `instantiateFromPost()`, the way the entry, block, location and tag ones already
  did — their create buttons carried the grid's own type filter into a URL nothing read.
  `Modules\Admin\Widgets\Navs\CategoryHeader`'s create button carries the provider's type now.

- **`Modules\Admin\Widgets\Buttons\EntryCreateButton` stops dropping the filtered type.** It overwrote the
  query's `type` with `$this->view->params['entryType']`, which was **always** `null` there: `$view` is assigned
  by `Widgets\Widget::__construct()`, which runs after a subclass's, and `??` answers `null` for an
  uninitialized typed property rather than erroring. The pinned type is read in `configure()` now, and only
  where the query named none.

- **`Modules\Admin\Widgets\Navs\BlockSubmenu`'s *Sections* tab is `th-list`**, the icon the entry submenu's
  sections tab already carries (monorepo issue #148). It was `link`, which Font Awesome draws as the `chain` of
  the *Entries* tab beside it — the two names are aliases of the same glyph.

- **`Models\EntryRelation::getPermissionName()` answers the model's own**, the way the media `Asset` does since
  monorepo issue #147, so `SectionEntry` and `BlockEntry` declare none. `EntryAsset`, `SectionAsset` and
  `BlockAsset` lost theirs for the same reason.

- **The entry's parent select reloads the form instead of swapping URLs in the browser** (monorepo issues #151,
  #150 and #155). `Modules\Admin\Widgets\Forms\Traits\ParentIdSelectFieldTrait::getParentIdOptionDataValue()`
  and its `$parentSlugMaxLength`, `ParentIdFieldTrait::getParentIdAttributes()` and
  `SlugFieldTrait::getSlugId()` are **deleted** with the `data-form-target` script they fed; the field calls the
  skeleton's `Widgets\Forms\Fields\Field::reloadsForm()`, so the server renders the base URL of the chosen
  parent and nothing builds a URL per option any more. The prefix div carried the *same* id as the slug input it
  sits in front of, and the script wrote into whichever of the two it found first — the slug input, with the
  *next* language's base URL, so opening an entry and pressing Update renamed the record.
  `EntryActiveForm::getSlugBaseUrl()` composes the parent's path rather than its route, so a new entry under a
  parent that has no children yet gets a URL instead of an empty prefix. The category's parent select no longer
  emits `data-value` either: a category's slug is a filter parameter, and the form never read it.

- **`Validators\TenantIdValidator` declares its attribute an integer** through the skeleton's new
  `Validators\Interfaces\AttributeTypeInterface`. Nothing else typed `tenant_id`, so a form reload — which
  renders the loaded record without validating it — left the posted string in place and
  `Models\Entry::getTenantRouteParams()` handed it to a `?int` parameter: every reload of the entry form was a
  500, the type and tenant selects included.

- **A page's title stays on the record that owns it, and the records below it name themselves underneath.**
  `Models\Entry`, `Section`, `Block` and `Category` answer the skeleton's `getAdminParent()` /
  `getAdminIndexBreadcrumb()`, `Section` also its `getAdminSubtitle()`, and the four headers extend
  `Skeleton\Widgets\Navs\ModelHeader` instead of assembling breadcrumbs of their own:
  `Modules\Admin\Widgets\Navs\Traits\EntryHeaderTrait` is **deleted**, with
  `SectionHeader::addSectionBreadcrumbs()` and `CategoryHeader::addCategoryBreadcrumbs()`. A section page reads
  "About — Section #3" and a section's asset "About — Section #3 · Asset #1", the H1 linking to the entry
  throughout and each subtitle item to its own page. `$maxParentBreadcrumbCount` is gone and the bar is not capped at all. *Blocks* and
  *Categories* no longer carry an *Entries* crumb, being nav items beside it rather than under it.

- **`Modules\Admin\Widgets\Navs\FrontendLink::findInChain()`** answers the nearest record up the admin chain
  that has a frontend URL, so an asset page keeps the website link its owner has; the three asset update views
  pass it as the header's subheading. It answers `null` for a block and anything under one.

- **`Modules\Admin\Widgets\Navs\SectionSubmenu` lost its `<<` tab.** A submenu holds the views of one record
  and never a link out of it; the way back to the entry is the title. `getSectionsItem()` is gone with the last
  use of the `angle-double-left` icon.

- `Modules\Admin\Widgets\Navs\BlockHeader` no longer adds `BlockActionDropdown` itself (monorepo issue #144):
  the block's submenu views hand the header a dropdown of their own and rendered two.
  `resources/views/admin/block/update.php` adds it now, as `entry/update.php` does with `EntryActionDropdown`, so
  a project view rendering the header for a block adds the dropdown the same way.

- **`Widgets\AdminLink` moved to `yii2-skeleton`** and links a section, an entry and a category again. It never
  referenced a cms class and the CSS it renders into is the skeleton's `Widgets\Buttons\AdminButton`, so the
  class is `Hirtz\Skeleton\Widgets\AdminLink` now and its default class lost the project-defined `overlay`.
  The frontend overlay link asked
  `method_exists($model, 'getPermissionName')` and rendered nothing when that was false, which was every cms
  model — only the asset and entry-relation families declared it, so the link had survived on assets alone.
  `Skeleton\Models\Interfaces\AdminModelInterface::getPermissionName()` now declares it, `Models\Entry`,
  `Category` and `Block` return their own `AUTH_*` constant, and `Section` and `EntryCategory` return
  `Entry::AUTH_ENTRY` — a section has no permission of its own. `resources/views/site/_sections.php` gained a
  `relative` class on its `<section>`, the positioned ancestor the overlay fills; a project overriding that view
  adds it itself, and the class is the project's to define.

- **Global sections** (monorepo issue #109). `Models\Block` is a section with no owner — the same types, assets,
  linked entries and custom attributes, but no tenant and no entry — and a section places one by carrying a
  `block_id`. `Module::$enableBlocks` (default `false`, cascading to `enableBlockAssets` and
  `enableBlockEntries`) turns the feature on, `Block::AUTH_BLOCK` is the permission, and the admin adds
  `BlockController`, `BlockAssetController` and `BlockEntryController` under `/admin/cms/` with a *Blocks* item
  beside *Categories*.

  A section carrying a block delegates to it: `Section::getViewFile()`, `getVisibleAssets()` and the new
  `getVisibleEntries()` answer with the block's, so `Widgets\SectionStack` and every project view render one
  unchanged, and a section that allows a block but has none renders nothing at all.
  `Models\Types\SectionType::allowBlock()` is the opt-in, `Models\Types\BlockSectionType` the shipped type
  that allows nothing else, and `Modules\Admin\Widgets\Forms\Fields\BlockIdSelectField` the form field.
  `Models\Actions\PreloadEntrySiteRelations` loads the blocks of an entry's sections with their assets and
  linked entries, in one extra query. `block.name` is a column rather than a custom attribute, so it can be
  sorted, searched and put in `i18nAttributes`.

  `block.section_count` records how many sections place a block, and `BlockSectionController` lists them
  through `Modules\Admin\Widgets\Grids\BlockSectionGridView` and deletes them — the tab the count links to,
  the way the media file's asset tab lists and removes what holds a file. Listing is `Block::AUTH_BLOCK`,
  deleting a section is `Entry::AUTH_ENTRY`, and the redirect stays on the tab until its last section is gone.
  The grid offers the skeleton's `Widgets\Grids\Traits\SelectionTrait` — a selection and `delete-all`, on by
  default — while its per-row delete button is behind `$showDeleteButton`, off, as `SectionGridView`'s is; the
  `delete-all` is scoped to the block the selection was made on, so a crafted id cannot reach another's.
  `SectionGridView` takes its own selection from that trait now, so `getSelectionButton()` is
  `getDeleteSelectionButton()` and the `> 1` row guard moved into `canDeleteSelection()`. The count is
  maintained from the section: `Section::afterSave()` and `afterDelete()` call the new static
  `Section::recalculateBlockSectionCounts()` for the block a section holds and the one it left, and
  `Models\Actions\DeleteSections` and `CreateSectionSet` call it once at the end of a batch.

  A block has **no `position`**: it belongs to nothing, so there is nothing to order it within. It is not
  draggable and `Modules\Admin\Data\BlockActiveDataProvider` sorts by `updated_at DESC` through the `Sort`'s
  `defaultOrder` rather than through the query, so every column header works.
  `Models\ActiveRecord::setDefaultPosition()` returns early for a record without the column, which is what lets
  a cms model drop it. See `UPGRADE.md`.


- **The section-entry link is polymorphic** (monorepo issue #109). `section_entry` is `entry_relation`, keyed by
  `model_class` / `model_id` the way `asset`, `trail` and `translation` are, so a second model can link entries
  through the same table. `Models\EntryRelation` is the base, `Models\SectionEntry` a subclass scoped to its
  `model_class`, and `Module::$entryRelations` the registry `instantiate()` dispatches on. A model that links
  entries implements `Models\Interfaces\EntryRelationModelInterface` and uses
  `Models\Traits\EntryRelationModelTrait`; its type implements `Models\Interfaces\EntryRelationTypeInterface`
  through `Models\Types\Traits\EntryRelationTypeTrait`, which is where `SectionType`'s `allowEntries()`,
  `entriesTypes()` and `entriesOrderBy()` now live. Renamed with it: `$section->sectionEntries` →
  `$section->entryRelations`, `$entry->sectionEntry` → `$entry->entryRelation`,
  `EntryQuery::whereSection()` → `whereRelatedModel()`, `EntryActiveDataProvider::$section` → `$relatedModel`
  (and `$innerJoinSection` → `$innerJoinRelatedModel`), `Models\Actions\ReorderSectionEntries` →
  `ReorderEntryRelations`, `SectionEntryGridView` → `EntryRelationGridView`, `SectionLinkedEntryGridView` →
  `LinkedEntryGridView`, `Columns\SectionEntryCountColumn` → `EntryRelationCountColumn`,
  `Buttons\SectionEntryCreateButton` → `EntryRelationCreateButton`. `SectionEntryController` keeps its route and
  moves onto the shared `Modules\Admin\Controllers\Traits\EntryRelationControllerTrait`; its reorder body
  parameter is `entry-relation`. `Models\Traits\SectionRelationTrait` is gone — the owner is polymorphic now.
  See `UPGRADE.md`.

- **`Models\Builders\EntrySiteRelationsBuilder` is `Models\Actions\PreloadEntrySiteRelations`** (monorepo issue
  #136). It builds nothing: it loads everything an entry's site view needs in one pass and populates the
  relations, so it joins the verb-first classes in `Models\Actions\` and `Models\Builders\` is gone with it.
  The event renames too — `Models\Events\EntrySiteRelationsBuilderEvent` is `Models\Events\EntrySiteRelationsEvent`,
  deliberately without the sender's name in it, so the next rename leaves subscribers alone. Nothing else moves:
  the three `EVENT_AFTER_LOAD_*` constants, the public `$entry`, `$assets`, `$entries`, `$files` and `$fileIds`
  properties and the work in `init()` are unchanged. See `UPGRADE.md`.

- **`EntryAssetController` and `SectionAssetController` traded `duplicate` for a POST-only `remove` action**
  (monorepo issue #133): an entry or section holds a file once, so the file picker's button removes what it
  already has rather than adding a second row, and duplicating an asset onto its own record no longer means
  anything. A project overriding one of those `behaviors()` swaps `duplicate` for `remove` in its access rule.
  See the media bundle's `UPGRADE.md`.

- **`EntryAssetController` and `SectionAssetController` gained a POST-only `delete-all` action** that removes the
  assets a grid selection names (monorepo issue #128). A project overriding one of those `behaviors()` has to add
  `delete-all` to its access rule; the verbs come from `getAssetVerbs()`.

- **`EntryController`, `CategoryController`, `SectionController`, `EntryAssetController` and
  `SectionAssetController` gained a POST-only `status` action** that cycles the record's status, which the grid's
  status icon posts to (monorepo issue #121). A project overriding one of those `behaviors()` has to add `status`
  to its access rule and its verbs. The entry, category and section grids offer it, the pickers do not.

- **The tenant filter is dropped from `Modules\Admin\Widgets\Grids\EntryGridView` while it lists the children of
  an entry** (monorepo issue #123): they all belong to their parent's tenant, so the filter could only empty the
  grid.

- **`EntryCategoryGridView` renders the search input** (monorepo issue #124). `EntryCategoryController::actionIndex()`
  had always taken the `q` parameter; only the input was missing, and the picker lists every category in the
  installation.

- **`SectionGridView`, `SectionLinkedEntryGridView` and `EntryCategoryGridView` explain themselves while empty**
  (monorepo issue #119), through `GridView::emptyMessage()`.

- **A section, entry or category built from a known type goes through `instantiateByType()`** (monorepo issue
  #105), so a type declaring a model class of its own gets it: `Models\Actions\CreateSectionSet` (the template
  names the type) and `Modules\Admin\Controllers\SetupController`, whose attribute arrays a project declares and
  which routinely name a type. **`EntryController`, `SectionController` and `CategoryController::actionCreate()`
  take the posted type**, through `instantiateFromPost()`, since the type select reloads the form by posting to
  the same action. A project's `EntryType::modelClass()` therefore has to keep the base `formName()`, see the
  skeleton's changelog.

  Side effect in `EntryController::actionCreate()`: with neither a `type` parameter nor
  `Module::$defaultEntryType`, the form now starts at the column default rather than at no type at all — the
  type select showed its first option while the custom attribute fields below it were the typeless ones.

- **`Modules\Admin\Widgets\Forms\EntryActiveForm::getRowsAsGroups()` is gone**, with the ambiguous row shape it
  worked around (monorepo issue #120, skeleton `Widgets\Forms\ActiveForm`). The tenant row is part of what
  `getDefaultRows()` declares now, so **a caller replacing the form's rows wholesale owns the tenant field too** —
  it used to be spliced in afterwards whatever the caller passed. Adding to the form rather than replacing it, which
  is what `Widget::EVENT_CONFIGURE` is for, is unaffected.

- `EntryActiveForm`, `CategoryActiveForm` and `SectionActiveForm` declare their fields in `getDefaultRows()` instead
  of assigning `$this->rows ??=` in `configure()`. A subclass overriding `configure()` to change the fields has to
  move to the hook.

- **`Modules\Admin\Controllers\Traits\SectionControllerTrait::isSectionAllowed()`** is the counterpart of
  `EntryControllerTrait::isEntryAllowed()`. `SectionEntryController` answers it with `Section::allowsEntries()`, so
  the whole controller behind the entries tab is refused where that tab is hidden — with `enableSectionEntries`
  defaulting to `false`, every one of its routes used to be reachable on a default installation.
  `SectionController::actionEntries()`, the picker for the same tab, refuses it too.

  **A section-scoped action is deliberately not gated.** `SectionController::actionUpdate()` and `actionDelete()`
  still reach a section whose entry's type has since stopped allowing sections, which is the exemption
  {@see Skeleton\Models\Types\Type::isAvailableOrStored()} makes everywhere else: a record stored in a state the
  configuration no longer allows has to stay deletable, or it is invisible *and* immortal.
  `Models\Section::validateEntryId()` refuses the save regardless.

- **`Models\Entry::getVisibleSections()` honours `allowsSections()` on the site**, the way `getVisibleAssets()`
  always honoured `allowsAssets()`. `Widgets\SectionStack` read `$entry->sections` directly, so an entry whose type
  declared no sections still rendered every one of them — the admin hid the tab and refused the routes while the
  frontend carried on. A project handing the stack its own list with `sections()` is unaffected.

- **`has<Feature>Enabled()` is `allows<Feature>()`, and the model answers for the type.** Whether an entry has
  assets, sections, categories or subentries was three unrelated mechanisms: a module flag read by
  `Models\Entry::has*Enabled()`, a `FIELD_*` marker in the type's `hiddenFields()` that each caller had to check
  itself, and `Models\Types\EntryType::showsCategories()`. The model is the single reader now — the installation's
  flag, the type's declaration and the record's own state, resolved in one place:

  ```php
  EntryType::make(2)->allowAssets(false)->allowSections(false)->allowCategories(false)->allowDescendants(false);
  SectionType::make(2)->allowEntries(false);
  CategoryType::make(2)->allowEntries(false)->allowDescendants(false);
  ```

  Renamed on `Models\Entry`: `allowsAssets()`, `allowsCategories()`, `allowsSections()`, `allowsDescendants()`,
  `allowsParent()`. On `Models\Section`: `allowsAssets()`, `allowsEntries()`. On `Models\Category`:
  `allowsDescendants()`, `allowsEntries()`, `allowsParent()` — the last two used to answer a bare `true` and now
  read the type. `Models\Section::FIELD_ENTRIES` is gone with `Media\…\AssetModelInterface::FIELD_ASSETS`.

  `Models\Types\EntryType::showsCategories()` and `showsCategoryDropdown()` are **not** renamed: they are
  tri-state grid settings with a default of their own to fall through to, where an `allow*()` is a plain `bool` a
  type can only narrow with.

- **A route no longer does what the submenu does not offer.** `Modules\Admin\Controllers\SectionController` and
  `EntryCategoryController` refuse an entry whose type has no sections or no categories, through the new
  `Modules\Admin\Controllers\Traits\EntryControllerTrait::isEntryAllowed()` hook — the module flags were not
  checked there either, so `enableSections => false` left the section routes open. `Models\Section::validateEntryId()`
  and `Models\EntryCategory` refused the write already; this refuses the page.

- **`Assets\TenantDropdownAssetBundle` is gone and `Modules\Admin\Widgets\Forms\Fields\TenantIdField` reloads the
  page.** The tenant select carried a script of its own that fetched the current URL with a `tenant` query parameter
  and replaced the parent select's `innerHTML` — so it reached the parents and nothing else, leaving the slug field
  spelling out the previous tenant's host. It uses `Skeleton\Widgets\Forms\Fields\Field::reloadsForm()` now, like
  the type select, and the bundle ships no JavaScript at all. The field no longer writes `data-id="tenant"` or a
  `data-value` per option.

  With it, `EntryActiveForm::setTenantFromRequest()` is `setTenant()` and **the record's own `tenant_id` wins over
  the request**. It did not: an entry belonging to one tenant, opened on the admin host of another, was silently
  re-tenanted by the form before it rendered, and the reload's posted value would have been discarded the same way.
  The request is still what seeds a *new* entry, through the `tenant` parameter or the admin's own host.

- **`Models\Section::FIELD_ENTRIES` is `'entries'`, not `'#entries'`**, and a type hiding it takes the tab out of
  `Modules\Admin\Widgets\Navs\SectionSubmenu` server-side rather than through a script; the same holds for the
  asset tab of both submenus and `Media\Models\Interfaces\AssetModelInterface::FIELD_ASSETS`. A project naming the
  marker through the constant needs no change.

  `Modules\Admin\Widgets\Forms\Fields\EntryParentIdSelectField` renders nothing where it used to render a hidden
  row for the tenant script to fill.

- **Entry menus replace `Entry::$show_in_menu` and `$show_in_footer`.** A project needing a third navigation — a
  copyright row, the two halves of a header — had to add a column of its own, and the two that shipped cost two
  columns where one does the job. `entry.menu_ids` is a JSON list of the menus an entry is in, added by
  `Migrations\M260915200000MenuIds`, which moves a v2 installation's `show_in_menu` into menu `1` and its
  `show_in_footer` into menu `2` before dropping both columns and their index.

  The menus themselves are `Models\Menus\Menu` definitions declared on `Module::$menus`, the way `$sectionSets`
  already are — a closure, since a menu's name is a `Yii::t()` result. Nothing is declared by default.
  `Menu::available(Closure|bool)` replaces `Models\Types\EntryType::showInMenu()` / `showInFooter()` and is
  decided per entry, and a menu an entry is already in stays offered and stays valid once it is unavailable.
  `Menu::autoload(false)` keeps a menu out of the one query a layout pays for.

  Gone with them: `Migrations\Traits\MenuColumnTrait`, `Migrations\Traits\FooterColumnTrait`,
  `Models\Traits\MenuAttributeTrait`, `Models\Traits\FooterAttributeTrait`,
  `Modules\Admin\Widgets\Forms\Traits\MenuFieldTrait` and `FooterFieldTrait` — and with them the `rules()`
  spread each needed, which made the checkbox vanish from the form without an error when it was forgotten.
  `Modules\Admin\Widgets\Forms\Fields\MenuIdsField` is in the entry form by default and renders nothing
  while no menu is declared

- **`Widgets\NavItems` is `Models\Collections\MenuCollection`.** It was a static class under `Widgets\` that was
  never a widget, and it only knew the two flags. `getItems($menu)`, `getRootItems($menu)` and
  `getSubmenuItems($parent, $menu)` replace `getMenuItems()`, `getMainMenuItems()`, `getSubmenuItems($parent)`
  and `getFooterItems()`; `getIsMenuItem()` and `getIsFooterItem()` are `Entry::isMenuItem($menu)`. Every
  autoloaded menu is still one query

- `Models\Queries\EntryQuery::andWhereMenu(int|BackedEnum ...)` filters by menu. Neither MySQL nor MariaDB can
  index a JSON list usefully, so a subset of the declared menus is a `JSON_CONTAINS()` per menu while all of
  them — what `MenuCollection` asks for — is the cheap `menu_ids IS NOT NULL`

- **`Migrations\M260908100000Tenant` no longer refuses to run without a canonical URL.** It still seeds the
  tenant from the console `urlManager.hostInfo` or `params['tenantUrl']` where one is configured; where neither
  is, the tenant is seeded without a URL and named after the application rather than the migration throwing.
  Such a tenant names no host, so `Models\Traits\PermalinkTrait::getPermalinkRequestUri()` records its
  redirects relative — which is all a single-tenant installation needs, and two of those could not tell their
  slugs apart anyway

- **`Widgets\MetaTags::registerImageMetaTags()` was a fatal on two counts**, both found by PHPStan level 7: it
  read `$model->assets` for a `Category`, which has none, and indexed `Media\Models\File::getTransformations()`
  — the relation to the generated derivatives — as if it were the module's preset array, which it stopped being
  when the presets became `Transformation` objects

- **The templates of `Widgets\Gallery`, `Widgets\SectionStack`, `Widgets\SectionGroup`, `Widgets\NavItems`,
  `Models\Collections\CategoryCollection`, `Models\Actions\PreloadEntrySiteRelations`, the three
  `Models\Actions\Reorder*` classes and `Modules\Admin\Widgets\Forms\Fields\EntryParentIdSelectField` are
  gone.** Each was generic over its own model and nothing ever specialised it, so every `@return array<int, T>`
  was a promise the body could not keep — a project that wrote `Gallery<MyAsset>` drops the argument

- `Models\Queries\EntryQuery::whereCategory()` takes a `Category` or an id, not a list: the list was in the
  docblock only, and `whereCategories()` is what takes several


- `Models\Traits\SlugAttributeTrait::$slugMaxLength` is an `int`, as `Models\Permalink` already declared its own:
  the `false` it also accepted reached `mb_substr()`, which rejects it

- **`Models\Sets\SectionTemplate` takes an int backed enum**, like the skeleton `Models\Definitions\Definition`
  it is declared beside, so a project naming its section types in an enum writes
  `SectionTemplate::make(SectionType::Text)` rather than repeating the `->value`.

- **Sections are deleted in bulk.** `Modules\Admin\Widgets\Grids\SectionGridView` renders a `CheckboxColumn`
  and a footer offering `Modules\Admin\Controllers\SectionController::actionDeleteAll()`, which hands the
  selection to the new `Models\Actions\DeleteSections`. The column only appears where the action makes sense —
  more than one section and `Entry::AUTH_ENTRY` — which `SectionGridView::$showSelection` also turns off in one
  place.

  `Models\Section::afterDelete()` honours `getIsBatch()` now, as its `afterSave()` already did, so the action
  recounts each entry the selection spans once instead of once per section. **A caller deleting a section with
  `setIsBatch(true)` owns `entry.section_count`** and has to call `recalculateSectionCount()` itself; nothing in
  the bundle did so before.

- **A project can declare section sets**, groups of sections an entry is given in one go. A set is
  `Models\Sets\SectionSet`, a skeleton `Models\Definitions\Definition` — a value, a name and an icon — holding a
  list of `Models\Sets\SectionTemplate` objects, each a section type plus the attribute values the new section
  starts out with. They are declared on the module, beside
  the feature flags, rather than on the model: a set is a project-level catalogue, not per-record state.

  ```php
  'modules' => [
      'cms' => [
          'sectionSets' => fn (): array => [
              SectionSet::make(1)
                  ->name(Yii::t('app', 'Landing page'))
                  ->sections(
                      SectionTemplate::make(Section::TYPE_DEFAULT)->attribute('name', 'Intro'),
                      SectionTemplate::make(Section::TYPE_DEFAULT),
                  ),
          ],
      ],
  ],
  ```

  The value must be a closure, because a set's name is a `Yii::t()` result and a configuration file is read before
  the application has an `i18n` component. `Module::getSectionSets()` resolves, validates and caches the
  declaration, and `findSectionSet()` looks one up; a set without a name, without sections, sharing a value with
  another or naming an undeclared section type throws there. `Models\Actions\CreateSectionSet` inserts them as a
  batch and `Modules\Admin\Controllers\SectionController::actionCreateSet()` is the endpoint.

  `SectionSet::available()` scopes a set to the entries it makes sense for — by type, by tenant, by anything the
  entry knows — the way `Models\Types\Type::available()` scopes a type:

  ```php
  SectionSet::make(2)
      ->name(Yii::t('app', 'Article layout'))
      ->available(fn (Entry $entry): bool => $entry->type === Entry::TYPE_ARTICLE)
      ->sections(...),
  ```

  It is read off the **entry**, never off the request: the admin edits entries of any tenant, so the request's
  tenant is not the entry's. `actionCreateSet()` enforces it as well as the modal's select, and answers an
  unavailable set with the same 404 as an unknown one rather than confirming that it exists.

- **The section index header is a `Widgets\Navs\SectionActionDropdown`**, not a
  `Widgets\Buttons\SectionCreateButton`. The dropdown takes either a model — the section actions, unchanged — or
  the index page's `SectionActiveDataProvider`, in which case it offers the create button and, where a project
  declared sets, the new `Widgets\Buttons\SectionSetButton` and its modal.

- **`Models\EntryAsset` and `Models\SectionAsset` are registered with the `search` component**, so an asset's
  caption and alt text are findable. Run `./yii search/rebuild` once to index the rows that already exist.

- **Linking or removing a nested category reports the whole branch.** `inheritNestedCategories` links a category's
  ancestors and removes its descendants along with it, which the flash claimed was one category;
  `Models\EntryCategory::$inheritedEntryCategories` collects what the cascade touched and
  `getAffectedCategoryCount()` counts it, so `ENTRY_CATEGORY_SUCCESS_LINKED` and `ENTRY_CATEGORY_SUCCESS_REMOVED`
  take a `count` parameter and are plural messages in all four languages.

- `Migrations\M260915150000CustomAttributesColumn` moves `entry.custom_attributes` after `description` and
  `category.custom_attributes` after `slug`, and `Migrations\M260915180000SectionCustomAttributesColumn` moves
  `section.custom_attributes` after `position` — cosmetic column order only.

- **`category.title` and `category.description` are custom attributes**, moved by
  `Migrations\M260915120000CategoryMeta`. A category has no URL of its own, so its meta pair is read where the
  category is rendered rather than queried; the entry keeps both as columns. `Category` declares them as default
  definitions, so nothing has to be configured — but a project that translated them moves them from
  `i18nAttributes` to `translatableAttributes`, and `CategoryActiveForm` keeps rendering them itself through
  `getCustomAttributeFields(except: ['title', 'description'])`, so the description stays the taller textarea.

- **The free text of the cms models left the schema.** `entry.content`, `category.content` and `section.name`,
  `section.slug` and `section.content` are custom attributes in the `custom_attributes` column now, moved by
  `Migrations\M260915100000CustomAttributes`. `Models\ActiveRecord::$contentType` and `$htmlValidator` are gone
  with them: a section declares `content` as an `HtmlCustomAttribute` itself, and an entry or a category that
  needs one declares it, which is what `$contentType = false` used to say. `Section` carries `name`, `content`
  and `slug` as default definitions, has a `translatableAttributes` property in place of `i18nAttributes` (a
  translated value lives under its suffixed key in the JSON, not in the `translation` table) and validates the
  slug against the sections of its own entry rather than through `UniqueValidator` — it is the section's HTML id,
  so that is as far as uniqueness has to reach. `Models\Traits\SlugAttributeTrait` therefore left `Section`,
  which keeps `generateUniqueSlug()` of its own, and `Models\CustomAttributes\SlugCustomAttribute` inflects the
  slug in every language. `Modules\Admin\Widgets\Forms\Traits\ActiveFormFieldsTrait::getContentField()` and
  `getLinkField()` are gone; the fields come from the definitions. See UPGRADE.md

- **`Models\Traits\MetaImageTrait::getTypes()` is an instance method**, with every other type declaration —
  drop `static` from your own overrides, see the skeleton's `UPGRADE.md`.

- **A picker grid no longer leads out of itself.** Clicking the name in `Grids\SectionEntryGridView`,
  `Grids\SectionParentEntryGridView` or `Grids\EntryCategoryGridView` opened the entry or category and cancelled
  the very flow the user was in, and the count badges led to the record's sections, assets or entries. A grid now
  says what it is through `isPicker()` on `Grids\EntryGridView` and `Grids\Traits\CategoryGridTrait`, and a
  picker:

  - drills into the subentries or subcategories from the name and the type icon — the same URL the child count
    badge carries — and renders plain text when there are none, through the new `getRecordUrl()` hook, which the
    category ancestors follow too;
  - renders every *other* count badge unlinked (still a `.badge`, just a `div`), the child count excepted since
    it drills further into the picker;
  - offers the record's own page as an external link button that opens in a new tab (`getAdminLinkButton()`).

  `EntryGridView` also gained `getDescendantUrl()`, and `CategoryGridTrait` `hasBranchesEnabled()` and
  `getBranchUrl()`, which `getBranchCountColumn()` and the hook share.

- **`author` is detached from `admin`, and gains the media permissions.**
  `Migrations\M260914210000AuthorRole` removes it from `admin`, which lists the permissions themselves now, and
  adds `File::AUTH_FILE` and `Folder::AUTH_FOLDER` to it, since `yii2-media` dropped the `media` role that used to
  carry them. `author` is an editor's role to assign, not something another role holds.

- **The sitemap logic left the models.** `Sitemap\EntrySitemap` and `Sitemap\CategorySitemap` — both
  `Sitemap\RecordSitemap`, which builds a URL from `getRoute()` and `updated_at` — replace
  `Models\Traits\SitemapTrait`, `Entry::getSitemapQuery()`, `Entry::getSitemapUrl()`,
  `Category::getSitemapQuery()` and `ActiveRecord::includeInSitemap()`, and neither model implements a sitemap
  interface any more. `Module::$enableImageSitemaps` became `EntrySitemap::$enableImages`, since it is an option
  of the sitemap rather than of the module. `CategorySitemap` filters by status in the query, where the model
  filtered in PHP after the index had already counted the record. See UPGRADE.md

- **The cms types are classes.** `Models\Types\Type` carries the two options every cms model reads (`viewFile()`,
  `cssClass()`); `Models\Types\EntryType` adds `orderBy()`, `sort()`, `showCategories()`,
  `showCategoryDropdown()`, `showInMenu()` and `showInFooter()`; `Models\Types\SectionType` adds the four
  `Widgets\SectionStack` stages — `visible()`, `group()`, `wrapper()`, `collect()` — plus `entriesOrderBy()`,
  `entriesTypes()` and `gridContent()`; `Models\Types\CategoryType` adds nothing. `EntryType` and `SectionType`
  also carry the media `sizes()` and `transformations()`. `SectionType::entriesTypes()` is validated against the
  entry's own declarations, which the array never was. `Models\Section::FIELD_ENTRIES` replaces the magic
  `'#entries'` string, and `Models\Traits\MetaImageTrait::getMetaImageTypeOptions()` is `getMetaImageTypes()`,
  composing with the spread operator instead of `+` — which silently discarded a colliding key — and declaring
  the meta image's admin visibility through `available()` rather than the `visible` key nothing read in v3. See
  UPGRADE.md

- **Renaming an entry back to an earlier slug no longer builds a redirect loop.**
  `Models\Actions\SavePermalinks::updatePreviousRedirects()` repointed every redirect aimed at the old URL and
  ignored the result, so the one whose `request_uri` is where the entry now lives was left pointing at a URI the
  entry no longer has, redirecting that URL to itself; it is deleted instead, and a genuinely failed update is
  reported. It needs the host-qualified form of the new URI to recognise that row — `request_uri` and `url` are
  never comparable as strings — which `insertRedirect()` now resolves and passes in, so the skeleton's
  `Redirect::validateUrl()` is not what protects this path
- `Widgets\Sections` is replaced by `Widgets\SectionStack` and `Widgets\SectionGroup`. The stack owns the
  ordered list and runs the filter, collect, group and wrap stages; a group is a widget of its own, renders one
  run of sections through its view and is that view's `$context` again — the port had dropped the third argument
  of `View::render()`, which made `Sections::renderAdjacentSectionsByType()` and its two siblings unreachable.
  A section's render behaviour is declared in its type options: `viewFile`, `visible`, `group`, `wrapper` and
  `collect`, which is what replaces the `hasSameViewFile()`, `getSectionViewFile()` and `renderSectionsInternal()`
  overrides. `SectionStack::visible()` is the widget's own visibility, as on every other widget — it used to
  double as the per-section default, which was a `TypeError` under `strict_types` — and sections are filtered
  with `filter()`. `$section->position` is still renumbered over the visible sections; the neighbours the issue
  asked for are `SectionStack::getPrevious()` / `getNext()` and `SectionGroup::getPrevious()` / `getNext()`,
  and nothing is written onto the model. See `UPGRADE.md`
- `Modules\Admin\Widgets\Grids\EntryGridView::getNameColumnContent()` returns `string|Stringable`, as
  `CategoryGridTrait` and `SectionGridView` already did. It declared `string` while composing the link from
  `Html\A`, so a grid rendering neither the frontend URL nor the category buttons was a `TypeError`
- `Models\EntryAsset` and `SectionAsset` declare `@extends Asset<Entry>` / `@extends Asset<Section>` in place of
  their narrowed `getModel()` overrides, see `yii2-media`
- `Modules\Admin\Widgets\Navs\Traits\EntryHeaderTrait` and the entry category index translate through
  `COMMON_ENTRIES` and `COMMON_CATEGORIES`; the former used `Yii::t('app', 'Entries')`, which is the host
  application's category. `COMMON_SECTION_ENTRIES` and `CATEGORY_CREATE_TITLE` had no English text
- **One permission per admin-managed model.** `Models\Entry::AUTH_ENTRY` (`entry`) replaces the 17 permissions of
  entries, entry assets, entry categories, sections and section assets; `Models\Category::AUTH_CATEGORY`
  (`category`) replaces the four category ones. `Models\Section` declares no permission of its own — a section is
  only ever edited through its entry — and `Models\EntryAsset::getPermissionName()` / `SectionAsset` return
  `Entry::AUTH_ENTRY` and lost their `$action` parameter. `Migrations\M260914110000AuthItems` grants the new item
  to every parent and assignee of any old one, so an account that held `entryUpdate` alone now manages entries
  outright. `findEntry()`, `findSection()` and `findCategory()` lost their permission argument, and no `can()` call
  takes a record any more
- `Models\Actions\ReorderEntries`, `ReorderEntryCategories`, `ReorderCategories`, `ReorderSections` and
  `ReorderSectionEntries` pass a skeleton `I18n\Message` to `Trail::createOrderTrail()`, so the trail reads in the
  language of whoever looks at it rather than the one the editor happened to use
- `Models\Entry`, `Section`, `Category`, `EntryCategory` and `SectionEntry` implement the skeleton's
  `Models\Interfaces\AdminModelInterface` through `TrailModelInterface`: `getTrailModelName()` and
  `getTrailModelType()` are `getAdminName()` and `getAdminType()`, and the boilerplate name is
  `Models\Traits\AdminModelTrait`'s. A section is now named by its `name` in the trail when it has one
- `Models\Entry`, `Section` and `Category` are searchable: they implement the skeleton's
  `Models\Interfaces\SearchableInterface`, declare their indexed attributes and gate their hit on
  `entryUpdate`, `sectionUpdate` and `categoryUpdate`. `Bootstrap` registers them on the `search` component
- `Modules\Admin\Widgets\Navs\EntrySubmenu` and `SectionSubmenu` build their assets item from the media
  `Modules\Admin\Widgets\Navs\AssetSubmenuItem`. The entry submenu's item had no id, so a file upload could not
  refresh its counter out of band
- `Widgets\NavItems::$_entries` is `$entries`, dropping the underscore prefix a private or protected property
  no longer carries
- `Models\ActiveRecord::rules()` and `attributeLabels()` no longer call the skeleton's removed
  `ModelTrait::getTraitRules()` / `getTraitAttributeLabels()`, which discovered trait hooks by reflection.
  `Models\Traits\MenuAttributeTrait` and `Models\Traits\FooterAttributeTrait` renamed theirs to
  `getMenuAttributeRules()` / `getMenuAttributeLabels()` and `getFooterAttributeRules()` /
  `getFooterAttributeLabels()`, and a model using either trait must spread them in its own `rules()` and
  `attributeLabels()` — the same change the `#[Configure]` removal made to the widget traits. A model that does
  not is left with an attribute that is neither safe nor validated, and `Widgets\Forms\Fields\Field` renders
  nothing for it, so the checkbox silently disappears from the form. See `UPGRADE.md`
- `Modules\Admin\Widgets\Grids\Buttons\Traits\FrontendUrlTrait::configureDefaultUrl()` is no longer called
  automatically — the skeleton's `#[Configure]` attribute is gone. `FrontendLinkButton` and
  `Modules\Admin\Widgets\Navs\FrontendLink` call it from their own `configure()`, and so must any other class
  using the trait
- `esbuild.js` uses the skeleton's shared `esbuild.config.js`. The entry point moved to
  `resources/assets/src/js/dropdown.ts` and its output to `resources/assets/dist/js/dropdown.js`, which is the layout
  every other bundle already used; `Assets\TenantDropdownAssetBundle::$js` follows
- `CategoryCollection::invalidateCache()` also drops the static list, which it left in place before, so a saved category
  is seen by the next `getAll()` in the same process; `reset()` drops the static alone and `Bootstrap` calls it, so
  an application starts without the categories of the one before it. `$_categories` is `$categories`
- `EntryQuery::whereSection()` lost its `$eagerLoading` parameter and takes the join type second: the section entry
  is read off the joined row (`ActiveQuery::selectWith()`) rather than queried again. `whereCategory()` does the
  same for a single eager-loaded category; `whereCategories()` keeps Yii's `joinWith()`, one relation cannot be
  read off two joins. The admin category list and `Category::insertEntryCategoryAncestors()` read the entry
  category off the join as well
- An entry's redirects are recorded host-qualified: `request_uri` is the tenant's host plus the path
  (`www.example.com/de/old`), so a renamed slug only redirects on its own tenant and the same slug can be renamed
  on two tenants. `PermalinkTrait::getPermalinkRequestUri()` builds that form; `getPermalinkUrl()` keeps the
  tenant in the route again (relative on the entry's host, absolute elsewhere) and is the redirect target
- `EntryQuery::whereUri()` reads the matched permalink off the joined row (`ActiveQuery::selectJoinedRecord()`) and
  hands it to the entry (`PermalinkTrait::populatePermalink()`), so a site request resolves the entry and its URL in
  one query;
  `SiteController::getQuery()` no longer eager loads `permalinks`. `getPermalink()` reads that record when the
  relation is not loaded, and anything needing the full set still loads it. `whereUri()` on a query without a
  select now selects the entry's columns — before, `SELECT *` over the join let the permalink's `id` overwrite
  the entry's
- `VirtualSlugTrait` populates one slug attribute at a time: reading the current language's slug no longer
  materialises every other language, which loaded the permalink relation the URI lookup had already answered —
  a second permalink query on every site request of a translated slug. `populateSlugAttributes()` is
  `populateSlugAttribute(string $name)`, and `$_slugsPopulated` is the keyed `$populatedSlugs`
- `Entry::afterSave()` writes the permalinks without validating them again: `validateSlug()` already did, so a
  rename runs one uniqueness check instead of two. `PermalinkTrait::savePermalinks()` and `SavePermalinks`
  take `bool $runValidation` (default `true`, the console rebuild keeps validating). `SavePermalinks` also stops
  re-querying the relation after the write and hands the entry the records it wrote through
  `PermalinkTrait::populatePermalinks()`; `buildPermalink()` keeps one unsaved record per language so validation
  and save work on the same object; a redirect that fails validation is logged like a failed permalink
- Requires `davidhirtz/yii2-tenant`. `yii2-cms-tenant` is gone; its behaviour lives here and in the tenant
  bundle, and a project `Entry` extends `Models\Entry` again. See `yii2-skeleton/UPGRADE.md`, "3.0.0 — Tenants"
- `Models\Entry` has a NOT NULL `tenant_id`: it uses the tenant `TenantRelationTrait`, validates the attribute
  with the new `Validators\TenantIdValidator` (an empty value resolves to the default tenant), rejects a parent
  from another tenant, spreads `getTenantRouteParams()` into `getRoute()`, scopes `findSiblings()`, and pushes a
  changed tenant to its descendants and their permalinks. Added `Models\Actions\UpdateTenantEntryCount`, which
  owns the `tenant.entry_count` column
- Added `Models\Events\TenantBeforeDeleteEventHandler` and `TenantAfterSaveEventHandler`, subscribed from
  `Bootstrap` on the `Tenant` model events. `Bootstrap` also maps the tenant `TenantGridView` to the cms
  subclass that adds the entry-count column — the one container mapping that survives
- `Models\Queries\EntryQuery` uses the tenant `TenantQueryTrait`: `whereUri()` and `selectSitemapAttributes()`
  scope to the current tenant. `Modules\Admin\Data\EntryActiveDataProvider` gained `$tenantId`, resolved from
  `?tenant=` and then the URL manager; `EntryActiveForm` renders the new `Forms\Fields\TenantIdField` (first
  row with several tenants, last with one) and `EntryGridView` a tenant filter dropdown (none with one tenant).
  Added `Assets\TenantDropdownAssetBundle` and the esbuild setup that builds it
- **Category URLs are removed.** `Module::$enableCategoryUrls`, `Category`'s permalink implementation,
  `SiteController::renderCategory()` and `resources/views/site/category.php` are gone. `Category::getRoute()`
  returns the filtered entry index, which was already the shipped default
- **`permalink` is an entry-only table.** `model_class` / `model_id` are `entry_id` with an `ON DELETE CASCADE`
  foreign key, and the row carries the entry's `tenant_id`; the unique indexes are `(tenant_id, language, uri)`
  and `(entry_id, language)`. Removed `Models\Interfaces\PermalinkInterface`, `Models\Actions\DeletePermalinks`
  and the `permalink/prune` command — the cascade replaces them; the redirect cleanup a deletion still needs
  moved to `Models\Actions\DeletePermalinkRedirects`. `PermalinkQuery::whereModel()` is gone;
  `EntryQuery::whereSlug()` / `whereNotSlug()` are `whereUri()` / `whereNotUri()`, which join the table and
  honour the `LANGUAGE_ALL` fallback the old slug subquery ignored
- Added the idempotent `Migrations\M260908100000Tenant`: it seeds one tenant from `params['tenantUrl']` or the
  console `urlManager.hostInfo` (and aborts rather than guessing), makes `entry.tenant_id` NOT NULL and adds
  `tenant.entry_count`. `M260909100000Permalink` was rewritten in place for the final table shape and
  `M260912091000PermalinkModelClass` deleted

- `Modules\Admin\Data\CategoryActiveDataProvider::$category` is `$parent`, which is what it holds: the
  category the listed ones are nested under. `EntryActiveDataProvider` keeps both `$category` and `$parent`,
  which are different filters
- `Models\ActiveRecord` implements the skeleton `Models\Interfaces\AdminRouteInterface` instead of declaring
  `getAdminRoute()` abstract, and dropped its `getTrailModelAdminRoute()`
- `EntryAssetController` and `SectionAssetController` extend the skeleton `Controller` and use the media
  `AssetControllerTrait`, at `/admin/cms/entry-asset/index?entry=<id>` and `/admin/cms/section-asset/index?section=<id>`
- `Modules\Admin\Controllers\AssetController` split into `EntryAssetController` and `SectionAssetController`,
  at `/admin/cms/entry-asset` and `/admin/cms/section-asset`, each with one model type and one set of
  permissions instead of an `$entry` / `$section` branch in every action. Their views live under
  `resources/views/admin/entry-asset/` and `section-asset/`, so `Widgets\Navs\AssetHeader` and `AssetSubmenu`
  — which existed only to dispatch between the two — are gone. `Widgets\Navs\EntrySubmenu` and
  `SectionSubmenu` gained `additionalActiveRoutes()`
- `Modules\Admin\Controllers\AssetController` declares its own access rules and permission checks against the
  `AUTH_ENTRY_ASSET_*` and `AUTH_SECTION_ASSET_*` constants, and calls the media action bodies
- Added `Tests\Migrations\AssetMigrationTest`, which replays `M260912110000Assets` against the kept `cms_asset`
  table: the subclass dispatch, the text columns and translations landing in the JSON, the trail rewrite and the
  fallback for an asset deleted before the migration
- `Models\Asset` is gone. `Models\EntryAsset` and `Models\SectionAsset` are subclasses of the media
  `Hirtz\Media\Models\Asset` and share its `asset` table; `Models\Entry` and `Models\Section` implement
  `AssetModelInterface`. `$asset->parent` is `$asset->model`, `isEntryAsset()` is `instanceof EntryAsset`, and
  `entry_id` / `section_id` are `model_class` / `model_id`. See `yii2-media/UPGRADE.md`
- `M260912110000Assets` copies `cms_asset` into `asset` with the ids unchanged, moves the text columns and their
  translations into the JSON, rewrites the trail rows to the two subclasses and folds `file.cms_asset_count` into
  `asset_count`. It asserts its own result and rolls back on a mismatch; `safeDown()` returns `false`, because the
  trail rewrite is not cleanly reversible. `cms_asset` is kept as the validation reference and dropped by a later
  migration
- `Models\Traits\MetaImageTrait` moved here from `yii2-media`; its type is visible on an `EntryAsset` only.
  `Models\Traits\VisibleAttributeTrait` moved to `yii2-skeleton`
- Removed `Models\Queries\AssetQuery`, `Models\Actions\DuplicateAsset`, `ReorderAssets`, `DuplicateAssetsTrait`,
  `Models\Events\FileBeforeDeleteEventHandler`, `Modules\Admin\Data\AssetArrayDataProvider`,
  `Widgets\Forms\AssetActiveForm`, `Widgets\Grids\AssetGridView`, `FileAssetGridView`, `FileAssetGridContainer`,
  `Columns\AssetThumbnailColumn`, `AssetCountColumn`, `Navs\AssetActionDropdown`, `AssetParentActionDropdown` and
  `Controllers\Traits\AssetControllerTrait` — the media equivalents replace them.
  `Modules\Admin\Controllers\AssetController` extends the media `AbstractAssetController` and keeps its views
- `Section::beforeDelete()` deletes its assets unconditionally: an entry deletion no longer sweeps them through a
  shared `entry_id`. `Section::updateRelatedAssets()` is gone, and `Entry::getVisibleAssets()` no longer filters by
  `section_id`
- `Models\Traits\VisibleAttributeTrait` moved to `Hirtz\Skeleton\Models\Traits`; it depends on nothing in this
  bundle and models outside it need it
- `permalink` names its owner in `model_class` instead of `model` (`M260912091000PermalinkModelClass`), following the
  skeleton's polymorphic tables. `Models\Permalink::$model` is `$model_class`; `isModel()` is unchanged
- `Models\ActiveRecord` implements `CustomAttributeInterface`, so `Entry`, `Section`, `Asset`, `Category` and every
  model extending it can declare typed custom attributes through the `customAttributes` key of their type options.
  Added the `custom_attributes` column to `entry`, `section`, `cms_asset` and `category`; it is excluded from the trail
- The admin forms render the custom attribute fields of the model's current type, and
  `ActiveFormFieldsTrait::getTypeField()` returns a `TypeSelectField`, which reloads the form through htmx when the
  types render different fields. `EntryController`, `SectionController`, `AssetController` and `CategoryController`
  guard their save with `Request::isFormReload()`
- `Test\Models\TestSection` gained `TYPE_LINK_LIST` with a repeatable `links` group and a translatable `subtitle` on
  `TYPE_HEADLINE`

- `PermalinkTrait::buildPermalink()` looks the record up by its exact language, and `SavePermalinks` removes the
  records of languages the model no longer writes. Before, an entry whose slug became an `i18nAttribute` after it
  was saved had its language-agnostic permalink rewritten with the translated slug instead of getting one record per
  language
- Translated attributes of `Entry`, `Section`, `Category` and `Asset` moved from their `_xx` columns into the
  skeleton's `translation` table (`M260910110000Translations`). `Models\ActiveRecord` implements
  `TranslationInterface` and uses `TranslationTrait`
- `PermalinkTrait::savePermalinks()` returns the `SavePermalinks` action instead of the changed languages, so a
  caller can read both `getChangedLanguages()` and `getSlugChanges()`; `SavePermalinks::save()` returns `void`.
  `Entry` uses it rather than building the action itself
- `VirtualSlugTrait::updateOldSlugAttributes()` was removed. The skeleton's `updateOldVirtualAttributes()`
  covers the slug attributes, which the trait already adds to `getVirtualAttributes()`
- `VirtualSlugTrait` now only adds the permalink-backed slug to the skeleton's virtual attribute mechanism;
  the `attributes()`, `getColumnAttributes()`, `__get()`, `insertInternal()` and `updateInternal()` overrides
  moved to `TranslationTrait`. `Entry` excludes `slug` from its translation attributes
- `Section`'s slug uniqueness uses `Hirtz\Skeleton\Validators\UniqueValidator` and the validator's default
  `when` check; the hand-written `when` closure was copied verbatim into the per-language rule and checked the
  source-language attribute
- `EntryQuery::matching()` and `CategoryQuery::selectSitemapAttributes()` no longer put a translated attribute
  name into the SQL; `Category::getSitemapQuery()` eager loads every language's translations
- Entry and category URLs now live in a dedicated `Permalink` record instead of `slug` / `parent_slug` columns. 
  `Hirtz\Cms\Models\Permalink` (with `PermalinkQuery`) stores the full resolvable path in
  `uri` (unique per `language`), the editable leaf in `slug`, and its owner as a polymorphic `model` / `model_id`
- `Entry`'s virtual slug is read from its permalink lazily (on first access) rather than on every `afterFind()`, so
  loading an entry without touching its slug or route no longer queries the permalink table. The machinery moved from
  `PermalinkTrait` to a dedicated `VirtualSlugTrait` (used only by `Entry`, which has no slug column), dropping the
  `hasVirtualSlug()` flag. A renamed slug is recorded on the entry's trail from the permalink's previous value, so it
  is correct even when the slug was never read before being written
- Consolidated the split entry/section asset controllers back into a single `AssetController`; entry
  and section assets are served under `admin/cms/asset/*` again (removed `EntryAssetController` and
  the planned `section-asset` route). Added the thin `AssetHeader` and `AssetSubmenu` dispatcher
  widgets, which render the parent-specific `EntryHeader`/`SectionHeader` and `EntrySubmenu`
  (plus `SectionSubmenu` for section parents). `actionCreate()` keeps the GET file-picker /
  POST-insert split. Moved the entry-asset views to `resources/views/admin/asset/`
- Added a `depth` column to the category table (`M260907100000Depth`), backfilled from the existing
  nested set, matching `NestedTreeTrait`'s new depth tracking
- Added `CategorySubmenu` and gave `CategoryHeader` a `ModelTrait`; the category update view now shows
  the category name (with frontend link) in the header and a submenu (general / subcategories) like
  entries. Subcategories are browsed through the category index (`parent` param) instead of an inline
  grid in the update view. Removed `CategoryParentGridView`. Added the `COMMON_SUBCATEGORIES` message
- Added `SectionActionDropdown` and `SectionDeleteButton`; the section update view now renders the
  actions (move/copy, duplicate, open website, delete) as a dropdown in the header. Removed
  `SectionPanel` and the inline `DeleteActiveForm` from the section update view
- Added `AssetActionDropdown`; the entry-asset update view now renders the actions (edit file,
  duplicate, remove asset, delete file) as a dropdown in `EntryHeader`. Removed `AssetPanel` and the
  inline `DeleteActiveForm`s from the entry-asset update view. Added the `ASSET_ACTION_DROPDOWN_DELETE`,
  `ASSET_ACTION_DROPDOWN_DELETE_MESSAGE` and `ASSET_ACTION_DROPDOWN_DELETE_FILE_MESSAGE` messages
- Added `CategoryActionDropdown` and `CategoryDeleteButton`; the category update view now renders the
  actions (create, view entries, open website, delete) as a dropdown in `CategoryHeader`. Removed
  `CategoryPanel` and the inline `DeleteActiveForm` from the category update view
- Removed the orphaned `EntryPanel` and `EntryDeleteFrom` widgets (superseded by `EntryActionDropdown`)
  and their unused `ENTRY_DELETE_FROM_*` / `ENTRY_DELETE_TITLE` messages
- Replaced `Canvas` with `Artwork`
- Changed `Entry::$path` and `Entry::$category_ids` to JSON `array` columns (were comma-separated
  strings); added migration `M260906100000Json` to convert existing data

## 2.4.10 (Jan 27, 2026)

- Added default value for `MetaTags::$transformationName`
- Enhanced `Entry::$description` validation to remove line breaks (Issue #20)

## 2.4.9 (Jan 26, 2026)

- PHP 8.5 compatibility fixes

## 2.4.8 (Jan 12, 2026)

- Fixed error in `MetaTags::registerDefaultHrefLangLinkTag()` if the default language was set to `false`

## 2.4.7 (Dec 2, 2025)

- Changed `Section::hasAssetsEnabled()` and `Section::hasEntriesEnabled()` to not check hidden fields for backend views
- Changed `EntryGridView` to remove parent entry scope and "orderByPosition" when category or search is selected

## 2.4.6 (Oct 21, 2025)

- Added Russian language support

## 2.4.5 (Sep 18, 2025)

- Enhanced `EntryQuery` to exclude index entry from allowed parents
- Enhanced `Entry::getCategoryIds` to return int[] instead of string[]

## 2.4.4 (Jul 18, 2025)

- Fixed `Entry` to reset position without `parent_id` change

## 2.4.3 (Jul 15, 2025)

- Changed `SiteController` URL route to be added to the end of the rules
- Changed `Entry` validation to always remove trailing slashes from the slug
- Fixed `Entry::validateSlug()` to also work with I18N slugs
- Fixed `SlugIndexTrait` trying to create a slug index on fields that have not been added yet
- Renamed `SiteController::$removeTrailingSlashes` to `SiteController::$redirectTrailingSlash`

## 2.4.2 (Jul 2, 2025)

- Fixed `AssetGridView` upload HTML ID

## 2.4.1 (Jul 1, 2025)

- Changed annotations for static analysis (Yii 2.0.53)
- Fixed `EntryCategory::insertCategoryAncestors()` for categories that don't have entries enabled
- Fixed `Asset::getTrailAttributes()`

## 2.4.0 (May 26, 2025)

- Requires PHP 8.3+

# 2.3.14 (May 22, 2025)

- Enhanced `SiteController::getQuery()` to always apply `EntryQuery::addSelectI18nSlugTargetAttributes()`
- Fixed `FrontendLink` section draft status (Issue #18)

# 2.3.13 (May 16, 2025)

- Added `SlugAttributeTrait::$slugLowercase` and `SlugAttributeTrait::$slugReplacement`

# 2.3.12 (May 15, 2025)

- Fixed setting `Entry::$parent_slug` before validation to be present during validation in duplicate action

# 2.3.11 (Apr 25, 2025)

- Added `Section::$entry_count` recalculation on entry delete (Issue #16)
- Added `Section` type attribute `visible` filter to `Sections` (Issue #15)
- Fixed a bug in `EntryActiveDataProvider` sorting where default orders would override the sorter
- Enhanced `Entry::hasParentEnabled()` check

## 2.3.10 (Mar 24, 2025)

- Added route to `SectionGridView::entriesCountColumn()`
- Enhanced `SectionEntryController::actionIndex()` to redirect to the default type if no type is set
- Enhanced `SectionLinkedEntryGridView` to link to the correct entry type if set
- Enhanced `SectionEntryGridView` to filter out unsupported entry types

## 2.3.9 (Mar 20, 2025)

- Fixed an issue where the entry slug database index was not created during migration

## 2.3.8 (Mar 19, 2025)

- Fixed issues with `Entry::$slugTargetAttribute` as type `string` or `null`

## 2.3.7 (Mar 6, 2025)

- Fixed `ReplaceIndexEntry::replaceIndexEntry()` return type

## 2.3.6 (Jan 23, 2025)

- Added `andWhereParentStatus()` to `NavItems::getEntryQuery()`
- Changed `Bootstrap` I18N configuration
- Enhanced `Canvas` to only use `AspectRatio` if the file has dimensions

## 2.3.5 (Dec 17, 2024)

- Removed `codecept_debug` call

## 2.3.4 (Dec 17, 2024)

- Fixed `MetaTags::registerImageMetaTags()` calculated header type

## 2.3.3 (Dec 4, 2024)

- Fixed `SectionController::actionCreate()` parameter type

## 2.3.2 (Dec 4, 2024)

- Fixed `SectionLinkedEntryGridView::getDeleteButton()` signature

## 2.3.1 (Dec 4, 2024)

- Fixed `FooterAttributeTrait::isFooterItem()` return type
- Renamed `AssetParentGridView` to `FileAssetParentGridView`
- Renamed `AssetFilePanel` to `FileAssetParentPanel`

## 2.3.0 (Nov 29, 2024)

- Added `AssetFilePanel` for `yii2-media` version 2.2 to display I18N assets in file view
- Added `FileBeforeDeleteEventHandler`
- Enhanced `CategoryGridTrait::getUrl()`
- Enhanced `EntryCategory` error logging (Issue #14)
- Removed `Asset::getParentName()`
- Renamed `Asset::getParentGridView()` to `Asset::getFilePanelClass()`
- Replaced `Asset::updateOrDeleteFileByAssetCount()` with `Asset::updateFileRelatedCount()`
- Replaced `Asset::getFileCountAttribute()` with `Asset::getFileCountAttributeNames()`

## 2.2.3 (Nov 19, 2024)

- Added `AspectRatio` helper
- Fixed default value for `Canvas::$embedViewFile`

## 2.2.2 (Oct 21, 2024)

- Fixed `M241001170341EntryParentStatus` for I18N tables

## 2.2.1 (Oct 2, 2024)

- Added URL schema to `MetaTags::registerHrefLangLinkTags()`
- Fixed `FrontendLink` for usage with `Category` models

## 2.2.0 (Oct 2, 2024)

- Added `Entry::$parent_status`
- Added `FrontendLink` helper widget
- Enhanced `Section::$position` in `Sections` to be reset after visibility filter was applied
- Fixed `EntryController::actionOrder()` authorization
- Removed `ParentIdValidator` and replaced it with `Entry::validateParentId()`

## 2.1.27 (Sep 6, 2024)

- Added `CategoryParentIdDropDown` and `EntryParentIdDropDown` widgets

## 2.1.26 (Aug 26, 2024)

- Added `Canvas::renderAdmin()` to render the `AdminLink` widget, use `{asset}` in `Canvas::$template` to render the
  link
- Added `Canvas::$setWrapperHeightWithAspectRatio` to set the wrapper height with the `aspect-ratio`
  CSS property, defaults to `true`
- Added `Canvas::$enableMaxWidth` to enable the `max-width` CSS property, defaults to `false`.
  Set `Canvas::$defaultMaxWidth` to prevent the setting of a `max-width` greater than the default value

## 2.1.25 (Aug 19, 2024)

- Changed `Bootstrap` to use `ApplicationTrait::addUrlManagerRules()` to prevent the initialization of the URL manager
  before the bootstrap is completed

## 2.1.24 (Aug 19, 2024)

- Added `SitemapInterface` to `Category` and `Entry` models
- Changed `EntryActiveDataProvider::prepareQuery()` visibility to `protected`
- Improved `Section::getEntriesOrderBy()` functionality

## 2.1.23 (Jul 23, 2024)

- Added `Section::getEntriesOrderBy()` and `Section::getEntriesTypes()` to filter manually picked section entries
- Added `EntrySiteRelationsBuilder::sortSectionEntriesByEntryAttributes()` to sort section entries by entry attributes
  after they have been loaded
- Renamed `Category::getEntryOrderBy()` to `Category::getEntriesOrderBy()`
- Renamed `Entry::getDescendantsOrder()` to `Entry::getDescendantsOrderBy()`

## 2.1.22 (Jul 23, 2024)

- Added `ReplaceIndexEntry` action and `EntryHelpPanel::getReplaceIndexButton()` (Issue #12)
- Changed `Hirtz\Cms\Modules\Admin\Module::$name` to `Module::getName()` to prevent translation issues
- Enhanced `EntrySiteRelationsBuilder` to populate entry parents if loaded
- Fixed index creation in `FooterColumnTrait` and `MenuColumnTrait` (Issue #11)
- Replaced RBAC strings with constants

## 2.1.21 (May 21, 2024)

- Fixed `EntryParentIdFieldTrait` to hide select field if results are empty
- Fixed an n+1 query issue in `EntrySiteRelationsBuilder` on invalid configuration

## 2.1.20 (Apr 17, 2024)

- Added `EntryParentIdFieldTrait::findEntries()` to allow custom queries
- Added `$i18nTablesRoute` to all admin controllers to allow custom routes for the language dropdown
  when `Module::$i18nTables` is enabled
- Enhanced `SetupController` to log model errors
- Renamed `Hirtz\Cms\Modules\Admin\Module::$url` to `$route` for clarity

## 2.1.19 (Apr 16, 2024)

- Enhanced `Entry::validateSlug()` to check against URL rules
- Enhanced `SetupController` to work with `Module::$enableI18nTables`
- Normalized `varchar` columns

## 2.1.18 (Apr 5, 2024)

- Updated admin according to `Hirtz\Skeleton\Modules\Admin\ModuleInterface`

## 2.1.17 (Mar 28, 2024)

- Added `Entry::validateSlug()` to check against real URL paths
- Added `SetupController::ensureFolder()` to create missing folder on setup
- Added `EntrySiteRelationsBuilder::$autoloadEntryAncestors` to load entry ancestors
- Removed select clause in `EntryParentIdFieldTrait::getEntries()`

## 2.1.16 (Jan 26, 2024)

- Changed the signature of `SlugAttributeTrait::isUniqueRule()` to accept any argument

## 2.1.15 (Jan 26, 2024)

- Added `SlugAttributeTrait::isUniqueRule()` to correctly translate I18N attributes in `$targetAttribute` (Issue #10)
- Fixed `ParentIdValidator` to set `parent_slug` to null on empty parent

## 2.1.14 (Jan 25, 2024)

- Fixed use of `File::getTransformationOption()` in `MetaTags` widget

## 2.1.13 (Jan 24, 2024)

- Added `ReorderCategories`
- Changed `Category::$title`, `Entry::$parent_slug` and `Entry::$title` to default to null

## 2.1.12 (Jan 24, 2024)

- Enhanced `EmbedUrlTrait`

## 2.1.11 (Jan 13, 2024)

- Added `EntryDeletePanel` (Issue #9)
- Fixed `M231104201316EmbedUrl` migration to only create I18N `embed_url` columns when needed

## 2.1.10 (Jan 12, 2024)

- Added `EntrySiteRelationsBuilder::getQuery()` to allow custom queries
- Fixed `Entry::isIndex()` to work with I18N slugs
- Removed `MetaTags::twitterCard`

## 2.1.9 (Jan 9, 2024)

- Enhanced `Canvas` widget to allow nullable `asset` attribute
- Fixed tests

## 2.1.8 (Jan 9, 2024)

- Fixed Rector (Issue #8)

## 2.1.7 (Jan 8, 2024)

- Added PHPDoc blocks to grids views
- Added `Hirtz\Media\Modules\Admin\Widgets\Grids\Traits\AssetColumnsTrait` usage to asset grid

## 2.1.6 (Jan 8, 2024)

- Enhanced `AssetThumbnailColumn` to display the asset
  via `\Hirtz\Media\Modules\Admin\Widgets\Grids\Columns\Thumbnail`
- Enhanced `SiteController` to remove trailing slashes from the slug (Issue #7)

## 2.1.5 (Jan 7, 2024)

- Fixed `Picture` embed

## 2.1.4 (Jan 7, 2024)

- Enhanced `SetupController` to also allow setting up categories
- Moved `CategoryCollection` to `Hirtz\Cms\Models\Collections`
- Removed `withFolder()` from `AssetQuery::withFiles()` query stack

## 2.1.3 (Jan 4, 2024)

- Moved `Category::getBySlug()` to `CategoryCollection::getBySlug()`

## 2.1.2 (Jan 4, 2024)

- Fixed `Entry::findSiblings()` to return the correct siblings with `parent_id` enabled

## 2.1.1 (Jan 3, 2024)

- Added GrumPHP configuration and pre-commit hook
- Added `I18nTablesTrait` to streamline the I18N table migrations
- Added `FooterColumnTrait` and `MenuColumnTrait`

## 2.1.0 (Dec 19, 2023)

- Added Codeception test suite
- Added GitHub Actions CI workflow
- Moved `DuplicateButtonTrait` from `yii2-cms` to `yii2-media`

## 2.0.23 (Dec 11, 2023)

- Enhanced `EntrySiteRelationsBuilder` to use cached folder queries

## 2.0.22 (Nov 28, 2023)

- Changed default `Category` slug attribute target to prevent n+1 queries created by `RedirectBehavior` (Issue #4)
- Changed `Asset::afterSave()` to always update the parent `updated_at` when an attribute was changed
- Removed "New Entry" button in `SectionEntryController::actionIndex` (Issue #6)

## 2.0.21 (Nov 18, 2023)

- Fixed bug in `EntryParentIdFieldTrait` where model status was not loaded correctly

## 2.0.20 (Nov 15, 2023)

- Enhanced the `SiteController` to allow entries to be extended to redirect before rendering the view
- Fixed a bug with the `Sitemap` URL generation

## 2.0.19 (Nov 14, 2023)

- Updated widgets to use `\Hirtz\Media\Helpers\Html` helper class

## 2.0.18 (Nov 14, 2023)

- Added helper methods for getting ancestors, children and descendants in `CategoryCollection`
- Fixed a bug which returned a wrong `Asset` order in `EntrySiteRelationsBuilder`

## 2.0.17 (Nov 12, 2023)

- Enhanced the `Gallery` widget, to only render the wrapper when there are assets to display

## 2.0.16 (Nov 10, 2023)

- Refactored `CategoryCollection::getByEntry()`
- Fixed a bug in `CategoryParentIdFieldTrait`where prompt options would not be initialized in some cases

## 2.0.15 (Nov 10, 2023)

- Fixed a bug in `EntryActiveDataProvider` which would ignore the parent entry in some cases

## 2.0.14 (Nov 10, 2023)

- Enhanced `Submenu` active nav items
- Fixed a bug in `EntrySiteRelationsBuilder` which prevented section entries from populating their related records
- Fixed a bug in `EntryActiveDataProvider` which would order section entries by their related position in the selection

## 2.0.13 (Nov 10, 2023)

- Changed the duplicate actions to keep the status of related records on duplicate
- Fixed `AdminLink` to use the link options

## 2.0.12 (Nov 10, 2023)

- Enhanced multiple widgets to check for `TypeAttributeTrait::getTypes()` via the dependency injection container

## 2.0.11 (Nov 9, 2023)

- Added `Navitems::getMainMenuItems()`

## 2.0.10 (Nov 9, 2023)

- Changed `NavItems` to use `EntryQuery::selectSiteAttributes()` by default

## 2.0.9 (Nov 9, 2023)

- Enhanced `SetupController` to initialize models with the dependency injection container

## v2.0.8 (Nov 9, 2023)

- Added `Category:hasDescendantsEnabled()` and `CategoryQuery::whereHasDescendantsEnabled()`
- Added `MenuFieldTrait`, `MenuFieldTrait` and `MenuColumn` classes
- Added `MenuFooterTrait` and `FooterFieldTrait` classes
- Added `NavItems` widget
- Changed the default `MetaTags::$assetType`  to `Asset::TYPE_META_IMAGE`
- Enhanced `CategoryActiveForm::parentIdField()` by extracting all related methods
  to `Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\CategoryParentIdFieldTrait`

## v2.0.7 (Nov 8, 2023)

- Changed view path resolution of `Canvas`, `Gallery` and `Sections` widgets

## v2.0.6 (Nov 7, 2023)

- Added the default view path to `Sections` widget
- Enhanced `Entry::getRoute()`, it now also returns the route when it has descendants
- Enhanced `EntryParentIdFieldTrait` to truncate long parent slugs

## 2.0.5 (Nov 7, 2023)

- Changed `Hirtz\Cms\Modules\Admin\Widgets\Forms\AssetActiveForm` to use `TypeFieldTrait` by default

## 2.0.4 (Nov 7, 2023)

- Added automatic default folder creation in `SetupController`
- Improved `VisibleAttributeTrait`

## 2.0.3 (Nov 6, 2023)

- Added `AdminLink` widget to display links to the backend
- Added `CategoryCollection::getByEntry()`
- Added `Hirtz\Cms\Modules\Admin\SetupController` to set up the entries
- Moved `Hirtz\Cms\Models\Traits\AssetParentTrait` to `Hirtz\Media\Models\Traits\AssetParentTrait`

## 2.0.2 (Nov 6, 2023)

- Added `Canvas` widget to display the assets in the frontend
- Added `DuplicateButtonTrait` to duplicate models
- Moved `Bootstrap` class to base package namespace for consistency
- Renamed `AssetViews` to `Gallery`
- Renamed `SectionViews` to `Sections`
- Renamed `MetaTags::register()` to `MetaTags::widget()` to match the other widgets
- Removed `ActiveRecord::updatePosition()`, `Category::updateEntryOrder()`, `Category::clone()`,
  `Entry::updateAssetOrder()`,`Entry::clone()`, `Entry::updateSectionOrder()`, `Section::clone()`,
  `Section::updateAssetOrder()`, `Section::updateSectionOrder()` use model actions found in
  `Hirtz\Cms\Models\Actions`
- Replaced `ModelCloneEvent` with `Hirtz\Skeleton\Models\Events\DuplicateActiveRecordEvent`

## 2.0.1 (Nov 4, 2023)

- Added `Hirtz\Cms\Models\Builders\EntrySiteRelationsBuilder` which loads all relations needed in the
  frontend `SiteController`
- Added `entryIndexSlug` which automatically loads the entry index page in the frontend `SiteController::actionIndex()`
- Added `enableUrlRules` to automatically register URL rules in the CMS Module config, defaults to `true`
- Changed `Module::$defaultEntryOrderBy` to `position` ascending

## 2.0.0 (Nov 3, 2023)

- Added `Hirtz\Cms\Module::$enableSectionEntries` option to disable section entries
- Changed namespaces from `Hirtz\Cms\admin\widgets\grid` to `Hirtz\Cms\admin\widgets\grids`
  and `Hirtz\Cms\admin\widgets\nav` to `Hirtz\Cms\admin\widgets\navs`
- Changed namespaces for `LinkButtonTrait` and `UpdateFileButtonTrait`
  to `Hirtz\Cms\admin\widgets\panels\Traits`
- Merged `Hirtz\Cms\yii2-cms-parent` into this package
- Moved source code to `src` folder
- Moved all models, data providers and widgets out of `base` folder, to override them use Yii's dependency injection
  container
- Removed `CategoryTrait` and `Category::getCategories()` in favor
  of `Hirtz\Cms\Models\Collections\CategoryCollection`
- Removed `ActiveForm::getActiveForm()`, to override the active forms, use Yii's dependency injection
  container

## 1.3.3 (Nov 3, 2023)

- Locked `davidhirtz/yii2-media` to version `1.3`, upgrade to version 2 to use the new media library