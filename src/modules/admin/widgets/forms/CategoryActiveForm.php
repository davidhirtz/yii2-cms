<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\CategoryCollection;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveField;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * @property Category $model
 */
class CategoryActiveForm extends ActiveForm
{
    use ModuleTrait;

    public int $slugMaxLength = 20;

    public bool $hasStickyButtons = true;

    public function init(): void
    {
        $this->fields ??= [
            'status',
            'parentId',
            'type',
            'name',
            'content',
            '-',
            'title',
            'description',
            'slug',
        ];

        parent::init();
    }

    /** @noinspection PhpUnused {@see static::$fields} */
    public function parentIdField(array $options = []): ActiveField|string
    {
        if (static::getModule()->enableNestedCategories) {
            if ($categories = CategoryCollection::getAll()) {
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
                $options = ArrayHelper::merge($defaultOptions, $options);

                return $this->field($this->model, 'parent_id')->dropDownList($items, $options);
            }
        }

        return '';
    }

    /**
     * @param string|null $language
     * @return string
     */
    public function getSlugBaseUrl(?string $language = null): string
    {
        if ($route = $this->model->getRoute()) {
            $urlManager = Yii::$app->getUrlManager();

            $route = [
                ...$route,
                'category' => '',
                'language' => $urlManager->i18nUrl || $urlManager->i18nSubdomain ? $language : null,
            ];

            return rtrim($urlManager->createAbsoluteUrl($route), '/') . '/';
        }

        return '';
    }

    protected function getCategoryBaseUrl(Category $category, ?string $language = null): string
    {
        if ($route = $category->getRoute()) {
            $draftHostInfo = Yii::$app->getRequest()->getDraftHostInfo();
            $urlManager = Yii::$app->getUrlManager();

            $route = array_filter(array_merge($route, ['language' => $urlManager->i18nUrl || $urlManager->i18nSubdomain ? $language : null]));

            if (isset($route['category']) && mb_strlen((string)$route['category'], Yii::$app->charset) > $this->slugMaxLength) {
                $route['category'] = mb_substr((string)$route['category'], -$this->slugMaxLength, $this->slugMaxLength, Yii::$app->charset);
            }

            return rtrim($category->isEnabled() || !$draftHostInfo ? $urlManager->createAbsoluteUrl($route) : $urlManager->createDraftUrl($route), '/') . '/';
        }

        return '';
    }
}