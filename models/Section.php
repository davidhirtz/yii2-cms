<?php

namespace davidhirtz\yii2\cms\models;

/**
 * Class Section.
 * @package davidhirtz\yii2\cms\models
 */
class Section extends \davidhirtz\yii2\cms\models\base\Section
{
    public $name_de;
    public $content_de;
    public $i18nAttributes = ['name', 'content'];
}