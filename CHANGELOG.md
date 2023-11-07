## v2.0.2 (Nov 6, 2023)

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

## 1.3.3 (Sat 4, 2023)

- Locked `davidhirtz/yii2-media` to version `1.3`, upgrade to version 2 to use the new media library