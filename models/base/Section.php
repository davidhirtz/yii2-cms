<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\queries\PageQuery;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use Yii;
use yii\helpers\Inflector;

/**
 * Class Section.
 * @package davidhirtz\yii2\cms\models\base
 *
 * @property int $page_id
 * @property int $position
 * @property string $name
 * @property string $slug
 * @property string $content
 * @property \davidhirtz\yii2\cms\models\Page $page
 * @method static \davidhirtz\yii2\cms\models\Section findOne($condition)
 */
class Section extends ActiveRecord
{
    /**
     * @return PageQuery
     */
    public function getPage(): PageQuery
    {
        return $this->hasOne(Page::class, ['id'=>'page_id']);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getI18nRules([
            [
                ['page_id'],
                'required',
            ],
            [
                ['page_id'],
                'filter',
                'filter'=>'intval',
            ],
            [
                ['page_id'],
                'validatePageId',
            ],
            [
                $this->getI18nAttributeNames(['name', 'slug', 'content']),
                'filter',
                'filter' => 'trim',
            ],
            [
                $this->getI18nAttributeNames(['slug']),
                'string',
                'max' => 100,
            ],
            [
                $this->getI18nAttributeNames(['name']),
                'string',
                'max' => 250,
            ],
            [
                ['slug'],
                'unique',
                'targetAttribute' => ['slug', 'page_id'],
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
        ]));
    }

    /**
     * Validates page and populates relation.
     */
    public function validatePageId()
    {
        if(!$this->page || (!$this->getIsNewRecord() && $this->isAttributeChanged('page_id')))
        {
            $this->addInvalidAttributeError('page_id');
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if ($this->slug) {
            $this->slug = Inflector::slug($this->slug);
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->position = $this->findSiblings()->max('[[position]]') + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return ActiveQuery
     */
    public function findSiblings()
    {
        return static::find()->where(['page_id' => $this->page_id]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'page_id' => Yii::t('skeleton', 'Page'),
            'slug' => Yii::t('cms', 'Url'),
            'section_count' => Yii::t('skeleton', 'Sections'),
            'media_count' => Yii::t('skeleton', 'Media'),
        ]);
    }

    /**
     * @return string
     */
    public function formName()
    {
        return 'Section';
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return static::getModule()->getTableName('section');
    }
}