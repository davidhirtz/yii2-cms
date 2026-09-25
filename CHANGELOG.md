## Unreleased

- Added `Entry::getDefaultType()`
- Changed `EntryAsset` to declare only the `alt_text`, `loading` and `fetchpriority`

## 3.1.0 (September 24, 2026)

- Changed `Entry::updateSectionCount()` to renumber the sections `1..n` first, so a delete or a move leaves no gap;
  `M260924100000RenumberSectionPositions` closes the gaps an installation already holds
- Added setters for the `MetaTags` options; `transformation()` replaces `$transformationName` and defaults to the `og` transformation
- Changed the `Artwork` closures to stack and answer the element they receive, added `Artwork::makeMedia()`, and made `Gallery` render an `Artwork` per asset, configured through `Gallery::artwork()`; `viewFile()` is optional

## 3.0.0 (September 23, 2026)

- Renamed the namespace `davidhirtz\yii2\cms\` to `Hirtz\Cms\` and every directory to StudlyCase (`models\actions\` is `Models\Actions\`)
- Moved the views from `src/modules/admin/views/` and `src/views/` to `resources/views/admin/` and `resources/views/site/`
- Moved the admin routes from `/admin/<controller>` to `/admin/cms/<controller>`; the asset routes are `/admin/cms/entry-asset` and `/admin/cms/section-asset`
- Requires `davidhirtz/yii2-tenant`; `davidhirtz/yii2-cms-tenant` is gone and a project `Entry` extends `Models\Entry` again
- Changed `entry.tenant_id` to NOT NULL, validated by `Validators\TenantIdValidator`; `Models\Actions\UpdateTenantEntryCount` keeps `tenant.entry_count`
- Replaced the array type options with type classes: `getTypes()` is an instance method returning `Models\Types\EntryType`, `SectionType`, `CategoryType` or `BlockType` objects, declared through the container
- Moved `viewFile` and `cssClass` onto `Models\Types\Type`; `orderBy()`, `sort()`, `showCategories()`, `showCategoryDropdown()` onto `EntryType`; `visible()`, `group()`, `wrapper()`, `collect()`, `entriesTypes()`, `entriesOrderBy()` onto `SectionType`; `nameColumn` is `SectionType::gridContent()` and `Section::getNameColumnContent()` is `getGridContent()`
- Replaced the English message keys with `UPPER_SNAKE_CASE` keys (`COMMON_ENTRIES`, `ENTRY_CREATE_TITLE`, `SECTION_SUCCESS_ORDERED`); dropped the `ru`, `zh-CN` and `zh-TW` translations
- Replaced the 23 verb permissions (`entryCreate`, `entryAssetUpdate`, `sectionOrder`, `categoryDelete`, `assetUpdate`, …) with `Entry::AUTH_ENTRY` (`entry`) and `Category::AUTH_CATEGORY` (`category`)
- Removed the `AUTH_ENTRY_*`, `AUTH_SECTION_*`, `AUTH_CATEGORY_*` and `AUTH_ASSET_*` constants; a section, an asset, an entry category and an entry relation answer `getPermissionName()` with its owner's
- Changed `author` into a role to assign rather than one `admin` holds, and gave it the media `file` and `folder` permissions
- Moved entry URLs into `Models\Permalink` (`entry_id`, `tenant_id`, `language`, `uri`, `slug`); `entry.slug` and `entry.parent_slug` are gone and `Entry::$slug` is virtual (`Models\Traits\VirtualSlugTrait`)
- Renamed `EntryQuery::whereSlug()` to `whereUri()`; removed `addSelectI18nSlugTargetAttributes()` in favour of `withPermalinks()`
- Changed an entry's redirects to be recorded with the tenant's host (`PermalinkTrait::getPermalinkRequestUri()`), so the same slug can be renamed on two tenants
- Added the `permalink/rebuild` console command
- Moved the translated attributes of `Entry`, `Section` and `Category` from their `_xx` columns into the skeleton `translation` table; `find()` returns an `I18nActiveQuery`
- Moved `entry.content`, `category.content`, `category.title`, `category.description`, `section.name`, `section.slug` and `section.content` into the `custom_attributes` JSON column as custom attributes
- Changed `Section` and `Category` to declare those custom attributes by default; an entry `content` is declared by the project, and a translated one is named in `translatableAttributes`, never `i18nAttributes`
- Removed `ActiveRecord::$contentType` and `$htmlValidator`; a custom attribute is neither a query condition nor a sort
- Replaced `Models\Asset` with `Models\EntryAsset` and `SectionAsset`, subclasses of `Hirtz\Media\Models\Asset` in the shared `asset` table; `cms_asset` is copied and kept
- Renamed the asset's `entry_id` / `section_id` to `model_class` / `model_id`, `$asset->parent` to `$asset->model`, `populateParentRelation()` to `populateModelRelation()`; `isEntryAsset()` is `instanceof EntryAsset`
- Removed `Models\Queries\AssetQuery`, `Models\Actions\DuplicateAsset`, `ReorderAssets`, `DuplicateAssetsTrait`, `Models\Events\FileBeforeDeleteEventHandler`, `Forms\AssetActiveForm`, `Grids\AssetGridView`, `FileAssetParentGridView`, `Columns\AssetCountColumn`, `AssetThumbnailColumn`, `Controllers\AssetController` and `AssetTrait`; the media classes replace them
- Added `EntryAssetController` and `SectionAssetController` over the media `AssetControllerTrait`, with `remove` and `delete-all` in place of `duplicate`
- Replaced `section_entry` with the polymorphic `entry_relation` table (`model_class` / `model_id`): `Models\EntryRelation` is the base, `SectionEntry` and `BlockEntry` the subclasses, `Module::$entryRelations` the registry
- Renamed `$section->sectionEntries` to `entryRelations`, `$entry->sectionEntry` to `entryRelation`, `EntryQuery::whereSection()` to `whereRelatedModel()`, `EntryActiveDataProvider::$section` / `$innerJoinSection` to `$relatedModel` / `$innerJoinRelatedModel`
- Renamed `Models\Actions\ReorderSectionEntries` to `ReorderEntryRelations`, `Grids\SectionEntryGridView` to `EntryRelationGridView`, `SectionLinkedEntryGridView` to `LinkedEntryGridView`, `Columns\SectionEntryCountColumn` to `EntryRelationCountColumn`, `Buttons\SectionEntryCreateButton` to `EntryRelationCreateButton`; removed `Models\Traits\SectionRelationTrait`
- Replaced `Entry::$show_in_menu` and `$show_in_footer` with the JSON list `entry.menu_ids` and `Models\Menus\Menu` definitions on `Module::$menus`; `EntryQuery::andWhereMenu()` filters
- Replaced `Widgets\NavItems` with `Models\Collections\MenuCollection` (`getItems($menu)`, `getRootItems($menu)`, `getSubmenuItems($parent, $menu)`); `NavItems::getIsMenuItem()` / `getIsFooterItem()` are `Entry::isMenuItem($menu)`
- Removed `Models\Traits\MenuAttributeTrait`, `FooterAttributeTrait`, `Migrations\Traits\MenuColumnTrait`, `FooterColumnTrait`, `Forms\Traits\MenuFieldTrait`, `FooterFieldTrait` and `EntryType::showInMenu()` / `showInFooter()`; `Fields\MenuIdsField` is in the entry form
- Renamed `has<Feature>Enabled()` to `allows<Feature>()` on `Entry`, `Section` and `Category`; a type narrows with `allowAssets(false)`, `allowSections(false)`, `allowCategories(false)`, `allowDescendants(false)` and `allowEntries(false)` instead of a hidden-field marker
- Changed `Entry::getVisibleSections()` and `Section::getVisibleEntries()` to apply those capabilities on the site, and the admin to refuse a route whose tab is hidden (`EntryControllerTrait::isEntryAllowed()`, `SectionControllerTrait::isSectionAllowed()`)
- Replaced `Widgets\Sections` with `Widgets\SectionStack` and `Widgets\SectionGroup`; the render stages are declared on `SectionType` and `$context` in `_sections.php` is the `SectionGroup`
- Replaced `Sections::renderAdjacentSectionsByType()`, `renderSectionsByType()` and `renderSectionsByCallback()` with `SectionGroup::renderAdjacent()` and `renderWhere()`; `Sections::$isVisible` is `SectionStack::filter()`
- Replaced `Widgets\Canvas` with `Widgets\Artwork`, and the public properties of `Gallery`, `MetaTags` and `Artwork` with fluent setters (`MetaTags::make()->model($entry)`, `Gallery::make()->assets($assets)`)
- Moved `Widgets\AdminLink` to `Hirtz\Skeleton\Widgets\AdminLink`; it takes any `AdminModelInterface`, its default class is `admin`, and the shipped `_sections.php` names `relative` on the section as the positioned ancestor
- Replaced `Models\Traits\SitemapTrait`, `Entry::getSitemapQuery()`, `Entry::getSitemapUrl()`, `Category::getSitemapQuery()` and `ActiveRecord::includeInSitemap()` with `Sitemap\EntrySitemap` and `Sitemap\CategorySitemap` over `Sitemap\RecordSitemap`, registered by the project
- Moved `Module::$enableImageSitemaps` to `Sitemap\EntrySitemap::$enableImages`
- Replaced the admin `Navs\Submenu` with `EntrySubmenu`, `SectionSubmenu`, `CategorySubmenu` and `BlockSubmenu`, and added `EntryHeader`, `SectionHeader`, `CategoryHeader` and `BlockHeader` over the skeleton `ModelHeader`; a tab is widened with `getItem('assets')?->addRoute()`
- Changed `Entry`, `Section`, `Category`, `EntryCategory` and `EntryRelation` to implement the skeleton `AdminModelInterface`: `getTrailModelName()` / `getTrailModelType()` are `getAdminName()` / `getAdminType()`, and `getAdminParent()`, `getAdminIndexBreadcrumb()` and `getAdminSubtitle()` feed the headers
- Removed `Widgets\Panels\HelpPanel` and its five subclasses, `EntryDeletePanel`, `FileAssetParentPanel`, `UpdateFileButtonTrait` and `ActiveRecord::getTrailModelAdminRoute()`
- Renamed `Helpers\FrontendLink` to `Widgets\Navs\FrontendLink`, `Fields\EntryParentIdDropDown` / `CategoryParentIdDropDown` to `EntryParentIdSelectField` / `CategoryParentIdSelectField`, `Columns\EntryCountColumn` to `EntryEntryCountColumn`, `CategoryActiveDataProvider::$category` to `$parent`
- Renamed the controller traits `EntryTrait` / `SectionTrait` / `CategoryTrait` to `EntryControllerTrait` / `SectionControllerTrait` / `CategoryControllerTrait`, `Models\Builders\EntrySiteRelationsBuilder` to `Models\Actions\PreloadEntrySiteRelations` and its event to `Models\Events\EntrySiteRelationsEvent`
- Changed the admin forms to declare their fields in `getDefaultRows()` with protected getters: `EntryActiveForm::publishDateField()` is `getPublishDateField()`, `slugField()` is `SlugFieldTrait::getSlugField()`, `descriptionField()` is `MetaFieldsTrait::getDescriptionField()`
- Changed the entry form's tenant row into part of its defaults; `setTenantFromRequest()` is `setTenant()` and the record's tenant wins over the request
- Removed `ActiveForm::getSlugId()`, `ActiveFormFieldsTrait::getContentField()` and `getLinkField()`; the custom attribute fields come from the definitions
- Changed the create actions to build the record from the posted type (`instantiateFromPost()`); a type, tenant or parent change reloads the form server-side and the bundle ships no JavaScript
- Removed `SectionController::$autoCreateSection`; a section is inserted on submit like every other record
- Changed `Entry::$path` and `$category_ids` to JSON columns, `SlugAttributeTrait::$slugMaxLength` to `int`, and `EntryQuery::whereCategory()` to take one category (`whereCategories()` takes several)
- Changed a section's slug to be unique within its entry only (`Section::SLUG_MAX_LENGTH`); `SlugAttributeTrait` is off `Section`
- Changed `Section::afterSave()` and `afterDelete()` to honour `getIsBatch()`; a caller working in batch owns `entry.section_count` and `block.section_count`
- Changed a duplicated entry to be named "Copy of …" (`Models\Actions\DuplicateEntry`)
- Changed the bundle to ship one baseline migration, `Migrations\M260101000400CmsBaseline`; the v2 to v3 migrations live in `davidhirtz/yii2-upgrade`
- Added blocks, global sections placed through `section.block_id`: `Models\Block`, `BlockAsset`, `BlockEntry`, `Block::AUTH_BLOCK`, `Module::$enableBlocks`, `$enableBlockAssets`, `$enableBlockEntries`, `Models\Types\BlockType`, `BlockSectionType`, `SectionType::allowBlock()`, `Section::getVisibleBlock()`, `Fields\BlockIdSelectField`
- Added the `block`, `block-asset`, `block-entry` and `block-section` admin controllers, `BlockActiveForm`, `BlockGridView`, `BlockSectionGridView` and `BlockActionDropdown`
- Added section sets: `Module::$sectionSets`, `Models\Sets\SectionSet`, `SectionTemplate`, `Models\Actions\CreateSectionSet`, `SectionController::actionCreateSet()`, `Buttons\SectionSetButton` and `Navs\SectionActionDropdown`
- Added `Models\Interfaces\EntryRelationModelInterface` with `Models\Traits\EntryRelationModelTrait` and `Models\Interfaces\EntryRelationTypeInterface` with `Models\Types\Traits\EntryRelationTypeTrait`
- Added fulltext search: `Entry`, `Section`, `Category`, `Block` and the three asset classes implement the skeleton `SearchableInterface` and are registered on the `search` component
- Added the POST-only `status` action to the entry, section, category and asset controllers, and `delete-all` with `Models\Actions\DeleteSections` to the section controller
- Added `category.depth`, `Models\Actions\DeletePermalinkRedirects`, `CategoryCollection::reset()`, `EntryGridView::isPicker()` / `getRecordUrl()`, `SectionStack::getPrevious()` / `getNext()`, `Models\CustomAttributes\SlugCustomAttribute`, `Validators\MenuIdsValidator` and the cms `Grids\TenantGridView`
- Added `Models\Traits\MetaImageTrait` (moved from `yii2-media`); `getMetaImageTypeOptions()` is `getMetaImageTypes()`
- Added the skeleton `HintAlert` to the block pages (`BLOCK_INDEX_HINT`, `BLOCK_SECTION_INDEX_HINT`) and empty-grid messages to the section, linked entry and entry category grids

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