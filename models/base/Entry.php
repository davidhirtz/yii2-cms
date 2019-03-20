<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeValidator;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use Yii;
use yii\helpers\Inflector;
use yii\helpers\Json;

/**
 * Class Entry.
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
 * @property int $file_count
 * @property bool $order_by
 * @property Section[] $sections
 * @property  \davidhirtz\yii2\cms\models\Entry $entry
 * @method static \davidhirtz\yii2\cms\models\Entry findOne($condition)
 */
class Entry extends ActiveRecord
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
    public function afterFind()
    {
        if ($this->order_by) {
            $this->order_by = Json::decode($this->order_by);
        }

        parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate(): bool
    {
        if ($this->customSlugBehavior) {
            $this->slug = Inflector::slug($this->slug);
        }

        return parent::beforeValidate();
    }


    /**
     * @inheritdoc
     */
    public function beforeSave($insert): bool
    {
        if (!$this->publish_date) {
            $this->publish_date = new DateTime;
        }

        if ($insert) {
            $this->position = $this->findSiblings()->max('[[position]]') + 1;
        }

        if ($this->order_by) {
            $this->order_by = Json::encode($this->order_by);
        }

        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($this->order_by) {
            $this->order_by = Json::decode($this->order_by);
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return ActiveQuery
     */
    public function getSections(): ActiveQuery
    {
        return $this->hasMany(Section::class, ['entry_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');
    }

    /**
     * @inheritdoc
     * @return EntryQuery
     */
    public static function find(): EntryQuery
    {
        return new EntryQuery(get_called_class());
    }

    /**
     * @return EntryQuery
     */
    public function findSiblings(): EntryQuery
    {
        return static::find()->where(['parent_id' => $this->parent_id]);
    }

    /**
     * @return false|int
     */
    public function recalculateSectionCount()
    {
        $this->section_count = $this->getSections()->count();
        return $this->update(false);
    }
    
    /**
     * @return array
     */
    public function getRoute(): array
    {
        return array_filter(['/cms/site/view', 'entry' => $this->slug]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'slug' => Yii::t('cms', 'Url'),
            'title' => Yii::t('cms', 'Meta title'),
            'description' => Yii::t('cms', 'Meta description'),
            'section_count' => Yii::t('cms', 'Sections'),
        ]);
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return 'Entry';
    }

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return static::getModule()->getTableName('entry');
    }
}