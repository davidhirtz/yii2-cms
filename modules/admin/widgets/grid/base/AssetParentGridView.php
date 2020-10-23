<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\timeago\Timeago;
use yii\data\ArrayDataProvider;
use Yii;

/**
 * Class AssetParentGridView
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\grid\AssetParentGridView
 */
class AssetParentGridView extends GridView
{
    /**
     * @var File
     */
    public $file;

    /**
     * @var bool
     */
    public $showHeader = false;

    /**
     * @var string
     */
    public $layout = '{items}';

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->dataProvider) {
            $this->dataProvider = new ArrayDataProvider([
                'allModels' => Asset::find()
                    ->where(['file_id' => $this->file->id])
                    ->with(['entry', 'section'])
                    ->orderBy(['updated_at' => SORT_DESC])
                    ->all()
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

    /**
     * @return array
     */
    public function statusColumn()
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

    /**
     * @return array
     */
    public function typeColumn()
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

    /**
     * @return array
     */
    public function nameColumn()
    {
        return [
            'content' => function (Asset $asset) {
                return Html::tag('strong', Html::a($asset->entry->getI18nAttribute('name'), $this->getRoute($asset)));
            }
        ];
    }

    /**
     * @return array
     */
    public function assetCountColumn()
    {
        return [
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'content' => function (Asset $asset) {
                return Html::a(Yii::$app->getFormatter()->asInteger($asset->getParent()->asset_count), $this->getRoute($asset), ['class' => 'badge']);
            }
        ];
    }

    /**
     * @return array
     */
    public function updatedAtColumn()
    {
        return [
            'headerOptions' => ['class' => 'd-none d-lg-table-cell'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => function (Asset $asset) {
                return Timeago::tag($asset->updated_at);
            }
        ];
    }

    /**
     * @return array
     */
    public function buttonsColumn()
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
                        'data-confirm' => Yii::t('yii', 'Are you sure you want to remove this asset?'),
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
     * @param array $params
     * @return array|false
     */
    protected function getRoute($model, $params = [])
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

    /**
     * @return Asset
     */
    public function getModel()
    {
        return Asset::instance();
    }
}