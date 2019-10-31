<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\widgets\CategoryTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class CategoryActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms\base
 *
 * @property Category $model
 */
class CategoryActiveForm extends ActiveForm
{
    use CategoryTrait, ModuleTrait;

    /**
     * @var int
     */
    public $slugMaxLength = 20;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                'status',
                'parent_id',
                'type',
                'name',
                'content',
                '-',
                'title',
                'description',
                'slug',
            ];
        }

        parent::init();
    }

    /**
     * @param array $options
     * @return \yii\bootstrap4\ActiveField
     */
    public function parentIdField($options = [])
    {
        if ($categories = static::getCategories()) {
            $attributeNames = $this->model->getI18nAttributeNames('slug');
            $defaultOptions = [
                'prompt' => [
                    'options' => ['data-value' => ''],
                    'text' => '',
                ],
            ];

            foreach ($attributeNames as $language => $attributeName) {
                $defaultOptions['data-form-target'][] = $this->getSlugId($language);
            }

            foreach ($categories as $category) {
                if ($category->lft >= $this->model->lft && $category->rgt <= $this->model->rgt) {
                    $defaultOptions['options'][$category->id]['disabled'] = true;
                }

                foreach ($attributeNames as $language => $attributeName) {
                    $defaultOptions['options'][$category->id]['data-value'][] = $this->getCategoryBaseUrl($category, $language);
                }
            }

            $items = Category::indentNestedTree($categories, $this->model->getI18nAttributeName('name'));
            return $this->field($this->model, 'parent_id')->dropDownList($items, ArrayHelper::merge($defaultOptions, $options));
        }

        return '';
    }

    /**
     * @param null $language
     * @return string
     */
    protected function getSlugBaseUrl($language = null): string
    {
        return Html::tag('span', parent::getSlugBaseUrl($language), ['id' => $this->getSlugId()]);
    }

    /**
     * @param string $language
     * @return string
     */
    protected function getSlugId($language = null)
    {
        return $this->getId() . '-' . $this->model->getI18nAttributeName('slug', $language);
    }

    /**
     * @param Category $category
     * @param string $language
     * @return string
     */
    protected function getCategoryBaseUrl($category, $language = null): string
    {
        $draftHostInfo = Yii::$app->getRequest()->getDraftHostInfo();
        $urlManager = Yii::$app->getUrlManager();
        $route = array_merge($category->getRoute(), ['language' => $language]);

        if (mb_strlen($route['category'], Yii::$app->charset) > $this->slugMaxLength) {
            $route['category'] = '...' . mb_substr($route['category'], -$this->slugMaxLength, $this->slugMaxLength, Yii::$app->charset);
        }

        return $category->isEnabled() || !$draftHostInfo ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route);
    }
}