<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Traits\AssetGridViewTrait;
use Hirtz\Media\Traits\FilePropertyTrait;
use Hirtz\Skeleton\Widgets\Grids\Columns\BadgeColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\ViewGridButton;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Icon;
use Override;
use Stringable;
use Yii;
use yii\data\ActiveDataProvider;

/**
 * @extends GridView<Asset>
 * @property ActiveDataProvider|null $provider
 */
class FileAssetGridView extends GridView
{
    use AssetGridViewTrait;
    use FilePropertyTrait;

    protected string $language;

    public string $layout = '{items}{pager}';

    public function language(string $language): static
    {
        $this->language = $language;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        Yii::$app->getI18n()->callback($this->language, function (): void {
            $this->provider ??= new ActiveDataProvider([
                'query' => Asset::find()
                    ->where(['file_id' => $this->file->id])
                    ->with(['entry', 'section'])
                    ->orderBy(['updated_at' => SORT_DESC]),
            ]);

            $this->provider->getPagination()->pageParam = "cms-asset-page-$this->language";

            /** @var Asset $asset */
            foreach ($this->provider->getModels() as $asset) {
                $asset->populateRelation('file', $this->file);
            }
        });

        $this->columns ??= [
            $this->getStatusColumn(),
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getAssetCountColumn(),
            $this->getUpdatedAtColumn(),
            $this->getButtonColumn(),
        ];

        parent::configure();
    }

    protected function getStatusColumn(): Column
    {
        return LinkColumn::make()
            ->property('status')
            ->header(false)
            ->content($this->getStatusIcon(...))
            ->url(fn (Asset $asset) => $asset->getAdminRoute())
            ->centered();
    }

    protected function getStatusIcon(Asset $asset): Stringable
    {
        return Icon::make()
            ->name($asset->parent->getStatusIcon())
            ->tooltip($asset->parent->getStatusName());
    }

    protected function getTypeColumn(): Column
    {
        return LinkColumn::make()
            ->property('type')
            ->content($this->getTypeColumnContent(...))
            ->url(fn (Asset $asset) => $this->getParentRoute($asset));
    }

    protected function getTypeColumnContent(Asset $asset): string|Stringable
    {
        $type = $asset->entry->getTypeName();

        if (!$type && !$asset->section_id) {
            return Yii::t('cms', 'Entry');
        }

        if ($asset->section) {
            $type .= ($type ? " / " : '')
                . ($asset->section->getTypeName() ?: Yii::t('cms', 'Section'));
        }

        return $type;
    }

    protected function getNameColumn(): Column
    {
        return LinkColumn::make()
            ->property('name')
            ->content($this->getNameColumnContent(...))
            ->url(fn (Asset $asset) => $asset->getAdminRoute());
    }

    protected function getNameColumnContent(Asset $asset): string|Stringable
    {
        return $asset->entry->getI18nAttribute('name');
    }

    protected function getAssetCountColumn(): ?Column
    {
        return BadgeColumn::make()
            ->property('asset_count')
            ->content($this->getAssetCountColumnContent(...))
            ->url(fn (Asset $asset) => [...$this->getParentRoute($asset), '#' => 'assets']);
    }

    protected function getAssetCountColumnContent(Asset $asset): string
    {
        return (string)$asset->parent->asset_count;
    }

    protected function getUpdatedAtColumn(): Column
    {
        return RelativeTimeColumn::make()
            ->property('updated_at');
    }

    protected function getButtonColumn(): Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    protected function getButtonColumnContent(Asset $asset): array
    {
        $user = Yii::$app->getUser();
        $buttons = [];

        $permissionName = $asset->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_UPDATE
            : Section::AUTH_SECTION_ASSET_UPDATE;

        if ($user->can($permissionName, ['asset' => $asset])) {
            $buttons[] = ViewGridButton::make()
                ->url($this->getI18nRoute(['cms/asset/update', 'id' => $asset->id]));
        }

        $permissionName = $asset->isEntryAsset()
            ? Entry::AUTH_ENTRY_ASSET_DELETE
            : Section::AUTH_SECTION_ASSET_DELETE;

        if ($user->can($permissionName, ['asset' => $asset])) {
            $buttons[] = DeleteGridButton::make()
                ->model($asset)
                ->url($this->getI18nRoute(['cms/asset/delete', 'id' => $asset->id]))
                ->title(Yii::t('media', 'Are you sure you want to remove this asset?'));
        }

        return $buttons;
    }

    protected function getParentRoute(Asset $asset): array|false
    {
        $hasPermission = $asset->isEntryAsset()
            ? Yii::$app->getUser()->can(Entry::AUTH_ENTRY_UPDATE, ['entry' => $asset->parent])
            : Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['section' => $asset->parent]);

        return $hasPermission
            ? $this->getI18nRoute([...$asset->parent->getAdminRoute(), '#' => "asset-$asset->id"])
            : false;
    }

    protected function getI18nRoute(array $route): array
    {
        return [
            ...$route,
            'language' => $this->language !== Yii::$app->language ? $this->language : null,
        ];
    }
}
