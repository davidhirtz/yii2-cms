<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\controllers\AssetController;
use davidhirtz\yii2\cms\modules\admin\controllers\EntryController;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\modules\admin\widgets\grids\columns\FileThumbnailColumn;
use davidhirtz\yii2\media\modules\admin\widgets\grids\traits\AssetGridViewTrait;
use davidhirtz\yii2\media\modules\admin\widgets\grids\traits\FileGridViewTrait;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\DraggableSortGridButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\buttons\ViewGridButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\Column;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\GridToolbarItem;
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
 * @property ActiveDataProvider|null $provider
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
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'asset-grid';
        $this->model ??= Asset::instance();

        $this->provider ??= new ActiveDataProvider([
            'query' => $this->getParentAssetQuery(),
            'sort' => false,
        ]);

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getThumbnailColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getDimensionsColumn(),
            $this->getButtonColumn(),
        ];

        $this->footer ??= [
            GridToolbarItem::make()
                ->class('form-row')
                ->content(Div::make()
                    ->class('form-content btn-group')
                    ->content(...$this->getFooterButtons())),
        ];

        /**
         * @see EntryController::actionOrder()
         * @see SectionController::actionOrder()
         */
        $this->orderRoute = ['cms/asset/order', $this->parent->getParamName() => $this->parent->id];

        parent::configure();
    }

    protected function getParentAssetQuery(): ActiveQuery
    {
        return $this->parent->getAssets()
            ->andWhere(['section_id' => $this->parent instanceof Section ? $this->parent->id : null])
            ->with('file');
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

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    protected function getButtonColumnContent(Asset $asset): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        if ($this->isSortable() && $this->provider->getCount() > 1) {
            if ($asset->isEntryAsset()
                ? $user->can(Entry::AUTH_ENTRY_ASSET_ORDER, ['entry' => $asset->entry])
                : $user->can(Section::AUTH_SECTION_ASSET_ORDER, ['section' => $asset->section])
            ) {
                $buttons[] = DraggableSortGridButton::make();
            }
        }

        if ($user->can(File::AUTH_FILE_UPDATE, ['file' => $asset->file])) {
            $buttons[] = $this->getFileUpdateButton($asset);
        }

        $permission = $asset->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_UPDATE
            : Section::AUTH_SECTION_ASSET_UPDATE;

        if ($user->can($permission, ['asset' => $asset])) {
            $buttons[] = ViewGridButton::make()
                ->model($asset);
        }

        $permission = $asset->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_DELETE
            : Section::AUTH_SECTION_ASSET_DELETE;

        if ($user->can($permission, ['asset' => $asset])) {
            $buttons[] = $this->getDeleteButton($asset);
        }

        return $buttons;
    }

    protected function getNameColumn(): ?Column
    {
        return DataColumn::make()
            ->property($this->model->getI18nAttributeName('name'))
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(Asset $asset): ?Stringable
    {
        $name = $asset->getI18nAttribute('name');
        $route = $this->getRoute($asset);

        $content = $name
            ? Div::make()
                ->class('strong')
                ->text($name)
            : Div::make()
                ->class('text-muted')
                ->text($asset->file->name);

        return $route
            ? A::make()
                ->content($content)
                ->href($route)
            : $content;
    }

    protected function getThumbnailColumn(): ?Column
    {
        return FileThumbnailColumn::make()
            ->url(fn (Asset $asset) => $this->getRoute($asset));
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

    /**
     * @param Asset $model
     */
    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        $permissionName = $model->isEntryAsset() ? Entry::AUTH_ENTRY_ASSET_UPDATE : Section::AUTH_SECTION_ASSET_UPDATE;

        return Yii::$app->getUser()->can($permissionName, ['asset' => $model])
            ? ['cms/asset/update', 'id' => $model->id, ...$params]
            : false;
    }
}
