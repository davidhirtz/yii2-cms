<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;
use Yii;

/**
 * Class EntryCategory.
 * @package davidhirtz\yii2\cms\models\base
 *
 * @property int $entry_id
 * @property int $category_id
 * @property int $position
 * @property int $updated_by_user_id
 * @property DateTime $updated_at
 * @property DateTime $created_at
 * @property Entry $entry
 * @property Category $category
 * @property User $updated
 *
 * @method static \davidhirtz\yii2\cms\models\EntryCategory findOne($condition)
 */
class EntryCategory extends \davidhirtz\yii2\skeleton\db\ActiveRecord
{
    use ModuleTrait;

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                ['category_id', 'entry_id'],
                'required',
            ],
            [
                ['category_id', 'entry_id'],
                'filter',
                'filter' => 'intval',
            ],
            [
                ['category_id'],
                'validateCategoryId',
            ],
            [
                ['entry_id'],
                'validateEntryId',
            ],
            [
                ['entry_id'],
                'unique',
                'targetAttribute' => ['entry_id', 'category_id'],
            ],
        ]);
    }

    /**
     * @see EntryCategory::rules()
     */
    public function validateCategoryId()
    {
        if (($this->isAttributeChanged('category_id') && !$this->refreshRelation('category')) || !$this->category->enableEntryCategory()) {
            $this->addInvalidAttributeError('category_id');
        }
    }

    /**
     * @see EntryCategory::rules()
     */
    public function validateEntryId()
    {
        if ($this->isAttributeChanged('entry_id') && !$this->refreshRelation('entry')) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeSave($insert)
    {
        $this->attachBehaviors([
            'BlameableBehavior' => 'davidhirtz\yii2\skeleton\behaviors\BlameableBehavior',
            'TimestampBehavior' => [
                'class' => 'davidhirtz\yii2\skeleton\behaviors\TimestampBehavior',
                'createdAtAttribute' => null,
            ],
        ]);

        if ($insert) {
            $this->position = static::find()->where(['category_id' => $this->category_id])->max('[[position]]') + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @inheritDoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->category->recalculateEntryCount();
        $this->entry->recalculateCategoryIds();

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritDoc
     */
    public function afterDelete()
    {
        if ($this->category) {
            $this->category->recalculateEntryCount();
        }

        if ($this->entry) {
            $this->entry->recalculateCategoryIds();
        }

        parent::afterDelete();
    }

    /**
     * @return CategoryQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    /**
     * @return EntryQuery
     */
    public function getEntry()
    {
        return $this->hasOne(Entry::class, ['id' => 'entry_id']);
    }

    /**
     * @return UserQuery
     */
    public function getUpdated(): UserQuery
    {
        return $this->hasOne(User::class, ['id' => 'updated_by_user_id']);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'updated_at' => Yii::t('cms', 'Added'),
        ]);
    }


    /**
     * @return string
     */
    public function formName(): string
    {
        return 'EntryCategory';
    }

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return static::getModule()->getTableName('entry_category');
    }
}