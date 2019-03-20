<?php
namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;
use davidhirtz\yii2\cms\components\ModuleTrait;
use davidhirtz\yii2\cms\modules\admin\models\forms\PageForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\timeago\Timeago;
use rmrevin\yii\fontawesome\FAS;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;

/**
 * Class PageGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 *
 * @property ActiveDataProvider $dataProvider
 * @method PageForm getModel()
 */
class PageGridView extends GridView
{
    use ModuleTrait;

    /**
     * @var PageForm
     */
    public $page;

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
		if($this->page)
		{
			$this->orderRoute=['order', 'id'=>$this->page->id];
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
						'class'=>PageForm::getTypes() ? 'justify-content-between' : 'justify-content-end',
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
						'content'=>$this->renderCreatePageButton(),
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
	protected function renderCreatePageButton()
	{
		return Html::a(Html::iconText('plus', Yii::t('cms', 'New Page')), ['create', 'id'=>$this->page ? $this->page->id : null, 'type'=>Yii::$app->getRequest()->get('type')], ['class'=>'btn btn-primary']);
	}

    /**
     * @return array
     */
    public function renderStatusColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-center'],
            'content' => function (PageForm $page) {
                return FAS::icon($page->getStatusIcon(), [
                    'data-toggle' => 'tooltip',
                    'title' => $page->getStatusName()
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
			'visible'=>count(PageForm::getTypes())>1,
			'content'=>function(PageForm $page)
			{
				return Html::a($page->getTypeName(), ['update', 'id'=>$page->id]);
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
			'content'=>function(PageForm $page)
			{
				$html=Html::markKeywords(Html::encode($page->getI18nAttribute('name')), $this->search);
				$html=Html::tag('strong', Html::a($html, ['update', 'id'=>$page->id]));

				if($this->showUrl)
				{
					$url=Url::to($page->getRoute(), true);
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
			'content'=>function(PageForm $page)
			{
				return Html::a(Yii::$app->getFormatter()->asInteger($page->section_count), ['section/index', 'page'=>$page->id], ['class'=>'badge']);
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
			'content'=>function(PageForm $page)
			{
				return $this->dateFormat ? $page->publish_date->format($this->dateFormat) : Timeago::tag($page->publish_date);
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
			'content'=>function(PageForm $page)
			{
				$buttons=[];

				if($this->getIsSortedByPosition())
				{
					$buttons[]=Html::tag('span', FAS::icon('arrows-alt'), ['class'=>'btn btn-secondary sortable-handle']);
				}

				$buttons[]=Html::a(FAS::icon('wrench'), ['update', 'id'=>$page->id], ['class'=>'btn btn-secondary']);
				return Html::buttons($buttons);
			}
		];
	}
}