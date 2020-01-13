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
     * @var bool whether entries should be categorized
     */
    public $enableCategories = false;

    /**
     * @var bool whether entries should automatically inherit parent categories
     */
    public $inheritNestedCategories = true;

    /**
     * @var int duration in seconds for the caching the Category::getCategories() category query
     */
    public $categoryCachedQueryDuration = 60;

    /**
     * @var bool whether entries should have sections
     */
    public $enableSections = true;

    /**
     * @var bool whether entries should have assets
     */
    public $enableEntryAssets = true;

    /**
     * @var bool whether sections should have assets
     */
    public $enableSectionAssets = true;

    /**
     * @var array the default sort when neither type nor category apply an order
     */
    public $defaultEntryOrderBy;

    /**
     * @var array the default entry type which is applied to all default admin urls
     */
    public $defaultEntryType;

    /**
     * @todo currently not fully implemented
     * @var array the default category type which is applied to all default admin urls
     */
    public $defaultCategoryType;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->enableSections) {
            $this->enableSectionAssets = false;
        }

        if (!$this->enableCategories) {
            $this->inheritNestedCategories = false;
        }

        parent::init();
    }
}