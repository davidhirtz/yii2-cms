<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\queries\PageQuery;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeValidator;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
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
 * @property Section[] $sections
 * @property  \davidhirtz\yii2\cms\models\Page $page
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
     * @return ActiveQuery
     */
    public function getSections()
    {
        return $this->hasMany(Section::class, ['page_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('page');
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
        return array_merge(parent::attributeLabels(), [
            'slug' => Yii::t('cms', 'Url'),
            'title' => Yii::t('skeleton', 'Meta title'),
            'description' => Yii::t('cms', 'Meta description'),
            'section_count' => Yii::t('skeleton', 'Sections'),
            'media_count' => Yii::t('skeleton', 'Media'),
        ]);
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