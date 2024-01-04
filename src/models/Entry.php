<?php

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\models\traits\SlugAttributeTrait;
use davidhirtz\yii2\cms\models\validators\ParentIdValidator;
use davidhirtz\yii2\cms\Module;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeValidator;
use davidhirtz\yii2\media\models\interfaces\AssetParentInterface;
use davidhirtz\yii2\media\models\traits\AssetParentTrait;
use davidhirtz\yii2\skeleton\behaviors\RedirectBehavior;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\models\traits\MaterializedTreeTrait;
use Yii;
use yii\db\ActiveQuery;

/**
 * @property int|null $parent_id
 * @property string|null $path
 * @property string $parent_slug
 * @property int $position
 * @property string $name
 * @property string|null $slug
 * @property string $title
 * @property string|null $description
 * @property string $content
 * @property DateTime|null $publish_date
 * @property string|null $category_ids
 * @property int $entry_count
 * @property int $section_count
 * @property int $asset_count
 *
 * @property-read Asset[] $assets {@see static::getAssets()}
 * @property-read EntryCategory $entryCategory {@see static::getEntryCategory()}
 * @property-read EntryCategory[] $entryCategories {@see static::getEntryCategories()}
 * @property-read SectionEntry|null $sectionEntry {@see static::getSectionEntry()}
 * @property-read Section[] $sections {@see static::getSections()}
 */
class Entry extends ActiveRecord implements AssetParentInterface
{
    use AssetParentTrait;
    use MaterializedTreeTrait;
    use SlugAttributeTrait;

