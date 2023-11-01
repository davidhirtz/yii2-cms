# Version 2.0.0

- Moved source code to `src` folder
- Added `davidhirtz\yii2\cms\Module::$enableSectionEntries` option to disable section entries
- Merged `davidhirtz\yii2\cms\yii2-cms-parent` into this package
- Moved all models, data providers and widgets out of `base` folder, to override them use Yii's dependency injection
  container
- Changed namespaces from `davidhirtz\yii2\cms\admin\widgets\grid` to `davidhirtz\yii2\cms\admin\widgets\grids`
  and `davidhirtz\yii2\cms\admin\widgets\nav` to `davidhirtz\yii2\cms\admin\widgets\navs`
- Changed namespaces for `LinkButtonTrait` and `UpdateFileButtonTrait`
  to `davidhirtz\yii2\cms\admin\widgets\panels\traits`