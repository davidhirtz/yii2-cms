<?php
namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\base;
use app\components\helpers\ArrayHelper;
use app\components\helpers\Html;
use app\modules\content\models\Category;
use app\modules\content\models\Page;
use app\modules\content\modules\admin\models\forms\PageForm;
use Yii;
use yii\jui\DatePicker;
use yii\web\JsExpression;

/**
 * Class PageActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms\base
 * @see PageActiveForm
 *
 * @property PageForm $model
 */
class PageActiveForm extends ActiveForm
{
	/**
	 * @inheritdoc
	 */
	public function renderFields()
	{
		echo $this->fieldStatus();
		echo $this->fieldCategoryId();
		echo $this->fieldType();
		echo $this->fieldName();

		echo $this->fieldSlug();
		echo $this->fieldContent();
		echo $this->fieldPublishDate();

		$this->customFields();

		echo $this->submitButton($this->model);
		echo $this->renderInfo();
	}

	/***********************************************************************
	 * Fields.
	 ***********************************************************************/

	/**
	 * @param array $options
	 * @return string|\yii\bootstrap4\ActiveField
	 */
	public function fieldCategoryId($options=[])
	{
		$categoryOptions=$this->getCategoryOptions();
		$count=count($categoryOptions);

		if($count>1)
		{
			$defaultOptions=[];

			if(!Page::$requireCategory)
			{
				$defaultOptions['prompt']='';
			}

			foreach($this->getCategories() as $category)
			{
				if(!isset($options['options'][$category->id]['data-value']))
				{
					$options['options'][$category->id]['data-value']=$this->getCategorySlug($category);
				}

				$options['options'][$category->id]['data-sort-by-position']=$category->sort_by_position;
			}

			$defaultOptions['data-form-target']=$this->getSlugId();

			return $this->field($this->model, 'category_id')->dropDownList($categoryOptions, ArrayHelper::merge($defaultOptions, $options));
		}

		elseif($count && Page::$requireCategory)
		{
			return Html::activeHiddenInput($this->model, 'category_id', [
				'value'=>key($categoryOptions),
			]);
		}
	}

	/**
	 * @param array $options
	 * @return \yii\bootstrap4\ActiveField|\yii\widgets\ActiveField
	 */
	public function fieldPublishDate($options=[])
	{
		if(Page::$hasPublishDate)
		{
			$defaults=[
				'options'=>['class'=>'form-control'],
				'language'=>Yii::$app->language,
				'dateFormat'=>'php:Y-m-d H:i',
				'clientOptions'=>[
					'onSelect'=>new JsExpression('function(t){$(this).val(t.slice(0, 10)+" 00:00");}'),
				]
			];

			return $this->field($this->model, 'publish_date', ['inputTemplate'=>'<div class="input-group">{input}<div class="input-group-append"><span class="input-group-text">'.Yii::$app->getUser()->getIdentity()->getTimezoneOffset().'</span></div></div>'])->widget(DatePicker::class, array_merge($defaults, $options));
		}
	}

	/***********************************************************************
	 * Getters / setters.
	 ***********************************************************************/

	/**
	 * @return string
	 */
	public function getSlugUrl()
	{
		if($this->model->category_id)
		{
			return $this->getCategorySlug($this->model->category);
		}
	}

	/**
	 * @return array
	 */
	public function getCategoryOptions()
	{
		$attribute=Category::instance()->getI18nAttributeName('name');
		return Category::$hasNestedTree ? Category::indentNestedTree($this->getCategories(), $attribute) : ArrayHelper::getColumn($this->getCategories(), $attribute);
	}
}