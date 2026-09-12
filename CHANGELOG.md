## 3.0 (in development)

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