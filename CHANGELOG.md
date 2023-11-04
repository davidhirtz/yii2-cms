## 2.0.2 (Nov 5, 2023)

- Added `Canvas` widget to display the assets in the frontend
- Renamed `AssetViews` to `Gallery`
- Renamed `SectionViews` to `Sections` 
- Renamed `MetaTags::register()` to `MetaTags::widget()` to match the other widgets

## 2.0.1 (Nov 4, 2023)

- Added `davidhirtz\yii2\cms\models\builders\EntrySiteRelationsBuilder` which loads all relations needed in the
  frontend `SiteController`
- Added `entryIndexSlug` which automatically loads the entry index page in the frontend `SiteController::actionIndex()`
- Added `enableUrlRules` to automatically register URL rules in the CMS Module config, defaults to `true`
- Changed `Module::$defaultEntryOrderBy` to `position` ascending

## 2.0.0 (Nov 3, 2023)

- Moved source code to `src` folder
- Added `davidhirtz\yii2\cms\Module::$enableSectionEntries` option to disable section entries
- Merged `davidhirtz\yii2\cms\yii2-cms-parent` into this package
- Moved all models, data providers and widgets out of `base` folder, to override them use Yii's dependency injection
  container
- Changed namespaces from `davidhirtz\yii2\cms\admin\widgets\grid` to `davidhirtz\yii2\cms\admin\widgets\grids`
  and `davidhirtz\yii2\cms\admin\widgets\nav` to `davidhirtz\yii2\cms\admin\widgets\navs`
- Changed namespaces for `LinkButtonTrait` and `UpdateFileButtonTrait`
  to `davidhirtz\yii2\cms\admin\widgets\panels\traits`
- Removed `CategoryTrait` and `Category::getCategories()` in favor
  of `davidhirtz\yii2\cms\models\collections\CategoryCollection`
- Removed `ActiveForm::getActiveForm()`, to override the active forms, use Yii's dependency injection
  container

## 1.3.3 (Sat 4, 2023)

- Locked `davidhirtz/yii2-media` to version `1.3`, upgrade to version 2 to use the new media library