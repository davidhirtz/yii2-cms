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
 * Class EntryCategory
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
 * @method static \davidhirtz\yii2\cms\models\EntryCategory[] findAll($condition)
 */
class EntryCategory extends \davidhirtz\yii2\skeleton\db\ActiveRecord
{
    use ModuleTrait;

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'TrailBehavior' => 'davidhirtz\yii2\skeleton\behaviors\TrailBehavior',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                ['category_id'],
                'davidhirtz\yii2\skeleton\validators\RelationValidator',
                'relation' => 'category',
                'required' => true,
            ],
            [
                ['entry_id'],
                'davidhirtz\yii2\skeleton\validators\RelationValidator',
                'relation' => 'entry',
                'required' => true,
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
        if (!$this->category->hasEntriesEnabled()) {
            $this->addInvalidAttributeError('category_id');
        }
    }

    /**
     * @see EntryCategory::rules()
     */
    public function validateEntryId()
    {
        if (!$this->entry->hasCategoriesEnabled()) {
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

        if ($this->position === null) {
            $this->position = $this->getMaxPosition() + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @inheritDoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            if (!$this->getIsBatch()) {
                if ($this->category->inheritNestedCategories()) {
                    $this->insertCategoryAncestors();
                }

                $this->updateEntryCategoryIds();
            }

            $this->updateCategoryEntryCount();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritDoc
     */
    public function afterDelete()
    {
        if (!$this->getIsBatch()) {
            if ($this->category->inheritNestedCategories()) {
                $this->deleteDescendantCategories();
            }

            if (!$this->entry->isDeleted()) {
                $this->updateEntryCategoryIds();
            }
        }

        if (!$this->category->isDeleted()) {
            $this->updateCategoryEntryCount();
        }

        parent::afterDelete();
    }

    /**
     * @return CategoryQuery
     */
    public function getCategory()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    /**
     * @return EntryQuery
     */
    public function getEntry()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(Entry::class, ['id' => 'entry_id']);
    }

    /**
     * @return UserQuery
     */
    public function getUpdated(): UserQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(User::class, ['id' => 'updated_by_user_id']);
    }

    /**
     * @param Category $category
     */
    public function populateCategoryRelation($category)
    {
        $this->populateRelation('category', $category);
        $this->category_id = $category->id;
    }

    /**
     * @param Entry $entry
     */
    public function populateEntryRelation($entry)
    {
        $this->populateRelation('entry', $entry);
        $this->entry_id = $entry->id;
    }

    /**
     * Updates {@link \davidhirtz\yii2\cms\models\Entry::$category_ids}.
     * @return bool|int
     */
    public function updateEntryCategoryIds()
    {
        $this->entry->recalculateCategoryIds();
        return $this->entry->update();
    }

    /**
     * Updates {@link \davidhirtz\yii2\cms\models\Category::$entry_count}.
     * @return bool|int
     */
    public function updateCategoryEntryCount()
    {
        $this->category->recalculateEntryCount();
        return $this->category->update();
    }

    /**
     * Inserts ascending categories.
     */
    public function insertCategoryAncestors()
    {
        if ($categories = $this->category->getAncestors(true)) {
            foreach ($categories as $category) {
                if ($category->inheritNestedCategories()) {
                    $junction = new static();
                    $junction->populateInheritedRelation($this, $category);
                    $junction->insert();
                }
            }
        }
    }

    /**
     * @param static $entryCategory
     * @param Category $category
     */
    protected function populateInheritedRelation($entryCategory, $category)
    {
        $this->populateEntryRelation($entryCategory->entry);
        $this->populateCategoryRelation($category);
        $this->setIsBatch(true);
    }

    /**
     * Deletes descendant categories.
     */
    public function deleteDescendantCategories()
    {
        if ($categories = $this->category->descendants) {
            $junctions = static::findAll([
                'category_id' => array_keys($categories),
                'entry_id' => $this->entry_id,
            ]);

            foreach ($junctions as $junction) {
                $category = $categories[$junction->category_id];
                if ($category->inheritNestedCategories()) {
                    $junction->populateCategoryRelation($category);
                    $junction->populateEntryRelation($this->entry);
                    $this->setIsBatch(true);
                    $junction->delete();
                }
            }
        }
    }

    /**
     * @return int
     */
    public function getMaxPosition(): int
    {
        return (int)static::find()->where(['category_id' => $this->category_id])->max('[[position]]');
    }

    /**
     * @return array
     */
    public function getTrailParents()
    {
        return [$this->entry, $this->category];
    }

    /**
     * @return string
     */
    public function getTrailModelName()
    {
        return Yii::t('cms', 'Entry–Category');
    }

    /**
     * @return string
     */
    public function getTrailModelType(): string
    {
        return Yii::t('skeleton', 'Relation');
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'entry_id' => Yii::t('cms', 'Entry'),
            'category_id' => Yii::t('cms', 'Category'),
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