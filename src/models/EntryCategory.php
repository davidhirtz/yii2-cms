<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\traits\CategoryRelationTrait;
use davidhirtz\yii2\cms\models\traits\EntryRelationTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\behaviors\BlameableBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\log\ActiveRecordErrorLogger;
use davidhirtz\yii2\skeleton\models\interfaces\TrailModelInterface;
use davidhirtz\yii2\skeleton\models\traits\TrailModelTrait;
use davidhirtz\yii2\skeleton\models\traits\UpdatedByUserTrait;
use davidhirtz\yii2\skeleton\validators\RelationValidator;
use Override;
use Yii;

/**
 * Represents a relation between an entry and a category.
 *
 * @property int $entry_id
 * @property int $category_id
 * @property int $position
 * @property int|null $updated_by_user_id
 * @property DateTime $updated_at
 *
 * @mixin TrailBehavior
 */
class EntryCategory extends \davidhirtz\yii2\skeleton\db\ActiveRecord implements TrailModelInterface
{
    use CategoryRelationTrait;
    use EntryRelationTrait;
    use ModuleTrait;
    use TrailModelTrait;
    use UpdatedByUserTrait;

    public bool|null $shouldUpdateEntryAfterInsert = null;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'TrailBehavior' => TrailBehavior::class,
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['category_id'],
                RelationValidator::class,
                'required' => true,
            ], [
                ['entry_id'],
                RelationValidator::class,
                'required' => true,
            ], [
                ['category_id'],
                $this->validateCategoryId(...),
            ], [
                ['entry_id'],
                $this->validateEntryId(...),
            ], [
                ['entry_id'],
                'unique',
                'targetAttribute' => ['entry_id', 'category_id'],
            ],
        ];
    }

    public function validateCategoryId(): void
    {
        if (!$this->category->hasEntriesEnabled()) {
            $this->addInvalidAttributeError('category_id');
        }
    }

    public function validateEntryId(): void
    {
        if (!$this->entry->hasCategoriesEnabled()) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        $this->attachBehaviors([
            'BlameableBehavior' => BlameableBehavior::class,
            'TimestampBehavior' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => null,
            ],
        ]);

        $this->position ??= $this->getMaxPosition() + 1;
        $this->shouldUpdateEntryAfterInsert ??= !$this->getIsBatch();

        return parent::beforeSave($insert);
    }

    #[Override]
    public function afterSave($insert, $changedAttributes): void
    {
        if ($insert) {
            if ($this->shouldUpdateEntryAfterInsert) {
                $this->insertCategoryAncestors();
                $this->updateEntryCategoryIds();
            }

            $this->updateCategoryEntryCount();
        }

        static::getModule()->invalidatePageCache();

        parent::afterSave($insert, $changedAttributes);
    }

    #[Override]
    public function afterDelete(): void
    {
        if (!$this->getIsBatch()) {
            $this->deleteDescendantCategories();

            if (!$this->entry->isDeleted()) {
                $this->updateEntryCategoryIds();
            }
        }

        if (!$this->category->isDeleted()) {
            $this->updateCategoryEntryCount();
        }

        static::getModule()->invalidatePageCache();

        parent::afterDelete();
    }

    public function updateEntryCategoryIds(): bool|int
    {
        $this->entry->recalculateCategoryIds();

        if (!$this->entry->update()) {
            ActiveRecordErrorLogger::log($this->entry);
            return false;
        }

        return true;
    }

    public function updateCategoryEntryCount(): bool|int
    {
        $this->category->recalculateEntryCount();

        if (!$this->category->update()) {
            ActiveRecordErrorLogger::log($this->category);
            return false;
        }

        return true;
    }

    public function insertCategoryAncestors(): void
    {
        if ($categories = $this->category->getAncestors()) {
            foreach ($categories as $category) {
                if ($category->inheritNestedCategories() && $category->hasEntriesEnabled()) {
                    $junction = static::create();
                    $junction->populateInheritedRelation($this, $category);

                    if (!$junction->insert()) {
                        ActiveRecordErrorLogger::log($junction);
                    }
                }
            }
        }
    }

    protected function populateInheritedRelation(EntryCategory $entryCategory, ?Category $category): void
    {
        $this->populateEntryRelation($entryCategory->entry);
        $this->populateCategoryRelation($category);
        $this->setIsBatch(true);
    }

    public function deleteDescendantCategories(): void
    {
        if ($categories = $this->category->getDescendants()) {
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

    public function getMaxPosition(): int
    {
        return (int)static::find()->where(['category_id' => $this->category_id])->max('[[position]]');
    }

    #[Override]
    public function getTrailParents(): array
    {
        return [$this->entry, $this->category];
    }

    #[Override]
    public function getTrailModelName(): string
    {
        return Yii::t('cms', 'Entry–Category');
    }

    #[Override]
    public function getTrailModelType(): string
    {
        return Yii::t('skeleton', 'Relation');
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [...parent::attributeLabels(), 'entry_id' => Yii::t('cms', 'Entry'), 'category_id' => Yii::t('cms', 'Category'), 'updated_at' => Yii::t('cms', 'Added')];
    }

    #[Override]
    public function formName(): string
    {
        return 'EntryCategory';
    }

    #[Override]
    public static function tableName(): string
    {
        return static::getModule()->getTableName('entry_category');
    }
}
