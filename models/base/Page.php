<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\queries\PageQuery;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeValidator;
use Yii;
use yii\helpers\Inflector;

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
 * @property string $content
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
    public $customSlugBehavior = false;

    /**
     * @var bool|string
     */
    public $contentType = false;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getI18nRules([
            [
                ['name', 'slug'],
                'required',
            ],
            [
                $this->getI18nAttributeNames(['name', 'slug', 'title', 'description', 'content']),
                'filter',
                'filter' => 'trim',
            ],
            [
                $this->getI18nAttributeNames(['slug']),
                'string',
                'max' => 100,
            ],
            [
                $this->getI18nAttributeNames(['name', 'title', 'description']),
                'string',
                'max' => 250,
            ],
            [
                ['slug'],
                'unique',
                'targetAttribute' => static::getModule()->enabledNestedSlugs ? ['slug', 'category_id'] : 'slug',
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
            [
                ['publish_date'],
                DateTimeValidator::class,
            ],
        ]));
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if ($this->customSlugBehavior) {
            $this->slug = Inflector::slug($this->slug);
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!$this->publish_date) {
            $this->publish_date = new DateTime;
        }

        if ($insert) {
            $this->position = $this->findSiblings()->max('[[position]]') + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     * @return PageQuery
     */
    public static function find()
    {
        return new PageQuery(get_called_class());
    }

    /**
     * @return PageQuery
     */
    public function findSiblings()
    {
        return static::find()->where(['parent_id' => $this->parent_id]);
    }

    /**
     * @return array
     */
    public function getRoute()
    {
        return array_filter(['/cms/site/view', 'page' => $this->slug]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('skeleton', 'ID'),
            'status' => Yii::t('skeleton', 'Status'),
            'type' => Yii::t('skeleton', 'Type'),
            'name' => Yii::t('skeleton', 'Name'),
            'slug' => Yii::t('cms', 'Url'),
            'title' => Yii::t('skeleton', 'Meta title'),
            'description' => Yii::t('cms', 'Meta description'),
            'position' => Yii::t('cms', 'Order'),
            'updated_by_user_id' => Yii::t('skeleton', 'User'),
            'updated_at' => Yii::t('skeleton', 'Last Update'),
            'created_at' => Yii::t('skeleton', 'Created'),
            'media_count' => Yii::t('skeleton', 'Media'),
        ];
    }

    /**
     * @return string
     */
    public function formName()
    {
        return 'Page';
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return static::getModule()->getTableName('page');
    }
}