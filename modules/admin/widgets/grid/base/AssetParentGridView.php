<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\timeago\Timeago;
use yii\data\ActiveDataProvider;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * The AssetParentGridView widget is used to display {@see Asset} models in a grid related to {@link File}.
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\grid\AssetParentGridView
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
        if (!$this->dataProvider) {
            $this->dataProvider = new ActiveDataProvider([
                'query' => Asset::find()
                    ->where(['file_id' => $this->file->id])
                    ->with(['entry', 'section'])
                    ->orderBy(['updated_at' => SORT_DESC]),
            ]);
        }

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
            'content' => function (Asset $asset) {
                return Icon::tag($asset->getParent()->getStatusIcon(), [
                    'data-toggle' => 'tooltip',
                    'title' => $asset->getParent()->getStatusName(),
                ]);
            }
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
            'content' => function (Asset $asset) {
                return Html::tag('strong', Html::a($asset->entry->getI18nAttribute('name'), $this->getRoute($asset)));
            }
        ];
    }

    public function assetCountColumn(): array
    {
        return [
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'content' => function (Asset $asset) {
                return Html::a(Yii::$app->getFormatter()->asInteger($asset->getParent()->asset_count), $this->getRoute($asset), ['class' => 'badge']);
            }
        ];
    }

    public function updatedAtColumn(): array
    {
        return [
            'headerOptions' => ['class' => 'd-none d-lg-table-cell'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => function (Asset $asset) {
                return Timeago::tag($asset->updated_at);
            }
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => function (Asset $asset) {
                $user = Yii::$app->getUser();
                $buttons = [];

                if ($user->can($asset->isEntryAsset() ? 'entryAssetUpdate' : 'sectionAssetUpdate', ['asset' => $asset])) {
                    $buttons[] = Html::a(Icon::tag('wrench'), ['cms/asset/update', 'id' => $asset->id], [
                        'class' => 'btn btn-primary',
                        'data-toggle' => 'tooltip',
                        'title' => Yii::t('cms', 'Edit Asset'),
                    ]);
                }

                if ($user->can($asset->isEntryAsset() ? 'entryAssetDelete' : 'sectionAssetDelete', ['asset' => $asset])) {
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

    /**
     * @param Asset $model
     */
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        $user = Yii::$app->getUser();
        $parent = $model->getParent();

        if ($model->isEntryAsset()) {
            if ($user->can('entryUpdate', ['entry' => $parent])) {
                return array_merge(['/admin/entry/update', 'id' => $parent->id, '#' => 'asset-' . $model->id], $params);
            }
        }

        if ($model->isSectionAsset()) {
            if ($user->can('sectionUpdate', ['section' => $parent])) {
                return array_merge(['/admin/section/update', 'id' => $parent->id, '#' => 'asset-' . $model->id], $params);
            }
        }

        return false;
    }

    public function getModel(): Asset
    {
        return Asset::instance();
    }
}