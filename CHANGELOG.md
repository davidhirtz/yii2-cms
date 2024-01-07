## 2.1.4 (Jan 7, 2024)

- Enhanced `SetupController` to also allow setting up categories
- Moved `CategoryCollection` to `davidhirtz\yii2\cms\models\collections`
- Removed `withFolder()` from `AssetQuery::withFiles()` query stack

## 2.1.3 (Jan 4, 2024)

- Moved `Category::getBySlug()` to `CategoryCollection::getBySlug()`

## 2.1.2 (Jan 4, 2024)

- Fixed `Entry::findSiblings()` to return the correct siblings with `parent_id` enabled

## 2.1.1 (Jan 3, 2024)

- Added GrumPHP configuration & pre-commit hook
- Added `I18nTablesTrait` to streamline the I18N table migrations
- Added `FooterColumnTrait` and `MenuColumnTrait`

## 2.1.0 (Dec 19, 2023)

- Added Codeception test suite
- Added GitHub Actions CI workflow
- Moved `DuplicateButtonTrait` from `yii2-cms` to `yii2-media`

## 2.0.23 (Dec 11, 2023)

- Enhanced `EntrySiteRelationsBuilder` to use cached folder queries

## 2.0.22 (Nov 28, 2023)

- Changed default `Category` slug attribute target to prevent n+1 queries created by `RedirectBehavior` (#4)
- Changed `Asset::afterSave()` to always update the parent `updated_at` when an attribute was changed
- Removed "New Entry" button in `SectionEntryController::actionIndex` (#6)

## 2.0.21 (Nov 18, 2023)

- Fixed bug in `EntryParentIdFieldTrait` where model status was not loaded correctly

## 2.0.20 (Nov 15, 2023)

- Enhanced the `SiteController` to allow entries to be extended to redirect before rendering the view
- Fixed a bug with the `Sitemap` URL generation

## 2.0.19 (Nov 14, 2023)

- Updated widgets to use `\davidhirtz\yii2\media\helpers\Html` helper class

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
  to `davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\CategoryParentIdFieldTrait`

## v2.0.7 (Nov 8, 2023)

- Changed view path resolution of `Canvas`, `Gallery` and `Sections` widgets

## v2.0.6 (Nov 7, 2023)

- Added default view path to `Sections` widget
- Enhanced `Entry::getRoute()`, it now also returns the route when it has descendants
- Enhanced `EntryParentIdFieldTrait` to truncate long parent slugs

## 2.0.5 (Nov 7, 2023)

- Changed `davidhirtz\yii2\cms\modules\admin\widgets\forms\AssetActiveForm` to use `TypeFieldTrait` by default

## 2.0.4 (Nov 7, 2023)

- Added automatic default folder creation in `SetupController`
- Improved `VisibleAttributeTrait`

## 2.0.3 (Nov 6, 2023)

- Added `AdminLink` widget to display links to the backend
- Added `CategoryCollection::getByEntry()`
- Added `davidhirtz\yii2\cms\modules\admin\controllers\SetupController` to set up the entries
- Moved `davidhirtz\yii2\cms\models\traits\AssetParentTrait` to `davidhirtz\yii2\media\models\traits\AssetParentTrait`

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
  `davidhirtz\yii2\cms\models\actions`
- Replaced `ModelCloneEvent` with `davidhirtz\yii2\skeleton\models\events\DuplicateActiveRecordEvent`

## 2.0.1 (Nov 4, 2023)

- Added `davidhirtz\yii2\cms\models\builders\EntrySiteRelationsBuilder` which loads all relations needed in the
  frontend `SiteController`
- Added `entryIndexSlug` which automatically loads the entry index page in the frontend `SiteController::actionIndex()`
- Added `enableUrlRules` to automatically register URL rules in the CMS Module config, defaults to `true`
- Changed `Module::$defaultEntryOrderBy` to `position` ascending

## 2.0.0 (Nov 3, 2023)

- Added `davidhirtz\yii2\cms\Module::$enableSectionEntries` option to disable section entries
- Changed namespaces from `davidhirtz\yii2\cms\admin\widgets\grid` to `davidhirtz\yii2\cms\admin\widgets\grids`
  and `davidhirtz\yii2\cms\admin\widgets\nav` to `davidhirtz\yii2\cms\admin\widgets\navs`
- Changed namespaces for `LinkButtonTrait` and `UpdateFileButtonTrait`
  to `davidhirtz\yii2\cms\admin\widgets\panels\traits`
- Merged `davidhirtz\yii2\cms\yii2-cms-parent` into this package
- Moved source code to `src` folder
- Moved all models, data providers and widgets out of `base` folder, to override them use Yii's dependency injection
  container
- Removed `CategoryTrait` and `Category::getCategories()` in favor
  of `davidhirtz\yii2\cms\models\collections\CategoryCollection`
- Removed `ActiveForm::getActiveForm()`, to override the active forms, use Yii's dependency injection
  container

## 1.3.3 (Nov 3, 2023)

- Locked `davidhirtz/yii2-media` to version `1.3`, upgrade to version 2 to use the new media library