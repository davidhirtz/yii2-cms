<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grids;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\timeago\TimeagoColumn;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecordInterface;

/**
 * @extends GridView<Asset>
 */
class AssetParentGridView extends GridView
{
    /**
     * @var File|null the file to display assets from
     */
    public ?File $file = null;

    public $showHeader = false;
    public $layout = '{items}{pager}';

    public function init(): void
    {
        $this->dataProvider ??= new ActiveDataProvider([
            'query' => Asset::find()
                ->where(['file_id' => $this->file->id])
                ->with(['entry', 'section'])
                ->orderBy(['updated_at' => SORT_DESC]),
        ]);

        if (!$this->columns) {
            $this->columns = [
                $this->statusColumn(),
                $this->typeColumn(),
                $this->nameColumn(),
                $this->assetCountColumn(),
                $this->updatedAtColumn(),
                $this->buttonsColumn(),
            ];
        }

        /** @var Asset $asset */
        foreach ($this->dataProvider->getModels() as $asset) {
            $asset->populateRelation('file', $this->file);
        }

        parent::init();
    }

    public function statusColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => fn (Asset $asset) => Icon::tag($asset->getParent()->getStatusIcon(), [
                'data-toggle' => 'tooltip',
                'title' => $asset->getParent()->getStatusName(),
            ])
        ];
    }

    public function typeColumn(): array
    {
        return [
            'content' => function (Asset $asset) {
                $typeName = [$asset->entry->getTypeName() ?: (!$asset->section_id ? Yii::t('cms', 'Entry') : null)];

                if ($asset->section_id) {
                    $typeName[] = $asset->section->getTypeName() ?: Yii::t('cms', 'Section');
                }

                return Html::a(implode(' / ', array_filter($typeName)), $this->getRoute($asset));
            }
        ];
    }

    public function nameColumn(): array
    {
        return [
            'content' => fn (Asset $asset) => Html::tag('strong', Html::a($asset->entry->getI18nAttribute('name'), $this->getRoute($asset)))
        ];
    }

    public function assetCountColumn(): array
    {
        return [
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'content' => fn (Asset $asset) => Html::a(Yii::$app->getFormatter()->asInteger($asset->getParent()->asset_count), $this->getRoute($asset), ['class' => 'badge'])
        ];
    }

    public function updatedAtColumn(): array
    {
        return [
            'attribute' => 'updated_at',
            'class' => TimeagoColumn::class,
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => function (Asset $asset): string {
                $user = Yii::$app->getUser();
                $buttons = [];

                if ($user->can($asset->isEntryAsset() ? Entry::AUTH_ENTRY_ASSET_UPDATE : Section::AUTH_SECTION_ASSET_UPDATE, ['asset' => $asset])) {
                    $buttons[] = Html::a(Icon::tag('wrench'), ['cms/asset/update', 'id' => $asset->id], [
                        'class' => 'btn btn-primary',
                        'data-toggle' => 'tooltip',
                        'title' => Yii::t('cms', 'Edit Asset'),
                    ]);
                }

                if ($user->can($asset->isEntryAsset() ? Entry::AUTH_ENTRY_ASSET_DELETE : Section::AUTH_SECTION_ASSET_DELETE, ['asset' => $asset])) {
                    $buttons[] = Html::a(Icon::tag('trash'), ['cms/asset/delete', 'id' => $asset->id], [
                        'class' => 'btn btn-danger btn-delete-asset d-none d-md-inline-block',
                        'data-confirm' => Yii::t('cms', 'Are you sure you want to remove this asset?'),
                        'data-ajax' => 'remove',
                        'data-target' => '#' . $this->getRowId($asset),
                    ]);
                }

                return Html::buttons($buttons);
            }
        ];
    }

    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        $user = Yii::$app->getUser();
        $parent = $model->getParent();

        if ($model->isEntryAsset()) {
            if ($user->can(Entry::AUTH_ENTRY_UPDATE, ['entry' => $parent])) {
                return ['/admin/entry/update', 'id' => $parent->id, '#' => 'asset-' . $model->id, ...$params];
            }
        }

        if ($model->isSectionAsset()) {
            if ($user->can(Section::AUTH_SECTION_UPDATE, ['section' => $parent])) {
                return ['/admin/section/update', 'id' => $parent->id, '#' => 'asset-' . $model->id, ...$params];
            }
        }

        return false;
    }

    public function getModel(): Asset
    {
        return Asset::instance();
    }
}
