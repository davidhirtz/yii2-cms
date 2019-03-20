<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;

use davidhirtz\yii2\cms\components\ModuleTrait;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use rmrevin\yii\fontawesome\FAS;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;

/**
 * Class SectionGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property ActiveDataProvider $dataProvider
 * @method SectionForm getModel()
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
//        'media_count',
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

            $this->setModel(new SectionForm);
        }

        $this->orderRoute = ['order', 'entry' => $this->entry->id];

        $this->initHeader();
        $this->initFooter();

        parent::init();
    }

    /**
     * Sets up grid header.
     */
    protected function initHeader()
    {
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
    public function renderStatusColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function (SectionForm $section) {
                return FAS::icon($section->getStatusIcon(), [
                    'data-toggle' => 'tooltip',
                    'title' => $section->getStatusName()
                ]);
            }
        ];
    }

    /**
     * @return array
     */
    public function renderTypeColumn()
    {
        return [
            'attribute' => 'type',
            'visible' => count(SectionForm::getTypes()) > 1,
            'content' => function (SectionForm $section) {
                return Html::a($section->getTypeName(), ['update', 'id' => $section->id]);
            }
        ];
    }

    /**
     * @return array
     */
    public function renderNameColumn()
    {
        return [
            'attribute' => $this->getModel()->getI18nAttributeName('name'),
            'headerOptions' => ['class' => 'hidden-xs'],
            'contentOptions' => ['class' => 'hidden-xs'],
            'content' => function (SectionForm $section) {
                $text = $section->getI18nAttribute('name');
                $cssClass = null;

//                if(!$text)
//                {
//                    if($file=$this->getFile($section->id))
//                    {
//                        if($preview=$file->getPictureByName(Picture::ADMIN_PICTURE_ID))
//                        {
//                            $text=Html::img(Yii::$app->getRequest()->cdnUrl.$preview->getUploadPath().$preview->filename, [
//                                'width'=>$preview->width,
//                                'height'=>$preview->height,
//                            ]);
//                        }
//                    }
//                }

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
//    public function renderMediaCountColumn()
//    {
//        return [
//            'attribute'=>'media_count',
//            'headerOptions'=>['class'=>'hidden-sm hidden-xs text-center'],
//            'contentOptions'=>['class'=>'hidden-sm hidden-xs text-center'],
//            'visible'=>static::getModule()->enableSections,
//            'content'=>function(SectionForm $section)
//            {
//                return Html::a(Yii::$app->getFormatter()->asInteger($section->media_count), ['/cms/admin/section/media', 'section'=>$section->id], ['class'=>'badge']);
//            }
//        ];
//    }

    /**
     * @return array
     */
    public function renderButtonsColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => function (SectionForm $section) {
                $buttons = [];

                if ($this->dataProvider->getCount() > 1) {
                    $buttons[] = Html::tag('span', FAS::icon('arrows-alt'), ['class' => 'btn btn-secondary sortable-handle']);
                }

                $buttons[] = Html::a(FAS::icon('wrench'), ['update', 'id' => $section->id], ['class' => 'btn btn-secondary']);
                return Html::buttons($buttons);
            }
        ];
    }
}