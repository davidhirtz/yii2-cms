<?php
namespace davidhirtz\yii2\cms\modules\admin\widgets\grid\base;
use app\components\helpers\ArrayHelper;
use app\components\helpers\Html;
use app\modules\admin\components\widgets\grid\GridView;
use app\modules\content\models\File;
use app\modules\content\models\Page;
use app\modules\content\models\Picture;
use app\modules\content\models\queries\PictureQuery;
use app\modules\content\models\Section;
use davidhirtz\yii2\timeago\Timeago;
use rmrevin\yii\fontawesome\FA;
use Yii;
use yii\data\ArrayDataProvider;

/**
 * Class SectionGridView.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\grid\base
 * @see SectionGridView
 *
 * @method Section getModel()
 * @method Section[] getModels()
 */
class SectionGridView extends GridView
{
	/**
	 * @var Page
	 */
	public $page;

	/**
	 * @see getFiles()
	 * @var File[]
	 */
	private $_files;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if(!$this->dataProvider)
		{
			$this->dataProvider=new ArrayDataProvider([
				'allModels'=>$this->page->sections,
				'pagination'=>false,
				'sort'=>false,
			]);

			$this->setModel(new Section);
		}

		$this->orderRoute=['order', 'id'=>$this->page->id];

		$this->initHeader();
		$this->initFooter();

		parent::init();
	}

	/**
	 * Header.
	 */
	protected function initHeader()
	{
	}

	/**
	 * Footer.
	 */
	protected function initFooter()
	{
		if($this->footer===null)
		{
			$this->footer=[
				[
					[
						'content'=>Html::a(Html::iconText('plus', Yii::t('cms', 'New Section')), ['create', 'id'=>$this->page->id], ['class'=>'btn btn-primary']),
						'options'=>[
							'class'=>'col',
						],
					],
				],
			];
		}
	}

	/***********************************************************************
	 * Columns.
	 ***********************************************************************/

	/**
	 * Setup columns.
	 */
	protected function guessColumns()
	{
		$this->columns=[
			$this->renderStatusColumn(),
			$this->renderTypeColumn(),
			$this->renderNameColumn(),
			$this->renderFileCountColumn(),
			$this->renderUpdatedAtColumn(),
			$this->renderButtonsColumn(),
		];
	}

	/**
	 * @return array
	 */
	public function renderStatusColumn()
	{
		return [
			'contentOptions'=>['class'=>'text-center'],
			'content'=>function(Section $section)
			{
				return FA::icon($section->getIsPublic() ? 'globe' : 'lock', [
					'data-toggle'=>'tooltip',
					'title'=>$section->getStatusName(),
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
			'visible'=>count(Section::getTypes())>1,
			'content'=>function(Section $section)
			{
				return Html::a($section->getTypeName(), ['update', 'id'=>$section->id]);
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
			'headerOptions'=>['class'=>'hidden-xs'],
			'contentOptions'=>['class'=>'hidden-xs'],
			'content'=>function(Section $section)
			{
				$text=$section->getI18nAttribute('name');
				$cssClass=null;

				if(!$text)
				{
					if($file=$this->getFile($section->id))
					{
						if($preview=$file->getPictureByName(Picture::ADMIN_PICTURE_ID))
						{
							$text=Html::img(Yii::$app->getRequest()->cdnUrl.$preview->getUploadPath().$preview->filename, [
								'width'=>$preview->width,
								'height'=>$preview->height,
							]);
						}
					}
				}

				if(!$text)
				{
					$text=Yii::t('cms', '[ No title ]');
					$cssClass='text-muted';
				}

				return Html::a($text, ['update', 'id'=>$section->id], ['class'=>$cssClass]);
			}
		];
	}

	/**
	 * @return array
	 */
	public function renderFileCountColumn()
	{
		return [
			'attribute'=>'file_count',
			'headerOptions'=>['class'=>'hidden-xs text-center'],
			'contentOptions'=>['class'=>'hidden-xs text-center'],
			'content'=>function(Section $section)
			{
				$fileCount=Yii::$app->getFormatter()->asInteger($section->file_count);
				return Html::a($fileCount, ['update', 'id'=>$section->id, '#'=>'files'], ['class'=>'badge']);
			}
		];
	}

	/**
	 * @return array
	 */
	public function renderUpdatedAtColumn()
	{
		return [
			'attribute'=>'updated_at',
			'headerOptions'=>['class'=>'hidden-sm hidden-xs'],
			'contentOptions'=>['class'=>'text-nowrap hidden-sm hidden-xs'],
			'content'=>function(Section $section)
			{
				return Timeago::tag($section->updated_at);
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
			'content'=>function(Section $section)
			{
				$buttons=[];

				if($this->getIsSortedByPosition())
				{
					$buttons[]=Html::tag('span', FA::icon('arrows-alt'), ['class'=>'btn btn-secondary sortable-handle']);
				}

				$buttons[]=Html::a(FA::icon('wrench'), ['update', 'id'=>$section->id], ['class'=>'btn btn-secondary']);

				return Html::buttons($buttons);
			}
		];
	}

	/***********************************************************************
	 * Getters / setters.
	 ***********************************************************************/

	/**
	 * Loads pictures for sections without name.
	 * @return File[]
	 */
	protected function getFiles()
	{
		if($this->_files===null)
		{
			$query=File::find()
				->where(['page_id'=>$this->page->id])
				->andWhere(['not', ['section_id'=>null]])
				->withPictureSrcsetAttributes(Picture::ADMIN_PICTURE_ID);

			foreach($query->all() as $file)
			{
				$file->populateRelation('page', $this->page);
				$this->_files[$file->section_id]=$file;
			}
		}

		return $this->_files;
	}

	/**
	 * @param int $sectionId
	 * @return File|null
	 */
	protected function getFile($sectionId)
	{
		return ArrayHelper::getValue($this->getFiles(), $sectionId);
	}
}