<?php
namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\widgets\CategoryTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Class CategoryActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms\base
 *
 * @property Category $model
 */
class CategoryActiveForm extends ActiveForm
{
    use CategoryTrait;

    /**
     * @var bool
     */
    public $showUnsafeAttributes = true;

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
                ['status', 'dropDownList', ArrayHelper::getColumn($this->model::getStatuses(), 'name')],
                ['parent_id'],
                ['type', 'dropDownList', ArrayHelper::getColumn($this->model::getTypes(), 'name')],
                ['name'],
                ['content', ['options' => ['style' => !$this->model->contentType ? 'display:none' : null]], $this->model->contentType === 'html' ? CKEditor::class : 'textarea'],
                ['-'],
                ['title'],
                ['description', 'textarea'],
                ['slug', ['enableClientValidation' => false], 'url'],
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
            $defaultOptions = [
                'data-form-target' => $this->getSlugId(),
                'prompt' => [
                    'options' => ['data-value' => ''],
                    'text' => '',
                ],
            ];

            foreach ($categories as $category) {
                if ($category->lft >= $this->model->lft && $category->rgt <= $this->model->rgt) {
                    $defaultOptions['options'][$category->id]['disabled'] = true;
                }

                $defaultOptions['options'][$category->id]['data-value'] = $this->getCategorySlug($category);
            }

            $items = Category::indentNestedTree($categories, $this->model->getI18nAttributeName('name'));
            return $this->field($this->model, 'parent_id')->dropDownList($items, ArrayHelper::merge($defaultOptions, $options));
        }

        return '';
    }

    /**
     * @param null $attribute
     * @return string
     */
    public function getBaseUrl($attribute = null)
    {
        return parent::getBaseUrl() . Html::tag('span', '', ['id' => $this->getSlugId()]);
    }

    /**
     * @return string
     */
    protected function getSlugId()
    {
        return $this->getId() . '-slug';
    }

    /**
     * @param Category $category
     * @return string
     */
    protected function getCategorySlug($category)
    {
        $route = ltrim(Url::to($category->getRoute()), '/');

        if (mb_strlen($route, Yii::$app->charset) > $this->slugMaxLength) {
            $route = Html::tag('span', '...', ['class' => 'text-muted']) . mb_substr($route, -$this->slugMaxLength, $this->slugMaxLength, Yii::$app->charset);
        }

        return $route ? "{$route}/" : '';
    }
}