<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Models\Queries\CategoryQuery;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Traits\SlugAttributeTrait;
use Hirtz\Cms\Models\Types\CategoryType;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Traits\NestedTreeTrait;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;
use Hirtz\Skeleton\Models\Traits\TranslatableAttributesTrait;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Yii;
use yii\db\ActiveQuery;

/**
 * @property int $position
 * @property string $name
 * @property string|null $slug
 * @property string|null $title
 * @property string|null $description
 * @property int $entry_count
 *
 * @property-read Entry[] $entries {@see static::getEntries()}
 * @property-read EntryCategory|null $entryCategory {@see static::getEntryCategory()}
 * @property-read EntryCategory[] $entryCategories {@see static::getEntryCategories()}
 * @property-read static[] $ancestors {@see static::getAncestors()}
 * @property-read static[] $descendants {@see static::getDescendants()}
 */
class Category extends ActiveRecord implements SearchableInterface
{
    use NestedTreeTrait;
    use SearchableTrait;
    use SlugAttributeTrait;
    use TranslatableAttributesTrait;

    final public const string AUTH_CATEGORY = 'category';

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getI18nRules([
                [
                    ['parent_id'],
                    'number',
                    'integerOnly' => true,
                ],
                [
                    ['parent_id'],
                    $this->validateParentId(...),
                    'skipOnEmpty' => false,
                ],
                [
                    ['name'],
                    'required',
                ],
                [
                    ['slug'],
                    'required',
                    'when' => $this->isSlugRequired(...),
                ],
                [
                    ['name', 'slug'],
                    'trim',
                ],
                [
                    ['name'],
                    'string',
                    'max' => 255,
                ],
                [
                    ['slug'],
                    'string',
                    'max' => $this->slugMaxLength,
                ],
                [
                    ['slug'],
                    $this->slugUniqueValidator,
                    'targetAttribute' => ['slug'],
                ],
            ]),
        ];
    }

    /**
     * @return list<CustomAttribute>
     */
    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            ...$this->getDefaultCustomAttributes(),
            ...parent::getCustomAttributes(),
        ];
    }

    /**
     * Resolved for every loaded record, with no relation populated, so nothing here may read one.
     *
     * @return list<CustomAttribute>
     */
    protected function getDefaultCustomAttributes(): array
    {
        return [
            TextCustomAttribute::make('title')
                ->label(Yii::t('cms', 'CATEGORY_TITLE_LABEL'))
                ->translatable($this->isTranslatableAttribute('title')),
            TextCustomAttribute::make('description')
                ->multiline()
                ->label(Yii::t('cms', 'CATEGORY_DESCRIPTION_LABEL'))
                ->translatable($this->isTranslatableAttribute('description')),
        ];
    }

    #[Override]
    public function beforeValidate(): bool
    {
        $this->ensureSlug();

        if (!static::getModule()->enableNestedCategories) {
            $this->parent_id = null;
        }

        return parent::beforeValidate();
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->updateTreeBeforeSave();
        return true;
    }

    /**
     * On parent id change all related entries (linked to this category as well as to the child categories)
     * need to be added to the new parent categories, if {@see \Hirtz\Cms\Module::$inheritNestedCategories}
     * is true. Previous parent {@see EntryCategory} relations will not be deleted.
     *
     * @param array<string, mixed> $changedAttributes
     */
    #[Override]
    public function afterSave($insert, $changedAttributes): void
    {
        if (!$insert && ($this->parent_id && array_key_exists('parent_id', $changedAttributes))) {
            $this->insertEntryCategoryAncestors();
        }

        CategoryCollection::invalidateCache();

        parent::afterSave($insert, $changedAttributes);
    }

    #[Override]
    public function beforeDelete(): bool
    {
        if ($isValid = parent::beforeDelete()) {
            $this->deleteNestedTreeItems();

            if ($this->entry_count) {
                $this->deleteEntryCategories();
            }
        }

        return $isValid;
    }

    #[Override]
    public function afterDelete(): void
    {
        $this->updateNestedTreeAfterDelete();

        parent::afterDelete();
    }

    /**
     * @return ActiveQuery<EntryCategory>
     */
    public function getEntryCategory(): ActiveQuery
    {
        return $this->hasOne(EntryCategory::class, ['category_id' => 'id'])
            ->inverseOf('category');
    }

    /**
     * @return EntryQuery<Entry>
     */
    public function getEntries(): EntryQuery
    {
        /** @var EntryQuery<Entry> $relation */
        $relation = $this->hasMany(Entry::class, ['id' => 'entry_id'])
            ->via('entryCategories');

        return $relation;
    }

    /**
     * @return ActiveQuery<EntryCategory>
     */
    public function getEntryCategories(): ActiveQuery
    {
        return $this->hasMany(EntryCategory::class, ['category_id' => 'id'])
            ->inverseOf('category');
    }

    public function recalculateEntryCount(): static
    {
        $this->entry_count = (int)$this->getEntryCategories()->count();
        return $this;
    }

    /**
     * Inserts related {@see EntryCategory} records to this records ancestor categories. This method is called after
     * the parent id was changed and can insert quite a lot of records. This might need to be overridden on applications
     * with MANY entry-category relations.
     */
    protected function insertEntryCategoryAncestors(): void
    {
        // If the category doesn't have `inheritNestedCategories` enabled, descendant categories need to be used.
        $categoryIds = $this->inheritNestedCategories() ? $this->id : array_keys(array_filter($this->getDescendants(), fn (self $category): bool => $category->inheritNestedCategories()));

        if ($categoryIds) {
            $entries = Entry::find()
                ->selectWith(
                    'entryCategory',
                    'INNER JOIN',
                    fn (ActiveQuery $query) => $query->onCondition([EntryCategory::tableName() . '.[[category_id]]' => $categoryIds]),
                )
                ->all();

            if ($entries) {
                // Refresh category ancestors once to prevent duplicate queries.
                $this->getAncestors(true);

                foreach ($entries as $entry) {
                    $entryCategory = $entry->entryCategory;

                    $entryCategory->populateCategoryRelation($this);
                    $entryCategory->insertCategoryAncestors();
                    $entryCategory->updateEntryCategoryIds();
                }
            }
        }
    }

    /**
     * Deletes all related entry categories before the {@see EntryCategory} records would be deleted by the database's
     * foreign key relation. This enables recalculating the related record as well as adding {@see Trail} records.
     * This might need to be overridden on applications with MANY entry-category relations.
     */
    protected function deleteEntryCategories(): void
    {
        $entryCategories = $this->getEntryCategories()
            ->with('entry')
            ->all();

        foreach ($entryCategories as $entryCategory) {
            $entryCategory->delete();
        }
    }

    #[Override]
    public static function find(): CategoryQuery
    {
        return Yii::createObject(CategoryQuery::class, [static::class]);
    }

    public function findSiblings(): CategoryQuery
    {
        return static::find()->where(['parent_id' => $this->parent_id]);
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function getTrailAttributes(): array
    {
        return array_values(array_diff(parent::getTrailAttributes(), [
            'lft',
            'rgt',
            'entry_count',
        ]));
    }

    public function getAdminType(): string
    {
        return $this->getTypeName() ?: Yii::t('cms', 'COMMON_CATEGORY');
    }

    public function getAdminRoute(): array
    {
        return $this->id ? ['/admin/cms/category/update', 'id' => $this->id] : ['/admin/cms/category/index'];
    }

    /**
     * `content` has no column of its own: it is indexed only where the project declares it as a custom attribute.
     */
    public function getSearchAttributes(): array
    {
        return ['name', 'title', 'description', 'content'];
    }

    public function getSearchWeight(): float
    {
        return 0.8;
    }

    protected function isSearchResultVisible(): bool
    {
        return WebUser::current()?->can(static::AUTH_CATEGORY) ?? false;
    }

    /**
     * @return array<int|string, mixed>|false
     */
    public function getRoute(): array|false
    {
        return array_filter(['/cms/site/index', 'category' => $this->getI18nAttribute('slug')]);
    }

    /**
     * @return array<string, int>
     */
    public function getEntriesOrderBy(): bool|array
    {
        return [EntryCategory::tableName() . '.[[position]]' => SORT_ASC];
    }

    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    public function allowsDescendants(): bool
    {
        return static::getModule()->inheritNestedCategories
            && ($this->getType()?->allowsDescendants() ?? true);
    }

    public function allowsEntries(): bool
    {
        return $this->getType()?->allowsEntries() ?? true;
    }

    /**
     * `parent_id` is an attribute of the category's own, so a type declares it through `hiddenFields()` rather than
     * through an `allow*()` of its own.
     */
    public function allowsParent(): bool
    {
        return static::getModule()->enableNestedCategories && $this->isAttributeVisible('parent_id');
    }

    public function inheritNestedCategories(): bool
    {
        return static::getModule()->inheritNestedCategories;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'name' => Yii::t('cms', 'CATEGORY_NAME_LABEL'),
            'parent_id' => Yii::t('cms', 'CATEGORY_PARENT_ID_LABEL'),
            'slug' => Yii::t('cms', 'CATEGORY_SLUG_LABEL'),
            'branchCount' => Yii::t('cms', 'CATEGORY_BRANCHCOUNT_LABEL'),
            'entry_count' => Yii::t('cms', 'CATEGORY_ENTRY_COUNT_LABEL'),
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Category';
    }

    #[Override]
    public static function getTypeClass(): string
    {
        return CategoryType::class;
    }

    #[Override]
    public function getType(): ?CategoryType
    {
        /** @var CategoryType|null */
        return static::findType(static::normalizeTypeValue($this->type ?? null));
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%category}}';
    }
}
