<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\widgets\CategoryTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class CategoryActiveForm
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms
 *
 * @property Category $model
 */
class CategoryActiveForm extends ActiveForm
{
    use CategoryTrait;
    use ModuleTrait;

    /**
     * @var int
     */
    public $slugMaxLength = 20;

    /**
     * @var bool
     */
    public bool $hasStickyButtons = true;

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
     * @return string
     */
    public function parentIdField($options = [])
    {
        if (static::getModule()->enableNestedCategories) {
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
                return $this->field($this->model, 'parent_id')->dropdownList($items, ArrayHelper::merge($defaultOptions, $options));
            }
        }

        return '';
    }

    /**
     * @param null $language
     * @return string
     */
    public function getSlugBaseUrl($language = null): string
    {
        if ($route = $this->model->getRoute()) {
            $urlManager = Yii::$app->getUrlManager();
            return rtrim($urlManager->createAbsoluteUrl(array_merge($route, ['category' => '', 'language' => $urlManager->i18nUrl || $urlManager->i18nSubdomain ? $language : null])), '/') . '/';
        }

        return '';
    }

    /**
     * @param Category $category
     * @param string|null $language
     * @return string
     */
    protected function getCategoryBaseUrl($category, $language = null): string
    {
        if ($route = $category->getRoute()) {
            $draftHostInfo = Yii::$app->getRequest()->getDraftHostInfo();
            $urlManager = Yii::$app->getUrlManager();

            $route = array_filter(array_merge($route, ['language' => $urlManager->i18nUrl || $urlManager->i18nSubdomain ? $language : null]));

            if (isset($route['category']) && mb_strlen($route['category'], Yii::$app->charset) > $this->slugMaxLength) {
                $route['category'] = '...' . mb_substr($route['category'], -$this->slugMaxLength, $this->slugMaxLength, Yii::$app->charset);
            }

            return rtrim($category->isEnabled() || !$draftHostInfo ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route), '/') . '/';
        }

        return '';
    }
}