    public string|false $contentType = false;
    public array|string $dateTimeValidator = DateTimeValidator::class;
    public array|string|null $slugTargetAttribute = ['slug', 'parent_slug'];
    public bool|null $shouldUpdateParentAfterSave = null;

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'RedirectBehavior' => RedirectBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getI18nRules([
                [
                    ['name'],
                    'required',
                ],
                [
                    ['slug'],
                    'required',
                    'when' => fn (): bool => $this->isSlugRequired()
                ],
                [
                    ['name', 'slug', 'title', 'description', 'content'],
                    'trim',
                ],
                [
                    ['name', 'title', 'description'],
                    'string',
                    'max' => 250,
                ],
                [
                    ['parent_id'],
                    ParentIdValidator::class,
                ],
                [
                    ['slug'],
                    'string',
                    'max' => $this->slugMaxLength,
                ],
                [
                    ['slug'],
                    $this->slugUniqueValidator,
                    'targetAttribute' => $this->slugTargetAttribute,
                ],
                [
                    ['publish_date'],
                    ...(array)$this->dateTimeValidator
                ],
            ]),
        ];
    }

    public function beforeValidate(): bool
    {
        $this->ensureRequiredI18nAttributes();
        $this->ensureSlug();

        return parent::beforeValidate();
    }

    public function afterValidate(): void
    {
        if ($this->isAttributeChanged('parent_id')) {
            $this->setAttribute('path', $this->parent
                ? ArrayHelper::createCacheString(ArrayHelper::cacheStringToArray($this->parent->path, $this->parent_id))
                : null);
        }

        parent::afterValidate();
    }

    public function beforeSave($insert): bool
    {
        if (!$this->slug) {
            $this->slug = null;
        }

        if (!$this->publish_date) {
            $this->publish_date = new DateTime();
        }

        if (!$this->description) {
            $this->description = null;
        }

        $this->shouldUpdateParentAfterSave ??= !$this->getIsBatch();

        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes): void
    {
        if (!$insert && $this->entry_count) {
            foreach ($this->getI18nAttributesNames(['path', 'slug', 'parent_slug']) as $key) {
                if (array_key_exists($key, $changedAttributes)) {
                    foreach ($this->getChildren(true) as $entry) {
                        foreach ($entry->getI18nAttributeNames('parent_slug') as $language => $attributeName) {
                            $entry->{$attributeName} = $this->getFormattedSlug($language);
                        }

                        $entry->path = ArrayHelper::createCacheString(ArrayHelper::cacheStringToArray($this->path, $this->id));
                        $entry->update();
                    }

                    break;
                }
            }
        }

        if ($this->shouldUpdateParentAfterSave && array_key_exists('parent_id', $changedAttributes)) {
            $ancestorIds = ArrayHelper::cacheStringToArray($changedAttributes['path'] ?? '', $this->getAncestorIds());

            if ($ancestorIds) {
                foreach (static::findAll($ancestorIds) as $ancestor) {
                    $ancestor->recalculateEntryCount()->update();
                }
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public function beforeDelete(): bool
    {
        if ($isValid = parent::beforeDelete()) {
            if ($this->asset_count || $this->section_count) {
                foreach ($this->assets as $asset) {
                    $asset->setIsBatch($this->getIsBatch());
                    $asset->delete();
                }
            }

            if ($this->section_count) {
                foreach ($this->sections as $section) {
                    $section->setIsBatch($this->getIsBatch());
                    $section->delete();
                }
            }

            if ($this->category_ids) {
                foreach ($this->entryCategories as $entryCategory) {
                    $entryCategory->setIsBatch($this->getIsBatch());
                    $entryCategory->delete();
                }
            }

            if ($this->entry_count) {
                foreach ($this->children as $entry) {
                    $entry->setIsBatch($this->getIsBatch());
                    $entry->delete();
                }
            }
        }

        return $isValid;
    }

    public function afterDelete(): void
    {
        if (!$this->getIsBatch()) {
            if ($this->parent_id) {
                foreach ($this->ancestors as $ancestor) {
                    $ancestor->recalculateEntryCount()->update();
                }
            }
        }
        parent::afterDelete();
    }

    public function getAssets(): AssetQuery
    {
        /** @var AssetQuery $relation */
        $relation = $this->hasMany(Asset::class, ['entry_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');

        return $relation;
    }

    public function getEntryCategory(): ActiveQuery
    {
        return $this->hasOne(EntryCategory::class, ['entry_id' => 'id'])
            ->inverseOf('entry');
    }

    public function getEntryCategories(): ActiveQuery
    {
        return $this->hasMany(EntryCategory::class, ['entry_id' => 'id'])
            ->inverseOf('entry');
    }

    public function getSectionEntry(): ActiveQuery
    {
        return $this->hasOne(SectionEntry::class, ['entry_id' => 'id'])
            ->inverseOf('entry');
    }

    public function getSections(): SectionQuery
    {
        /** @var SectionQuery $relation */
        $relation = $this->hasMany(Section::class, ['entry_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');

        return $relation;
    }

    public static function find(): EntryQuery
    {
        return Yii::createObject(EntryQuery::class, [static::class]);
    }

    public function findSiblings(): EntryQuery
    {
        return static::find()->where(['parent_id' => $this->parent_id]);
    }

    public function getSitemapQuery(): EntryQuery
    {
        $query = static::find()
            ->selectSitemapAttributes()
            ->orderBy(['id' => SORT_ASC]);

        if (static::getModule()->enableImageSitemaps) {
            $query->withSitemapAssets();
        }

        return $query;
    }

    protected function ensureRequiredI18nAttributes(): void
    {
        foreach ($this->i18nAttributes as $attribute) {
            if ($this->isAttributeRequired($attribute)) {
                foreach ($this->getI18nAttributeNames($attribute) as $i18nAttributeName) {
                    if (!$this->$i18nAttributeName) {
                        $this->$i18nAttributeName = $this->$attribute;
                    }
                }
            }
        }
    }

    /**
     * @param Asset[]|null $assets
     */
    public function populateAssetRelations(?array $assets = null): void
    {
        $assets ??= $this->assets;
        $relations = [];

        if ($assets) {
            foreach ($assets as $asset) {
                if ($asset->entry_id == $this->id && !$asset->section_id) {
                    $asset->populateRelation('entry', $this);
                    $relations[$asset->id] = $asset;
                }
            }
        }

        $this->populateRelation('assets', $relations);

        if ($this->hasSectionsEnabled() && $this->isRelationPopulated('sections')) {
            foreach ($this->sections as $section) {
                $section->populateAssetRelations($assets);
            }
        }
    }

    public function populateParentRelation(?Entry $parent): void
    {
        $this->populateRelation('parent', $parent);
        $this->parent_id = $parent?->id;
    }

    /**
     * @param Section[]|null $sections
     */
    public function populateSectionRelations(?array $sections = null): void
    {
        $this->populateRelation('sections', $sections);
    }

    public function recalculateCategoryIds(): static
    {
        $this->category_ids = ArrayHelper::createCacheString($this->getEntryCategories()
            ->select(['category_id'])
            ->column());

        return $this;
    }

    public function recalculateEntryCount(): static
    {
        $this->entry_count = $this->findDescendants()->count();
        return $this;
    }

    public function recalculateSectionCount(): static
    {
        $this->section_count = (int)$this->getSections()->count();
        return $this;
    }

    public function getAdminRoute(): false|array
    {
        return $this->id ? ['/admin/entry/update', 'id' => $this->id] : false;
    }

    public function getCategoryIds(): array
    {
        return ArrayHelper::cacheStringToArray($this->category_ids);
    }

    public function getCategoryCount(): int
    {
        return count($this->getCategoryIds());
    }

    public function getDescendantsOrder(): array
    {
        return ['position' => SORT_ASC];
    }

    public function getFormattedSlug(?string $language = null): string
    {
        $slug = $this->getI18nAttribute('parent_slug', $language) . '/' . $this->getI18nAttribute('slug', $language);
        return substr(trim($slug, '/'), 0, 255);
    }

    public function getRoute(): false|array
    {
        if ($this->isIndex()) {
            return ['/cms/site/index'];
        }

        return $this->hasRoute() ? array_filter(['/cms/site/view', 'slug' => $this->getFormattedSlug()]) : false;
    }

    /**
     * Extends the default XML sitemap url by image URLs if related assets were found. This is automatically the
     * case if {@see Module::$enableImageSitemaps} is set to `true`.
     */
    public function getSitemapUrl(?string $language = null): array|false
    {
        if ($url = parent::getSitemapUrl($language)) {
            /** @var Asset[] $assets */
            $assets = $this->getRelatedRecords()['assets'] ?? [];

            foreach ($assets as $asset) {
                if ($imageUrl = $asset->getSitemapUrl($language)) {
                    $url['images'][] = $imageUrl;
                }
            }
        }

        return $url;
    }

    public function getStatusIcon(): string
    {
        return !$this->isIndex() || !$this->isEnabled()
            ? parent::getStatusIcon()
            : 'home';
    }

    public function getTrailAttributes(): array
    {
        return array_diff(parent::getTrailAttributes(), [
            'path',
            'parent_slug',
            'category_ids',
            'entry_count',
            'section_count',
            'updated_at',
            'created_at',
        ]);
    }

    public function getTrailModelName(): string
    {
        if ($this->id) {
            return $this->getI18nAttribute('name') ?: Yii::t('skeleton', '{model} #{id}', [
                'model' => $this->getTrailModelType(),
                'id' => $this->id,
            ]);
        }

        return $this->getTrailModelType();
    }

    public function getTrailModelType(): string
    {
        return $this->getTypeName() ?: Yii::t('cms', 'Entry');
    }

    public function getViewFile(): ?string
    {
        return $this->getTypeOptions()['viewFile'] ?? null;
    }

    public function hasAssetsEnabled(): bool
    {
        return static::getModule()->enableEntryAssets;
    }

    public function hasCategoriesEnabled(): bool
    {
        return static::getModule()->enableCategories;
    }

    public function hasDescendantsEnabled(): bool
    {
        return static::getModule()->enableNestedEntries && !$this->isIndex();
    }

    public function hasParentEnabled(): bool
    {
        return static::getModule()->enableNestedEntries && !$this->isIndex();
    }

    public function hasSectionsEnabled(): bool
    {
        return static::getModule()->enableSections;
    }

    public function hasRoute(): bool
    {
        return $this->section_count > 0
            || (in_array('parent_slug', $this->slugTargetAttribute) && $this->entry_count > 0);
    }

    public function isIndex(): bool
    {
        return ($slug = static::getModule()->entryIndexSlug) && $this->slug == $slug;
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'parent_id' => Yii::t('cms', 'Parent entry'),
            'slug' => Yii::t('cms', 'Url'),
            'title' => Yii::t('cms', 'Meta title'),
            'description' => Yii::t('cms', 'Meta description'),
            'publish_date' => Yii::t('cms', 'Published'),
            'entry_count' => Yii::t('cms', 'Subentries'),
            'section_count' => Yii::t('cms', 'Sections')
        ];
    }

    public function formName(): string
    {
        return 'Entry';
    }

    public static function tableName(): string
    {
        return static::getModule()->getTableName('entry');
    }
}
