<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\StringHelper;

/**
 * Class SectionGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property ActiveDataProvider $dataProvider
 * @method Section getModel()
 */
class SectionGridView extends GridView
{
    use ModuleTrait;

    /**
     * @var Entry
     */
    public $entry;

    /**
     * @var array
     */
    public $columns = [
        'status',
        'type',
        'name',
        'asset_count',
        'buttons',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->dataProvider) {
            $this->dataProvider = new ArrayDataProvider([
                'allModels' => $this->entry->sections,
                'pagination' => false,
                'sort' => false,
            ]);

            $this->setModel(new Section);
        }

        $this->orderRoute = ['order', 'entry' => $this->entry->id];

        $this->initFooter();

        parent::init();
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
                        'content' => $this->renderCreateSectionButton(),
                        'options' => ['class' => 'col'],
                    ],
                ],
            ];
        }
    }

    /**
     * @return string
     */
    protected function renderCreateSectionButton()
    {
        return Html::a(Html::iconText('plus', Yii::t('cms', 'New Section')), ['create', 'entry' => $this->entry->id], ['class' => 'btn btn-primary']);
    }

    /**
     * @return array
     */
    public function statusColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function (Section $section) {
                return Icon::tag($section->getStatusIcon(), [
                    'data-toggle' => 'tooltip',
                    'title' => $section->getStatusName()
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
            'attribute' => 'type',
            'visible' => count(Section::getTypes()) > 1,
            'content' => function (Section $section) {
                return Html::a($section->getTypeName(), ['update', 'id' => $section->id]);
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
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (Section $section) {
                $text = $section->getNameColumnContent() ?: Html::tag('strong', $section->getI18nAttribute('name'));
                $cssClass = null;

                if(!$text)
                {
                    if($section->assets)
                    {
                        $asset = current($section->assets);

                        if($asset->file->hasPreview())
                        {
                            $text=Html::tag('div', Html::tag('div', '', [
                                'style' => 'background-image:url(' . ($asset->file->getTransformationUrl('admin') ?: $asset->file->getUrl()) . ');',
                                'class' => 'thumb',
                            ]), ['style' => 'width:120px']);
                        }
                    }
                }

                if(!$text) {
                    $text = $section->getI18nAttribute('content');
                    $text = StringHelper::truncate($section->contentType == 'html' ? strip_tags($text) : $text, 100);
                }

                if (!$text) {
                    $text = Yii::t('cms', '[ No title ]');
                    $cssClass = 'text-muted';
                }

                return Html::a($text, ['update', 'id' => $section->id], ['class' => $cssClass]);
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
            'visible' => static::getModule()->enableSectionAssets,
            'content' => function (Section $section) {
                return Html::a(Yii::$app->getFormatter()->asInteger($section->asset_count), ['update', 'id' => $section->id, '#' => 'assets'], ['class' => 'badge']);
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
            'content' => function (Section $section) {
                $buttons = [];

                if ($this->dataProvider->getCount() > 1) {
                    $buttons[] = Html::tag('span', Icon::tag('arrows-alt'), ['class' => 'btn btn-secondary sortable-handle']);
                }

                $buttons[] = Html::a(Icon::tag('wrench'), ['update', 'id' => $section->id], ['class' => 'btn btn-secondary']);
                return Html::buttons($buttons);
            }
        ];
    }
}