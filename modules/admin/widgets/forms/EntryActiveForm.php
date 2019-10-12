<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\CKEditor;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\DatePicker;
use yii\web\JsExpression;

/**
 * Class EntryActiveForm.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms\base
 *
 * @property Entry $model
 */
class EntryActiveForm extends ActiveForm
{
    use ModuleTrait;

    /**
     * @var int
     */
    public $slugMaxLength = 20;

    /**
     * @var bool
     */
    public $showUnsafeAttributes = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->fields) {
            $this->fields = [
                ['status', 'dropDownList', ArrayHelper::getColumn($this->model::getStatuses(), 'name')],
                ['parent_id', ['visible' => static::getModule()->enableNestedEntries]],
                ['type', 'dropDownList', ArrayHelper::getColumn($this->model::getTypes(), 'name')],
                ['name'],
                ['content', ['options' => ['style' => !$this->model->contentType ? 'display:none' : null]], $this->model->contentType === 'html' ? CKEditor::class : 'textarea', ['validator' => $this->model->contentType === 'html' ? $this->model->htmlValidator : null]],
                ['publish_date'],
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
        /** @var Entry[] $entries */
        $entries = Entry::find()
            ->select($this->model->getI18nAttributeNames(['id', 'name', 'slug']))
            ->where(['parent_id' => null])
            ->orderBy(static::getModule()->defaultEntryOrderBy ?: [$this->model->getI18nAttribute('name') => SORT_ASC])
            ->all();

        if ($entries) {
            $defaultOptions = [
                'data-form-target' => $this->getSlugId(),
                'prompt' => [
                    'options' => ['data-value' => ''],
                    'text' => '',
                ],
            ];

            $items = [];

            foreach ($entries as $entry) {
                $items[$entry->id] = $entry->getI18nAttribute('name');
                $defaultOptions['options'][$entry->id]['data-value'] = $this->getEntrySlug($entry);
            }

            return $this->field($this->model, 'parent_id')->dropDownList($items, ArrayHelper::merge($defaultOptions, $options));
        }

        return '';
    }

    /**
     * @return \yii\bootstrap4\ActiveField|\yii\widgets\ActiveField
     */
    public function publishDateField()
    {
        return $this->field($this->model, 'publish_date', ['inputTemplate' => '<div class="input-group">{input}<div class="input-group-append"><span class="input-group-text">' . Yii::$app->getUser()->getIdentity()->getTimezoneOffset() . '</span></div></div>'])->widget(DatePicker::class, [
            'options' => ['class' => 'form-control', 'autocomplete' => 'off'],
            'language' => Yii::$app->language,
            'dateFormat' => 'php:Y-m-d H:i',
            'clientOptions' => [
                'onSelect' => new JsExpression('function(t){$(this).val(t.slice(0, 10)+" 00:00");}'),
            ]
        ]);
    }

    /**
     * @return string
     */
    protected function getSlugId()
    {
        return $this->getId() . '-slug';
    }

    /**
     * @param Entry|Category $model
     * @return string
     */
    protected function getEntrySlug($model)
    {
        $route = ltrim(Url::to($model->getRoute()), '/');

        if (mb_strlen($route, Yii::$app->charset) > $this->slugMaxLength) {
            $route = Html::tag('span', '...', ['class' => 'text-muted']) . mb_substr($route, -$this->slugMaxLength, $this->slugMaxLength, Yii::$app->charset);
        }

        return $route ? "{$route}/" : '';
    }
}