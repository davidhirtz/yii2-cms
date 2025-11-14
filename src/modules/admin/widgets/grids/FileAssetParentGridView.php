<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Icon;
use davidhirtz\yii2\skeleton\html\Table;
use davidhirtz\yii2\skeleton\widgets\grids\buttons\DeleteButton;
use davidhirtz\yii2\skeleton\widgets\grids\columns\ButtonsColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\CounterColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\timeago\TimeagoColumn;
use Override;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecordInterface;

/**
 * @extends GridView<Asset>
 * @property ActiveDataProvider|null $dataProvider
 */
class FileAssetParentGridView extends GridView
{
    public File $file;
    public string $language;

    public string $layout = '{items}{pager}';

    #[Override]
    public function init(): void
    {
        Yii::$app->getI18n()->callback($this->language, function () {
            $this->dataProvider ??= new ActiveDataProvider([
                'query' => Asset::find()
                    ->where(['file_id' => $this->file->id])
                    ->with(['entry', 'section'])
                    ->orderBy(['updated_at' => SORT_DESC]),
            ]);

            $this->dataProvider->getPagination()->pageParam = "cms-asset-page-$this->language";

            /** @var Asset $asset */
            foreach ($this->dataProvider->getModels() as $asset) {
                $asset->populateRelation('file', $this->file);
            }
        });

        $this->columns ??= [
            $this->statusColumn(),
            $this->typeColumn(),
            $this->nameColumn(),
            $this->assetCountColumn(),
            $this->updatedAtColumn(),
            $this->buttonsColumn(),
        ];

        parent::init();
    }

    #[Override]
    protected function renderTable(): Table
    {
        return Table::make()
            ->attributes($this->tableAttributes)
            ->body($this->renderTableBody());
    }

    protected function statusColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => fn (Asset $asset) => Icon::make()
                ->name($asset->getParent()->getStatusIcon())
                ->tooltip($asset->getParent()->getStatusName()),
        ];
    }

    protected function typeColumn(): array
    {
        return [
            'content' => function (Asset $asset) {
                $typeName = [
                    $asset->entry->getTypeName() ?: (!$asset->section_id ? Yii::t('cms', 'Entry') : null),
                ];

                if ($asset->section_id) {
                    $typeName[] = $asset->section->getTypeName() ?: Yii::t('cms', 'Section');
                }

                return A::make()
                    ->text(implode(' / ', array_filter($typeName)))
                    ->href($this->getRoute($asset));
            }
        ];
    }

    protected function nameColumn(): array
    {
        return [
            'content' => fn (Asset $asset) => A::make()
                ->text($asset->entry->getI18nAttribute('name'))
                ->href($this->getRoute($asset))
        ];
    }

    protected function assetCountColumn(): array
    {
        return [
            'attribute' => 'parent.asset_count',
            'class' => CounterColumn::class,
            'route' => fn (Asset $asset) => $this->getRoute($asset),
        ];
    }

    protected function updatedAtColumn(): array
    {
        return [
            'attribute' => 'updated_at',
            'class' => TimeagoColumn::class,
        ];
    }

    protected function buttonsColumn(): array
    {
        return [
            'class' => ButtonsColumn::class,
            'content' => function (Asset $asset): array {
                $user = Yii::$app->getUser();
                $buttons = [];

                $permissionName = $asset->isEntryAsset()
                    ? Entry::AUTH_ENTRY_ASSET_UPDATE
                    : Section::AUTH_SECTION_ASSET_UPDATE;

                if ($user->can($permissionName, ['asset' => $asset])) {
                    $buttons[] = Button::make()
                        ->primary()
                        ->icon('wrench')
                        ->tooltip(Yii::t('cms', 'Edit Asset'))
                        ->href($this->getI18nRoute(['cms/asset/update', 'id' => $asset->id]));
                }

                $permissionName = $asset->isEntryAsset()
                    ? Entry::AUTH_ENTRY_ASSET_DELETE
                    : Section::AUTH_SECTION_ASSET_DELETE;

                if ($user->can($permissionName, ['asset' => $asset])) {
                    $buttons[] = Yii::createObject(DeleteButton::class, [
                        $asset,
                        $this->getI18nRoute(['cms/asset/delete', 'id' => $asset->id]),
                        Yii::t('media', 'Are you sure you want to remove this asset?'),
                    ]);
                }

                return $buttons;
            }
        ];
    }

    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        $user = Yii::$app->getUser();
        $parent = $model->getParent();

        if ($model->isEntryAsset()) {
            if ($user->can(Entry::AUTH_ENTRY_UPDATE, ['entry' => $parent])) {
                return $this->getI18nRoute([
                    '/admin/entry/update',
                    'id' => $parent->id,
                    '#' => 'asset-' . $model->id,
                    ...$params
                ]);
            }
        }

        if ($model->isSectionAsset()) {
            if ($user->can(Section::AUTH_SECTION_UPDATE, ['section' => $parent])) {
                return $this->getI18nRoute([
                    '/admin/section/update',
                    'id' => $parent->id,
                    '#' => 'asset-' . $model->id,
                    ...$params
                ]);
            }
        }

        return false;
    }

    protected function getI18nRoute(array $route): array
    {
        return [
            ...$route,
            'language' => $this->language !== Yii::$app->language ? $this->language : null,
        ];
    }

    #[Override]
    public function getModel(): Asset
    {
        return Asset::instance();
    }
}
