<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\AssetController;
use Hirtz\Cms\Modules\Admin\Controllers\EntryController;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Columns\AssetThumbnailColumn;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Models\File;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Traits\AssetGridViewTrait;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Traits\FileGridViewTrait;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DraggableSortGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridToolbarItem;
use Hirtz\Skeleton\Widgets\Grids\Traits\StatusGridViewTrait;
use Hirtz\Skeleton\Widgets\Grids\Traits\TypeGridViewTrait;
use Override;
use Stringable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecordInterface;

/**
 * @template T of Asset
 * @extends GridView<T>
 *
 * @property Entry|Section $parent
 */
class AssetGridView extends GridView
{
    use AssetGridViewTrait;
    use ModuleTrait;
    use FileGridViewTrait;
    use StatusGridViewTrait;
    use TypeGridViewTrait;

    protected string $layout = '{header}{items}{footer}';

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'asset-grid-view';
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
        $buttons = [];

        if ($this->isSortable() && $this->provider->getCount() > 1) {
            if ($asset->isEntryAsset()
                ? $this->webuser->can(Entry::AUTH_ENTRY_ASSET_ORDER, ['entry' => $asset->entry])
                : $this->webuser->can(Section::AUTH_SECTION_ASSET_ORDER, ['section' => $asset->section])
            ) {
                $buttons[] = DraggableSortGridButton::make();
            }
        }

        if ($this->webuser->can(File::AUTH_FILE_UPDATE, ['file' => $asset->file])) {
            $buttons[] = $this->getFileUpdateButton($asset);
        }

        $permission = $asset->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_UPDATE
            : Section::AUTH_SECTION_ASSET_UPDATE;

        if ($this->webuser->can($permission, ['asset' => $asset])) {
            $buttons[] = ViewGridButton::make()
                ->model($asset);
        }

        $permission = $asset->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_DELETE
            : Section::AUTH_SECTION_ASSET_DELETE;

        if ($this->webuser->can($permission, ['asset' => $asset])) {
            $buttons[] = $this->getDeleteButton($asset);
        }

        return $buttons;
    }

    protected function getNameColumn(): ?Column
    {
        return DataColumn::make()
            ->property(Asset::instance()->getI18nAttributeName('name'))
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
        return AssetThumbnailColumn::make()
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
            ? ['/admin/cms/asset/update', 'id' => $model->id, ...$params]
            : false;
    }
}
