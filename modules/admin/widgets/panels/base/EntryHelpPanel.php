<?php
namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;
use app\modules\admin\components\widgets\panels\HelpPanel;
use app\modules\content\models\Entry;
use rmrevin\yii\fontawesome\FA;
use Yii;
use yii\helpers\Html;

/**
 * Class EntryHelpPanel.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\panels\base
 * @see EntryHelpPanel
 */
class EntryHelpPanel extends HelpPanel
{
	/**
	 * @var Entry
	 */
	public $entry;

	/**
	 * Sets content.
	 */
	public function init()
	{
		if(Entry::$hasSections)
		{
			if(!$this->title)
			{
				$this->title=Yii::t('cms', 'Sections');
			}

			if(!$this->content)
			{
				$this->content=$this->renderText().$this->renderButtons();
			}
		}

		parent::init();
	}

	/**
	 * Renders text.
	 */
//	public function renderText()
//	{
//		return $this->renderHelpBlock(Yii::t('cms', 'This entry has {count, plural, =0{no sections yet} =1{one section} other{# sections}}{public, select, 1{, click {here} to view the entry on the website} other{ but was not published yet and thus will not be displayed on the website}}.', [
//			'count'=>$this->entry->section_count,
//			'public'=>$this->entry->getIsPublic() ? 1 : 0,
//			'here'=>$this->entry->getRoute() ? Html::a(Yii::t('cms', 'here'), $this->entry->getRoute(), ['target'=>'_blank']) : Html::tag('s', Yii::t('app', 'here')),
//		]));
//	}

	/**
	 * @return string
	 */
	public function renderSectionCreateButton()
	{
		$text=FA::icon('pencil fa-fw').' '.Yii::t('cms', 'Create Section');
		return Html::a($text, ['/cms/admin/section/create', 'id'=>$this->entry->id], ['class'=>'btn btn-secondary']);
	}

	/**
	 * @return string
	 */
	public function renderSectionIndexButton()
	{
		$text=FA::icon('th-list fa-fw').' '.Yii::t('cms', 'View All Sections');
		return Html::a($text, ['/cms/admin/section/index', 'entry'=>$this->entry->id], ['class'=>'btn btn-secondary']);
	}
	
	/**
	 * Renders buttons.
	 */
	public function renderButtons()
	{
		return $this->renderButtonToolbar([
			$this->renderSectionCreateButton(),
			$this->renderSectionIndexButton(),
		]);
	}
}