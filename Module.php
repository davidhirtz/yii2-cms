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
     * @var array
     */
    public $defaultEntryOrderBy;
}