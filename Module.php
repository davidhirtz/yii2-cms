<?php

namespace davidhirtz\yii2\cms;

use Yii;

/**
 * Class Module
 * @package davidhirtz\yii2\cms
 */
class Module extends \yii\base\Module
{
    /**
     * @var bool
     */
    public $enabledNestedSlugs = false;

    /**
     * @var bool
     */
    public $enableI18nTables = false;

    /**
     * @var bool
     */
    public $enableSections = true;

    /**
     * @var array
     */
    public $defaultPageSort;

    /**
     * @var string
     */
    public $tablePrefix;

    /**
     * @param string $tableName
     * @return string
     */
    public function getTableName($tableName)
    {
        $tableName = $this->tablePrefix . $tableName;
        return '{{%' . ($this->enableI18nTables ? Yii::$app->getI18n()->getAttributeName($tableName) : $tableName) . '}}';
    }
}