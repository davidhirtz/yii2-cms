<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\nav\base;

use davidhirtz\yii2\cms\components\ModuleTrait;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use Yii;

/**
 * Class EntrySubmenu.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\nav\base
 */
class EntrySubmenu extends \davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu
{
    use ModuleTrait;

    /**
     * @var EntryForm
     */
    public $entry;

    /**
     * Initializes the nav items.
     */
    public function init()
    {
        if (!$this->items) {
            if ($this->entry) {
                $this->items = [
                    [
                        'label' => Yii::t('cms', 'Entry'),
                        'url' => ['entry/update', 'id' => $this->entry->id],
                        'active' => ['entry/'],
                        'icon' => 'book hidden-xs',
                    ],
                    [
                        'label' => Yii::t('cms', 'Sections'),
                        'url' => ['section/index', 'entry' => $this->entry->id],
                        'visible' => static::getModule()->enableSections,
                        'badge' => $this->entry->section_count ?: null,
                        'badgeOptions' => [
                            'id' => 'entry-section-count',
                            'class' => 'badge',
                        ],
                        'icon' => 'th-list hidden-xs',
                        'active' => ['section'],
                        'options' => [
                            'class' => 'entry-sections',
                        ],
                    ],
                ];
            }
        }

        if (!$this->title) {
            $this->title = $this->entry ? $this->entry->getI18nAttribute('name') : Yii::t('cms', 'Entries');
        }

        parent::init();
    }
}