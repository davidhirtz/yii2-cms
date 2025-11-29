<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\AssetController;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\columns\AssetThumbnailColumn;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\modules\admin\widgets\grids\traits\AssetGridViewTrait;
use davidhirtz\yii2\media\modules\admin\widgets\grids\traits\FileGridViewTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\DraggableSortButton;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\ViewButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonsColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\grids\traits\StatusGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\grids\traits\TypeGridViewTrait;
use Override;
use Stringable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecordInterface;

/**
 * @extends GridView<Asset>
 * @property ActiveDataProvider|null $dataProvider
 */
class AssetGridView extends GridView
{
    use AssetGridViewTrait;
    use ModuleTrait;
    use FileGridViewTrait;
    use StatusGridViewTrait;
    use TypeGridViewTrait;

    public Entry|Section $parent;
    public string $layout = '{header}{items}{footer}';

    public function parent(Entry|Section $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    #[Override]
    public function init(): void
    {
        $this->setId($this->getId(false) ?? 'asset-grid');

        $this->dataProvider ??= new ActiveDataProvider([
            'query' => $this->getParentAssetQuery(),
            'sort' => false,
        ]);

        $this->columns ??= [
            $this->statusColumn(),
            $this->thumbnailColumn(),
            $this->typeColumn(),
            $this->nameColumn(),
            $this->dimensionsColumn(),
            $this->buttonsColumn(),
        ];

        /**
         * @see EntryController::actionOrder()
         * @see SectionController::actionOrder()
         */
        $this->orderRoute = ['cms/asset/order', $this->parent->getParamName() => $this->parent->id];

        parent::init();
    }

    protected function getParentAssetQuery(): ActiveQuery
    {
        return $this->parent->getAssets()
            ->andWhere(['section_id' => $this->parent instanceof Section ? $this->parent->id : null])
            ->with('file');
    }

    protected function initFooter(): void
    {
        $this->footer ??= [
            [
                ...$this->getFooterButtons(),
            ],
        ];
    }

    /**
     * @see AssetController::actionCreate()
     */
    protected function getFooterButtons(): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        $hasPermission = $this->parent instanceof Entry
            ? $user->can(Entry::AUTH_ENTRY_ASSET_CREATE, ['entry' => $this->parent])
            : $user->can(Section::AUTH_SECTION_ASSET_CREATE, ['section' => $this->parent]);

        if ($hasPermission) {
            if ($user->can(File::AUTH_FILE_CREATE)) {
                $buttons[] = $this->getFileUploadButton();
                $buttons[] = $this->getFileImportButton();
            }

            $buttons[] = $this->getAssetLinkButton();
        }

        return $buttons;
    }

    protected function getAssetLinkButton(): ?Stringable
    {
        return Button::make()
            ->primary()
            ->text(Yii::t('cms', 'Link assets'))
            ->icon('images')
            ->href($this->getParentRoute('cms/asset/index'));
    }

    protected function buttonsColumn(): array
    {
        return [
            'class' => ButtonsColumn::class,
            'content' => function (Asset $asset) {
                $user = Yii::$app->getUser();
                $buttons = [];

                if ($this->isSortable() && $this->dataProvider->getCount() > 1) {
                    if ($asset->isEntryAsset()
                        ? $user->can(Entry::AUTH_ENTRY_ASSET_ORDER, ['entry' => $asset->entry])
                        : $user->can(Section::AUTH_SECTION_ASSET_ORDER, ['section' => $asset->section])
                    ) {
                        $buttons[] = Yii::createObject(DraggableSortButton::class);
                    }
                }

                if ($user->can(File::AUTH_FILE_UPDATE, ['file' => $asset->file])) {
                    $buttons[] = $this->getFileUpdateButton($asset);
                }

                $permission = $asset->isEntryAsset()
                    ? Entry::AUTH_ENTRY_ASSET_UPDATE
                    : Section::AUTH_SECTION_ASSET_UPDATE;

                if ($user->can($permission, ['asset' => $asset])) {
                    $buttons[] = Yii::createObject(ViewButton::class, [$asset]);
                }

                $permission = $asset->isEntryAsset()
                    ? Entry::AUTH_ENTRY_ASSET_DELETE
                    : Section::AUTH_SECTION_ASSET_DELETE;

                if ($user->can($permission, ['asset' => $asset])) {
                    $buttons[] = $this->getDeleteButton($asset);
                }

                return $buttons;
            }
        ];
    }

    protected function nameColumn(): array
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

    protected function thumbnailColumn(): array
    {
        return [
            'class' => AssetThumbnailColumn::class,
            'route' => fn (Asset $asset) => $this->getRoute($asset),
        ];
    }

    protected function getFileUploadRoute(): array
    {
        return $this->getParentRoute('/admin/cms/asset/create', [
            'folder' => Yii::$app->getRequest()->get('folder'),
        ]);
    }

    protected function getParentRoute(string $action, $params = []): array
    {
        return [$action, $this->parent->getParamName() => $this->parent->id, ...$params];
    }

    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        $permissionName = $model->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_UPDATE
            : Section::AUTH_SECTION_ASSET_UPDATE;

        if (!Yii::$app->getUser()->can($permissionName, ['asset' => $model])) {
            return false;
        }

        return ['cms/asset/update', 'id' => $model->id, ...$params];
    }

    #[Override]
    public function getModel(): Asset
    {
        return Asset::instance();
    }
}
