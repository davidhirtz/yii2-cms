<?php

namespace davidhirtz\yii2\cms\modules\admin\models\forms\base;

use davidhirtz\yii2\cms\models\Page;
use davidhirtz\yii2\datetime\DateTime;
use yii\helpers\Inflector;

/**
 * Class PageForm
 * @package davidhirtz\yii2\cms\modules\admin\models\forms\base
 *
 * @method static \davidhirtz\yii2\cms\modules\admin\models\forms\PageForm findOne($condition)
 */
class PageForm extends Page
{
    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if(!$this->customSlugBehavior)
        {
            $this->slug=Inflector::slug($this->slug);
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if(!$this->publish_date)
        {
            $this->publish_date=new DateTime;
        }

        return parent::beforeSave($insert);
    }
}