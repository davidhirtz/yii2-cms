<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\widgets\CategoryTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use Yii;
use yii\helpers\ArrayHelper;

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
            $defaultOptions = ['prompt' => ['text' => '']];

            foreach ($attributeNames as $language => $attributeName) {
                $defaultOptions['data-form-target'][] = $this->getSlugId($language);
                $defaultOptions['prompt']['options']['data-value'][] = $this->getSlugBaseUrl($language);
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
        if ($route = $this->model->getRoute()) {
            return rtrim(Yii::$app->getUrlManager()->createAbsoluteUrl(array_merge($route, ['category' => '', 'language' => $language])), '/') . '/';
        }

        return '';
    }

    /**
     * @param Category $category
     * @param string $language
     * @return string
     */
    protected function getCategoryBaseUrl($category, $language = null): string
    {
        if ($route = $category->getRoute()) {
            $draftHostInfo = Yii::$app->getRequest()->getDraftHostInfo();
            $urlManager = Yii::$app->getUrlManager();
            $route = array_merge($route, ['language' => $language]);

            if (mb_strlen($route['category'], Yii::$app->charset) > $this->slugMaxLength) {
                $route['category'] = '...' . mb_substr($route['category'], -$this->slugMaxLength, $this->slugMaxLength, Yii::$app->charset);
            }

            return rtrim($category->isEnabled() || !$draftHostInfo ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route), '/') . '/';
        }

        return '';
    }
}