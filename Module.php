<?php

namespace davidhirtz\yii2\cms;

use davidhirtz\yii2\skeleton\modules\ModuleTrait;

/**
 * Class Module
 * @package davidhirtz\yii2\cms
 */
class Module extends \yii\base\Module
{
    use ModuleTrait;

    /**
     * @var bool
     */
    public $enabledNestedSlugs = false;

    /**
     * @var bool
     */
    public $enableSections = true;

    /**
     * @var bool
     */
    public $enableEntryAssets = true;

    /**
     * @var bool
     */
    public $enableSectionAssets = true;

    /**
     * @var array
     */
    public $defaultEntryOrderBy;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->enableSections) {
            $this->enableSectionAssets = false;
        }

        parent::init();
    }
}