<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\AssetThumbnailColumn;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\media\modules\admin\widgets\grids\traits\AssetColumnsTrait;
use davidhirtz\yii2\media\modules\admin\widgets\grids\traits\UploadTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecordInterface;

/**
 * @extends GridView<Asset>
 * @property ActiveDataProvider $dataProvider
 */
class AssetGridView extends GridView
{
    use AssetColumnsTrait;
    use ModuleTrait;
    use StatusGridViewTrait;
    use TypeGridViewTrait;
    use UploadTrait;

    public $layout = '{header}{items}{footer}';

    public function init(): void
    {
        $this->dataProvider ??= $this->getAssetActiveDataProvider();

        if (!$this->columns) {
            $this->columns = [
                $this->statusColumn(),
                $this->thumbnailColumn(),
                $this->typeColumn(),
                $this->nameColumn(),
                $this->dimensionsColumn(),
                $this->buttonsColumn(),
            ];
        }

        if (Yii::$app->getUser()->can('fileCreate')) {
            $this->registerAssetClientScripts();
        }

        /**
         * @see EntryController::actionOrder()
         * @see SectionController::actionOrder()
         */
        $this->orderRoute = $this->getParentRoute('cms/asset/order');


        parent::init();
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                [
                    'content' => Html::buttons($this->getFooterButtons()),
                    'options' => ['class' => 'offset-md-3 col-md-9'],
                ],
            ],
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => fn ($asset): string => Html::buttons($this->getRowButtons($asset))
        ];
    }

    public function nameColumn(): array
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (Asset $asset) {
                $name = $asset->getI18nAttribute('name');
                $route = $this->getRoute($asset);

                $tag = $name
                    ? Html::tag('strong', Html::encode($asset->getI18nAttribute('name')))
                    : Html::tag('span', Html::encode($asset->file->name), ['class' => 'text-muted']);

                return $route ? Html::a($tag, $route) : $tag;
            }
        ];
    }

    public function thumbnailColumn(): array
    {
        return [
            'class' => AssetThumbnailColumn::class,
            'route' => fn (Asset $asset) => $this->getRoute($asset),
        ];
    }

    protected function getParentAssetQuery(): ActiveQuery
    {
        return $this->parent->getAssets()
            ->andWhere(['section_id' => $this->parent instanceof Section ? $this->parent->id : null])
            ->with('file')
            ->limit($this->maxAssetCount);
    }

    public function renderItems(): string
    {
        return Html::tag('div', parent::renderItems(), ['id' => 'files']);
    }

    protected function getFooterButtons(): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        $hasPermission = $this->parent instanceof Entry
            ? $user->can('entryAssetCreate', ['entry' => $this->parent])
            : $user->can('sectionAssetCreate', ['section' => $this->parent]);

        if ($hasPermission) {
            if ($user->can('fileCreate')) {
                $buttons[] = $this->getUploadFileButton();
                $buttons[] = $this->getImportFileButton();
            }

            $buttons[] = $this->getAssetsButton();
        }

        return $buttons;
    }

    protected function getAssetsButton(): string
    {
        $text = Html::iconText('images', Yii::t('cms', 'Link assets'));

        return Html::a($text, $this->getParentRoute('cms/asset/index'), [
            'class' => 'btn btn-primary',
        ]);
    }

    protected function getRowButtons(Asset $asset): array
    {
        $user = Yii::$app->getUser();
        $isEntry = $asset->isEntryAsset();
        $buttons = [];

        if ($this->isSortedByPosition() && $this->dataProvider->getCount() > 1) {
            if ($isEntry
                ? $user->can('entryAssetOrder', ['entry' => $asset->entry])
                : $user->can('sectionAssetOrder', ['section' => $asset->section])) {
                $buttons[] = $this->getSortableButton();
            }
        }

        if ($user->can('fileUpdate', ['file' => $asset->file])) {
            $buttons[] = $this->getFileUpdateButton($asset);
        }

        if ($user->can($isEntry ? 'entryAssetUpdate' : 'sectionAssetUpdate', ['asset' => $asset])) {
            $buttons[] = $this->getUpdateButton($asset);
        }

        if ($user->can($isEntry ? 'entryAssetDelete' : 'sectionAssetDelete', ['asset' => $asset])) {
            $buttons[] = $this->getDeleteButton($asset);
        }

        return $buttons;
    }

    protected function getFileUploadRoute(): array
    {
        return $this->getParentRoute('/admin/cms/asset/create', [
            'folder' => Yii::$app->getRequest()->get('folder'),
        ]);
    }

    protected function getDeleteRoute(ActiveRecordInterface $model, array $params = []): array
    {
        return ['/admin/asset/delete', 'id' => $model->id, ...$params];
    }

    protected function getParentRoute(string $action, $params = []): array
    {
        return array_merge([$action, ($this->parent instanceof Entry ? 'entry' : 'section') => $this->parent->id], $params);
    }

    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        $permissionName = $model->isEntryAsset() ? 'entryAssetUpdate' : 'sectionAssetUpdate';

        if (!Yii::$app->getUser()->can($permissionName, ['asset' => $model])) {
            return false;
        }

        return ['cms/asset/update', 'id' => $model->id, ...$params];
    }

    public function getModel(): Asset
    {
        return Asset::instance();
    }
}
