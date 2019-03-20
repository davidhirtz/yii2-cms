<?php
namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\timeago\Timeago;
use rmrevin\yii\fontawesome\FAS;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;

/**
 * Class EntryGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property ActiveDataProvider $dataProvider
 * @method EntryForm getModel()
 */
class EntryGridView extends GridView
{
    use ModuleTrait;

    /**
     * @var EntryForm
     */
    public $entry;

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
        'publish_date',
        'buttons',
    ];
    
	/**
     * @inheritdoc
	 */
	public function init()
	{
		if($this->entry)
		{
			$this->orderRoute=['order', 'id'=>$this->entry->id];
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
		if($this->header===null)
		{
			$this->header=[
				[
					[
						'content'=>$this->renderSearchInput(),
						'options'=>['class'=>'col-12 col-md-6'],
					],
					'options'=>[
						'class'=>EntryForm::getTypes() ? 'justify-content-between' : 'justify-content-end',
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
		if($this->footer===null)
		{
			$this->footer=[
				[
					[
						'content'=>$this->renderCreateEntryButton(),
						'visible'=>Yii::$app->getUser()->can('author'),
						'options'=>['class'=>'col'],
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
		return Html::a(Html::iconText('plus', Yii::t('cms', 'New Entry')), ['create', 'id'=>$this->entry ? $this->entry->id : null, 'type'=>Yii::$app->getRequest()->get('type')], ['class'=>'btn btn-primary']);
	}

    /**
     * @return array
     */
    public function renderStatusColumn()
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
	public function renderTypeColumn()
	{
		return [
			'attribute'=>'type',
			'visible'=>count(EntryForm::getTypes())>1,
			'content'=>function(EntryForm $entry)
			{
				return Html::a($entry->getTypeName(), ['update', 'id'=>$entry->id]);
			}
		];
	}

	/**
	 * @return array
	 */
	public function renderNameColumn()
	{
		return [
			'attribute'=>$this->getModel()->getI18nAttributeName('name'),
			'content'=>function(EntryForm $entry)
			{
				$html=Html::markKeywords(Html::encode($entry->getI18nAttribute('name')), $this->search);
				$html=Html::tag('strong', Html::a($html, ['update', 'id'=>$entry->id]));

				if($this->showUrl)
				{
					$url=Url::to($entry->getRoute(), true);
					$html.=Html::tag('div', Html::a($url, $url, ['target'=>'_blank']), ['class'=>'small hidden-xs']);
				}


				return $html;
			}
		];
	}

	/**
	 * @return array
	 */
	public function renderSectionCountColumn()
	{
		return [
			'attribute'=>'section_count',
			'headerOptions'=>['class'=>'hidden-sm hidden-xs text-center'],
			'contentOptions'=>['class'=>'hidden-sm hidden-xs text-center'],
			'visible'=>static::getModule()->enableSections,
			'content'=>function(EntryForm $entry)
			{
				return Html::a(Yii::$app->getFormatter()->asInteger($entry->section_count), ['section/index', 'entry'=>$entry->id], ['class'=>'badge']);
			}
		];
	}

	/**
	 * @return array
	 */
	public function renderPublishDateColumn()
	{
		return [
			'attribute'=>'publish_date',
			'headerOptions'=>['class'=>'hidden-sm hidden-xs'],
			'contentOptions'=>['class'=>'text-nowrap hidden-sm hidden-xs'],
			'content'=>function(EntryForm $entry)
			{
				return $this->dateFormat ? $entry->publish_date->format($this->dateFormat) : Timeago::tag($entry->publish_date);
			}
		];
	}

	/**
	 * @return array
	 */
	public function renderButtonsColumn()
	{
		return [
			'contentOptions'=>['class'=>'text-right text-nowrap'],
			'content'=>function(EntryForm $entry)
			{
				$buttons=[];

				if($this->getIsSortedByPosition())
				{
					$buttons[]=Html::tag('span', FAS::icon('arrows-alt'), ['class'=>'btn btn-secondary sortable-handle']);
				}

				$buttons[]=Html::a(FAS::icon('wrench'), ['update', 'id'=>$entry->id], ['class'=>'btn btn-secondary']);
				return Html::buttons($buttons);
			}
		];
	}
}