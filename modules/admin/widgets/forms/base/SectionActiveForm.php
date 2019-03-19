<?php
namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\base;
use app\modules\content\modules\admin\models\forms\SectionForm;

/**
 * Class SectionActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms\base
 * @see SectionActiveForm
 *
 * @property SectionForm $model
 */
class SectionActiveForm extends ActiveForm
{
	/**
	 * @inheritdoc
	 */
	public function renderFields()
	{
		echo $this->fieldStatus();
		echo $this->fieldType();
		echo $this->fieldName();
		echo $this->fieldSlug();
		echo $this->fieldContent();

		$this->customFields();
		
		echo $this->submitButton($this->model);
		echo $this->renderInfo();
	}

	/**
	 * @inheritdoc
	 */
	public function fieldSlug($options=[])
	{
		if($this->model->hasSlug)
		{
			return parent::fieldSlug($options);
		}
	}
	/**
	 * @return string
	 */
	public function getSlugUrl()
	{
		return trim($this->model->page->category ? ($this->model->page->category->slug.'/'.$this->model->page->slug) : $this->model->page->slug, '/').'/';
	}
}