<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ButtonDropdown;
use davidhirtz\yii2\timeago\Timeago;
use rmrevin\yii\fontawesome\FAS;
use Yii;
use yii\helpers\Url;

/**
 * Class EntryGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property EntryActiveDataProvider $dataProvider
 */
class EntryGridView extends GridView
{
    use ModuleTrait;

    /**
     * @var bool
     */
    public $showUrl = true;

    /**
     * @var string
     */
    public $dateFormat;

    /**
     * @var array
     */
    public $columns = [
        'status',
        'type',
        'name',
        'section_count',
        'asset_count',
        'updated_at',
        'buttons',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->dataProvider->entry) {
            $this->orderRoute = ['order', 'id' => $this->dataProvider->entry->id];
        }

        $this->initHeader();
        $this->initFooter();

        parent::init();
    }

    /**
     * Sets up grid header.
     */
    protected function initHeader()
    {
        if ($this->header === null) {
            $this->header = [
                [
                    [
                        'content' => $this->typeDropdown(),
                        'options' => ['class' => 'col-12 col-md-3'],
                    ],
                    [
                        'content' => $this->getSearchInput(),
                        'options' => ['class' => 'col-12 col-md-6'],
                    ],
                    'options' => [
                        'class' => EntryForm::getTypes() ? 'justify-content-between' : 'justify-content-end',
                    ],
                ],
            ];
        }
    }

    /**
     * Sets up grid footer.
     */
    protected function initFooter()
    {
        if ($this->footer === null) {
            $this->footer = [
                [
                    [
                        'content' => $this->renderCreateEntryButton(),
                        'visible' => Yii::$app->getUser()->can('author'),
                        'options' => ['class' => 'col'],
                    ],
                ],
            ];
        }
    }

    /**
     * @return string
     */
    protected function renderCreateEntryButton()
    {
        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Entry')), ['create', 'id' => $this->dataProvider->entry ? $this->dataProvider->entry->id : null, 'type' => $this->dataProvider->type], ['class' => 'btn btn-primary']);
    }

    /**
     * @return array
     */
    public function statusColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function (EntryForm $entry) {
                return FAS::icon($entry->getStatusIcon(), [
                    'data-toggle' => 'tooltip',
                    'title' => $entry->getStatusName()
                ]);
            }
        ];
    }

    /**
     * @return array
     */
    public function typeColumn()
    {
        if ($this->dataProvider->type || !EntryForm::getTypes()) {
            return false;
        }

        return [
            'attribute' => 'type',
            'visible' => count(EntryForm::getTypes()) > 1,
            'content' => function (EntryForm $entry) {
                return Html::a($entry->getTypeName(), ['update', 'id' => $entry->id]);
            }
        ];
    }

    /**
     * @return array
     */
    public function nameColumn()
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'content' => function (EntryForm $entry) {
                $html = Html::markKeywords(Html::encode($entry->getI18nAttribute('name')), $this->search);
                $html = Html::tag('strong', Html::a($html, ['update', 'id' => $entry->id]));

                if ($this->showUrl) {
                    $html .= $this->getUrl($entry);
                }


                return $html;
            }
        ];
    }

    /**
     * @return array
     */
    public function sectionCountColumn()
    {
        return [
            'attribute' => 'section_count',
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'visible' => static::getModule()->enableSections,
            'content' => function (EntryForm $entry) {
                return Html::a(Yii::$app->getFormatter()->asInteger($entry->section_count), ['section/index', 'entry' => $entry->id], ['class' => 'badge']);
            }
        ];
    }

    /**
     * @return array
     */
    public function assetCountColumn()
    {
        return [
            'attribute' => 'asset_count',
            'headerOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'visible' => static::getModule()->enableEntryAssets,
            'content' => function (EntryForm $entry) {
                return Html::a(Yii::$app->getFormatter()->asInteger($entry->asset_count), ['update', 'id' => $entry->id, '#' => 'assets'], ['class' => 'badge']);
            }
        ];
    }

    /**
     * @return array
     */
    public function publishDateColumn()
    {
        return [
            'attribute' => 'publish_date',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => function (EntryForm $entry) {
                return $this->dateFormat ? $entry->publish_date->format($this->dateFormat) : Timeago::tag($entry->publish_date);
            }
        ];
    }

    /**
     * @return array
     */
    public function updatedAtColumn()
    {
        return [
            'attribute' => 'updated_at',
            'headerOptions' => ['class' => 'd-none d-lg-table-cell'],
            'contentOptions' => ['class' => 'd-none d-lg-table-cell text-nowrap'],
            'content' => function (EntryForm $entry) {
                return $this->dateFormat ? $entry->updated_at->format($this->dateFormat) : Timeago::tag($entry->updated_at);
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
            'content' => function (EntryForm $entry) {
                $buttons = [];

                if ($this->getIsSortedByPosition()) {
                    $buttons[] = Html::tag('span', FAS::icon('arrows-alt'), ['class' => 'btn btn-secondary sortable-handle']);
                }

                $buttons[] = Html::a(FAS::icon('wrench'), ['update', 'id' => $entry->id], ['class' => 'btn btn-secondary d-none d-md-inline-block']);
                return Html::buttons($buttons);
            }
        ];
    }

    /**
     * @return string
     */
    public function typeDropdown()
    {
        if (Entry::getTypes()) {

            $config = [
                'label' => isset(Entry::getTypes()[$this->dataProvider->type]) ? Html::tag('strong', Entry::getTypes()[$this->dataProvider->type]['name']) : Yii::t('cms', 'Types'),
                'paramName' => 'type',
            ];

            foreach (Entry::getTypes() as $id => $type) {
                $config['items'][] = [
                    'label' => $type['name'],
                    'url' => Url::current(['type' => $id, 'page' => null]),
                ];
            }

            return ButtonDropdown::widget($config);
        }

        return '';
    }

    /**
     * @param EntryForm $entry
     * @return string
     */
    public function getUrl($entry)
    {
        $url = Url::to($entry->getRoute(), true);
        return Html::tag('div', Html::a($url, $url, ['target' => '_blank']), ['class' => 'd-none d-md-block small']);
    }

    /**
     * @return EntryForm
     */
    public function getModel()
    {
        return EntryForm::instance();
    }
}