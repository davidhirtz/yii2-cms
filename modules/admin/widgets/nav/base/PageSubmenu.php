<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\nav\base;

use davidhirtz\yii2\cms\components\ModuleTrait;
use davidhirtz\yii2\cms\modules\admin\models\forms\PageForm;
use Yii;

/**
 * Class PageSubmenu.
 * @package davidhirtz\yii2\cms\modules\admin\widgets\nav\base
 */
class PageSubmenu extends \davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu
{
    use ModuleTrait;

    /**
     * @var PageForm
     */
    public $page;

    /**
     * Initializes the nav items.
     */
    public function init()
    {
        if (!$this->items) {
            if ($this->page) {
                $this->items = [
                    [
                        'label' => Yii::t('cms', 'Page'),
                        'url' => ['page/update', 'id' => $this->page->id],
                        'active' => ['page/'],
                        'icon' => 'book hidden-xs',
                    ],
                    [
                        'label' => Yii::t('cms', 'Sections'),
                        'url' => ['section/index', 'page' => $this->page->id],
                        'visible' => static::getModule()->enableSections,
                        'badge' => $this->page->section_count ?: null,
                        'badgeOptions' => [
                            'id' => 'page-section-count',
                            'class' => 'badge',
                        ],
                        'icon' => 'th-list hidden-xs',
                        'active' => ['section'],
                        'options' => [
                            'class' => 'page-sections',
                        ],
                    ],
                ];
            }
        }

        if (!$this->title) {
            $this->title = $this->page ? $this->page->getI18nAttribute('name') : Yii::t('cms', 'Pages');
        }

        parent::init();
    }
}