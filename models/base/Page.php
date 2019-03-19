<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\queries\PageQuery;
use davidhirtz\yii2\datetime\DateTime;
use Yii;

/**
 * Class Page.
 * @package davidhirtz\yii2\cms\models\base
 *
 * @property int $parent_id
 * @property int $lft
 * @property int $rgt
 * @property int $position
 * @property string $name
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property DateTime $publish_date
 * @property int $section_count
 * @property int $media_count
 * @property bool $sort_by_publish_date
 *
 * @method \davidhirtz\yii2\cms\models\Page $page
 * @method static \davidhirtz\yii2\cms\models\Page findOne($condition)
 */
class Page extends ActiveRecord
{
    /**
     * @var bool
     */
    public $customSlugBehavior = true;

    /**
     * @inheritdoc
     * @return PageQuery
     */
    public static function find()
    {
        return new PageQuery(get_called_class());
    }

    /**
     * @return array
     */
    public function getRoute()
    {
        return array_filter(['/cms/site/view', 'page'=>$this->slug]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'=>Yii::t('skeleton', 'ID'),
            'status'=>Yii::t('skeleton', 'Status'),
            'type'=>Yii::t('skeleton', 'Type'),
            'name'=>Yii::t('skeleton', 'Name'),
            'slug'=>Yii::t('cms', 'Url'),
            'title'=>Yii::t('skeleton', 'Name'),
            'description'=>Yii::t('cms', 'Description'),
            'position'=>Yii::t('cms', 'Order'),
            'updated_by_user_id'=>Yii::t('skeleton', 'User'),
            'updated_at'=>Yii::t('skeleton', 'Last Update'),
            'created_at'=>Yii::t('skeleton', 'Created'),
            'media_count'=>Yii::t('skeleton', 'Media'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return static::getModule()->getTableName('page');
    }
}