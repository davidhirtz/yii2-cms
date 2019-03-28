<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\nav\base;

use davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class Submenu.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\nav\base
 */
class Submenu extends \davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu
{
    use ModuleTrait;

    /**
     * @var EntryForm|SectionForm
     */
    public $model;

    /**
     * Initializes the nav items.
     */
    public function init()
    {
        if (!$this->items && $this->model) {

            $isSection = $this->model instanceof SectionForm || Yii::$app->controller->id == 'section';
            $entry = $this->model instanceof SectionForm ? $this->model->entry : $this->model;

            if (!$this->title) {
                $this->title = Html::a($entry->getI18nAttribute('name'), ['/admin/entry/update', 'id' => $entry->id]);
            }

            if (static::getModule()->enableSections) {
                $this->items = [
                    [
                        'label' => Yii::t('cms', 'Entry'),
                        'url' => ['/admin/entry/update', 'id' => $entry->id],
                        'active' => !$isSection,
                        'icon' => 'book',
                        'labelOptions' => [
                            'class' => 'd-none d-md-inline'
                        ],
                    ],
                    [
                        'label' => Yii::t('cms', 'Sections'),
                        'url' => ['/admin/section/index', 'entry' => $entry->id],
                        'badge' => $entry->section_count ?: null,
                        'badgeOptions' => [
                            'id' => 'entry-section-count',
                            'class' => 'badge d-none d-md-inline-block',
                        ],
                        'icon' => 'th-list',
                        'active' => $isSection,
                        'options' => [
                            'class' => 'entry-sections',
                        ],
                        'labelOptions' => [
                            'class' => 'd-none d-md-inline'
                        ],
                    ],
                ];
            }
        }

        if (!$this->title) {
            $this->title = Yii::t('cms', 'Entries');
        }

        parent::init();
    }
}