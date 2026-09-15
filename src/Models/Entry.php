<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeValidator;
use Hirtz\Cms\Models\Actions\DeletePermalinkRedirects;
use Hirtz\Cms\Models\Actions\UpdateTenantEntryCount;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Queries\SectionQuery;
use Hirtz\Cms\Models\Types\EntryType;
use Hirtz\Cms\Models\Traits\PermalinkTrait;
use Hirtz\Cms\Models\Traits\SlugAttributeTrait;
use Hirtz\Cms\Models\Traits\VirtualSlugTrait;
use Hirtz\Cms\Module;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\Interfaces\AssetModelInterface;
use Hirtz\Media\Models\Traits\AssetModelTrait;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Traits\MaterializedTreeTrait;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;
use Hirtz\Cms\Validators\TenantIdValidator;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Models\Traits\TenantRelationTrait;
use Override;
use Yii;
use yii\db\ActiveQuery;

/**
 * @property int $tenant_id
 * @property int|null $parent_id
 * @property int|null $parent_status
 * @property list<int>|null $path
 * @property int|null|false $position
 * @property string $name
 * @property string|null $slug virtual, backed by {@see Permalink::$slug}
 * @property string|null $title
 * @property string|null $description
 * @property string $content
 * @property DateTime|null $publish_date
 * @property list<int>|null $category_ids
 * @property int $entry_count
 * @property int $section_count
 * @property int $asset_count
 *
 * @property-read EntryAsset[] $assets {@see static::getAssets()}
 * @property-read Permalink[] $permalinks {@see static::getPermalinks()}
 * @property-read EntryCategory $entryCategory {@see static::getEntryCategory()}
 * @property-read EntryCategory[] $entryCategories {@see static::getEntryCategories()}
 * @property-read SectionEntry|null $sectionEntry {@see static::getSectionEntry()}
 * @property-read Section[] $sections {@see static::getSections()}
 *
 * @method EntryQuery<static> findAncestors()
 * @method EntryQuery<static> findChildren()
 * @method EntryQuery<static> findDescendants()
 */
class Entry extends ActiveRecord implements AssetModelInterface, SearchableInterface
{
    use AssetModelTrait;
    use MaterializedTreeTrait;
    use SearchableTrait;
    use PermalinkTrait;
    use TenantRelationTrait;
    use SlugAttributeTrait;
    use VirtualSlugTrait;

    final public const string AUTH_ENTRY = 'entry';

    /**
     * @var array<string, mixed>|string
     */
    public array|string $dateTimeValidator = DateTimeValidator::class;
    public bool|null $shouldUpdateParentAfterSave = null;

    #[Override]
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
                    'when' => $this->isSlugRequired(...),
                ],
                [
                    ['name', 'slug', 'title', 'description'],
                    'trim',
                ],
                [
                    ['name', 'title', 'description'],
                    'string',
                    'max' => 255,
                ],
                [
                    ['parent_id'],
                    $this->validateParentId(...),
                    'skipOnEmpty' => false,
                ],
                [
                    ['tenant_id'],
                    TenantIdValidator::class,
                ],
                [
                    ['slug'],
                    'string',
                    'max' => $this->slugMaxLength,
                ],
                [
                    ['slug'],
                    $this->validateSlug(...),
                ],
                [
                    ['publish_date'],
                    ...(array)$this->dateTimeValidator,
                ],
            ]),
        ];
    }

    #[Override]
    public function beforeValidate(): bool
    {
        $this->ensureRequiredI18nAttributes();

        $this->ensureSlug();

        foreach ($this->getI18nAttributeNames('description') as $attributeName) {
            $description = preg_replace('/\R+/', ' ', (string)$this->$attributeName);
            $description = preg_replace('/\s+/', ' ', $description);
            $this->$attributeName = trim($description);
        }

        foreach ($this->getI18nAttributeNames('slug') as $attributeName) {
            $this->$attributeName = rtrim((string)$this->$attributeName, '/');
        }

        if (!$this->publish_date) {
            $this->publish_date = new DateTime();
        }

        return parent::beforeValidate();
    }

    #[Override]
    public function afterValidate(): void
    {
        if ($this->parent && $this->parent->getAttribute('tenant_id') !== $this->getAttribute('tenant_id')) {
            $this->addInvalidAttributeError('parent_id');
        }

        parent::afterValidate();
    }

    protected function validateParentId(): void
    {
        $this->parent_id = $this->parent_id && $this->hasParentEnabled() ? (int)$this->parent_id : null;

        if ($this->isAttributeChanged('parent_id')) {
            $parent = self::findOne($this->parent_id);

            if ($this->parent_id && !$parent) {
                $this->addInvalidAttributeError('parent_id');
                return;
            }

            if (!$this->getIsNewRecord()) {
                if (in_array($this->id, $parent?->getAncestorIds() ?? [], true)) {
                    $this->addInvalidAttributeError('parent_id');
                    return;
                }
            }

            $this->populateParentRelation($parent);
        }
    }

    /**
     * Validates the {@see Permalink} this slug would produce rather than the slug itself, so uniqueness, length and
     * the protected-path check all come from one place — including the tenant scope of the unique index.
     */
    protected function validateSlug(): void
    {
        if (!$this->hasPermalink()) {
            return;
        }

        foreach ($this->getPermalinkLanguages() as $language) {
            $attributeName = $this->getI18nAttributeName('slug', $language);

            if (!$this->getAttribute($attributeName) || $this->hasErrors($attributeName)) {
                continue;
            }

            $permalink = $this->buildPermalink($language);

            if (!$permalink->validate(['uri', 'slug'])) {
                foreach ($permalink->getFirstErrors() as $error) {
                    $this->addError($attributeName, $error);
                }
            }
        }
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        $this->shouldUpdateParentAfterSave ??= !$this->getIsBatch();

        if ($this->isAttributeChanged('parent_id')) {
            $this->parent_status = $this->parent
                ? min($this->parent->status, $this->parent->parent_status)
                : static::STATUS_ENABLED;

            $this->path = $this->parent
                ? [...$this->parent->path ?? [], $this->parent_id]
                : null;

            $this->position = null;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @param array<string, mixed> $changedAttributes
     */
    #[Override]
    public function afterSave($insert, $changedAttributes): void
    {
        // Validated by `validateSlug()`, so the records are written without a second validation round.
        $permalinks = $this->savePermalinks(false);
        $changedAttributes = [...$changedAttributes, ...$permalinks->getSlugChanges()];

        if (
            !$insert
            && $this->entry_count
            && ($permalinks->getChangedLanguages() || $this->isMaterializedTreeChanged($changedAttributes))
        ) {
            Yii::debug('Updating child entries ...', __METHOD__);

            foreach ($this->getChildren(true) as $entry) {
                $entry->populateParentRelation($this);
                $entry->parent_status = min($this->status, $this->parent_status);
                $entry->path = [...$this->path ?? [], $this->id];
                $entry->update();
            }
        }

        if ($this->shouldUpdateParentAfterSave && array_key_exists('parent_id', $changedAttributes)) {
            $allRelatedAncestorIds = array_unique(array_filter([
                ...$changedAttributes['path'] ?? [],
                ...$this->getAncestorIds(),
            ]));
            $allRelatedAncestorIds = array_map(intval(...), $allRelatedAncestorIds);

            if ($this->parent) {
                $allRelatedAncestorIds = array_diff($allRelatedAncestorIds, [$this->parent_id]);
                $this->parent->recalculateEntryCount()->update();
            }

            if ($allRelatedAncestorIds) {
                foreach (static::findAll($allRelatedAncestorIds) as $ancestor) {
                    $ancestor->recalculateEntryCount()->update();
                }
            }
        }

        $previousTenantId = (int)($changedAttributes['tenant_id'] ?? 0);

        if ($previousTenantId) {
            $this->updateDescendantTenants();
            (new UpdateTenantEntryCount($previousTenantId))->update();
        }

        if ($insert || $previousTenantId) {
            (new UpdateTenantEntryCount($this->tenant_id))->update();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    #[Override]
    public function beforeDelete(): bool
    {
        if ($isValid = parent::beforeDelete()) {
            (new DeletePermalinkRedirects($this))->delete();

            if ($this->asset_count) {
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
                foreach ($this->getChildren() as $entry) {
                    $entry->setIsBatch($this->getIsBatch());
                    $entry->delete();
                }
            }

            if (static::getModule()->enableSectionEntries) {
                Yii::debug('Loading affected sections ...', __METHOD__);

                $sectionIds = SectionEntry::find()
                    ->select('section_id')
                    ->where(['entry_id' => $this->id])
                    ->column();

                if ($sectionIds) {
                    $this->on(static::EVENT_AFTER_DELETE, function () use ($sectionIds): void {
                        $sections = Section::find()
                            ->where(['id' => $sectionIds])
                            ->all();

                        foreach ($sections as $section) {
                            $section->recalculateEntryCount()->update();
                        }
                    });
                }
            }
        }

        return $isValid;
    }

    #[Override]
    public function afterDelete(): void
    {
        (new UpdateTenantEntryCount($this->tenant_id))->update();

        if (!$this->getIsBatch()) {
            if ($this->parent_id) {
                foreach ($this->getAncestors() as $ancestor) {
                    $ancestor->recalculateEntryCount()->update();
                }
            }
        }

        parent::afterDelete();
    }

    /**
     * @return ActiveQuery<EntryCategory>
     */
    public function getEntryCategory(): ActiveQuery
    {
        return $this->hasOne(EntryCategory::class, ['entry_id' => 'id'])
            ->inverseOf('entry');
    }

    /**
     * @return ActiveQuery<EntryCategory>
     */
    public function getEntryCategories(): ActiveQuery
    {
        return $this->hasMany(EntryCategory::class, ['entry_id' => 'id'])
            ->inverseOf('entry');
    }

    /**
     * @return ActiveQuery<SectionEntry>
     */
    public function getSectionEntry(): ActiveQuery
    {
        return $this->hasOne(SectionEntry::class, ['entry_id' => 'id'])
            ->inverseOf('entry');
    }

    /**
     * @return SectionQuery<Section>
     */
    public function getSections(): SectionQuery
    {
        /** @var SectionQuery<Section> $relation */
        $relation = $this->hasMany(Section::class, ['entry_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');

        return $relation;
    }

    /**
     * @return EntryQuery<static>
     */
    #[Override]
    public static function find(): EntryQuery
    {
        return Yii::createObject(EntryQuery::class, [static::class]);
    }

    /**
     * @return EntryQuery<static>
     */
    #[Override]
    public function findSiblings(): EntryQuery
    {
        return static::find()->where([
            'parent_id' => $this->parent_id,
            'tenant_id' => $this->tenant_id,
        ]);
    }

    /**
     * The permalinks are updated by hand: they carry a denormalized copy of the entry's tenant, and nothing
     * re-saves a descendant after {@see static::afterSave()} rewrote its URL.
     */
    protected function updateDescendantTenants(): void
    {
        if (!$this->entry_count) {
            return;
        }

        Yii::debug('Updating descendants tenant ...', __METHOD__);

        $descendantIds = $this->findDescendants()
            ->select('id')
            ->column();

        if (!$descendantIds) {
            return;
        }

        static::updateAll([
            'tenant_id' => $this->tenant_id,
            'updated_by_user_id' => $this->updated_by_user_id,
            'updated_at' => $this->updated_at,
        ], ['id' => $descendantIds]);

        Permalink::updateAll(['tenant_id' => $this->tenant_id], ['entry_id' => $descendantIds]);
    }

    protected function ensureRequiredI18nAttributes(): void
    {
        foreach ($this->i18nAttributes as $attribute) {
            if (!$this->isAttributeRequired($attribute)) {
                continue;
            }

            foreach ($this->getI18nAttributeNames($attribute) as $i18nAttributeName) {
                if (!$this->$i18nAttributeName) {
                    $this->$i18nAttributeName = $this->$attribute;
                }
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
        $categoryIds = $this->getEntryCategories()
            ->select(['category_id'])
            ->column();

        $this->category_ids = $categoryIds ? array_map(intval(...), $categoryIds) : null;

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

    #[Override]
    public function getAdminRoute(): false|array
    {
        return $this->id ? ['/admin/cms/entry/update', 'id' => $this->id] : false;
    }

    /**
     * `content` has no column of its own: it is indexed only where the project declares it as a custom attribute.
     */
    public function getSearchAttributes(): array
    {
        return ['name', 'title', 'description', 'content'];
    }

    protected function isSearchResultVisible(): bool
    {
        return Yii::$app->has('user') && Yii::$app->getUser()->can(static::AUTH_ENTRY);
    }

    /**
     * @return int[]
     */
    public function getCategoryIds(): array
    {
        return array_map(intval(...), $this->category_ids ?? []);
    }

    public function getCategoryCount(): int
    {
        return $this->category_ids ? count($this->category_ids) : 0;
    }

    /**
     * @return array<string, int>
     */
    public function getDescendantsOrderBy(): array
    {
        return ['position' => SORT_ASC];
    }

    public function composeFormattedSlug(?string $language = null): string
    {
        $path = $this->parent?->getFormattedSlug($language) ?? '';
        $slug = $path . '/' . $this->getI18nAttribute('slug', $language);

        return substr(trim($slug, '/'), 0, 255);
    }

    /**
     * @return array<int|string, mixed>|false
     */
    #[Override]
    public function getRoute(): false|array
    {
        if ($this->isIndex()) {
            return ['/cms/site/index', ...$this->getTenantRouteParams()];
        }

        $slug = $this->hasRoute() ? $this->getFormattedSlug() : null;

        return $slug ? ['/cms/site/view', 'slug' => $slug, ...$this->getTenantRouteParams()] : false;
    }

    /**
     * @return array<string, Tenant|null>
     */
    public function getTenantRouteParams(): array
    {
        return ['tenant' => TenantCollection::getAll()[$this->tenant_id] ?? null];
    }

    /**
     * @return Asset[]
     */
    public function getVisibleAssets(): array
    {
        if (!$this->hasAssetsEnabled() || !$this->isAttributeVisible(self::FIELD_ASSETS)) {
            return [];
        }

        return array_filter($this->assets, fn (Asset $asset): bool => $asset->type !== $asset::TYPE_META_IMAGE);
    }

    public function getStatusIcon(): string
    {
        if ($this->isIndex() && $this->isEnabled()) {
            return 'home';
        }

        return parent::getStatusIcon();
    }

    public function getStatusName(): string
    {
        $status = parent::getStatusName();

        if ($this->hasInvalidParentStatus()) {
            $parentStatus = static::findStatus($this->parent_status)?->getName() ?? '';
            $status .= ' (' . $this->getAttributeLabel('parent_status') . ": $parentStatus)";
        }

        return $status;
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function getTrailAttributes(): array
    {
        return array_diff(parent::getTrailAttributes(), [
            'path',
            'parent_status',
            'category_ids',
            'entry_count',
            'section_count',
            'updated_at',
            'created_at',
        ]);
    }

    public function getAdminType(): string
    {
        return $this->getTypeName() ?: Yii::t('cms', 'COMMON_ENTRY');
    }

    #[Override]
    public static function getTypeClass(): string
    {
        return EntryType::class;
    }

    #[Override]
    public static function findType(?int $type): ?EntryType
    {
        /** @var EntryType|null */
        return parent::findType($type);
    }

    #[Override]
    public function getType(): ?EntryType
    {
        return static::findType(static::normalizeTypeValue($this->type ?? null));
    }

    public function getViewFile(): ?string
    {
        return $this->getType()?->getViewFile();
    }

    /**
     * @param array<string, mixed> $changedAttributes
     */
    protected function isMaterializedTreeChanged(?array $changedAttributes = null): bool
    {
        if (!$this->entry_count) {
            return false;
        }

        $changedAttributes ??= $this->getDirtyAttributes();

        foreach (['status', 'parent_status', 'path'] as $key) {
            if (array_key_exists($key, $changedAttributes)) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function isTransactional($operation): bool
    {
        return parent::isTransactional($operation)
            || (($this->entry_count > 0 || $this->isMaterializedTreeChanged()) && !static::getDb()->getTransaction());
    }

    public function getAssetClass(): string
    {
        return EntryAsset::class;
    }

    public function hasAssetsEnabled(): bool
    {
        return static::getModule()->enableEntryAssets;
    }

    public function hasCategoriesEnabled(): bool
    {
        return static::getModule()->enableCategories;
    }

    public function hasInvalidParentStatus(): bool
    {
        return $this->parent_status !== static::STATUS_ENABLED;
    }

    public function hasDescendantsEnabled(): bool
    {
        return static::getModule()->enableNestedEntries && !$this->isIndex();
    }

    public function hasParentEnabled(): bool
    {
        return static::getModule()->enableNestedEntries
            && $this->isAttributeVisible('parent_id')
            && !$this->isIndex();
    }

    public function hasSectionsEnabled(): bool
    {
        return static::getModule()->enableSections;
    }

    public function hasPermalink(): bool
    {
        return true;
    }

    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    public function isSlugRequired(): bool
    {
        return true;
    }

    public function hasRoute(): bool
    {
        return $this->section_count > 0 || $this->entry_count > 0;
    }

    public function isIndex(): bool
    {
        return ($slug = static::getModule()->entryIndexSlug)
            && $this->getI18nAttribute('slug') === $slug
            && $this->parent_id === null;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'name' => Yii::t('cms', 'MODEL_NAME_LABEL'),
            'tenant_id' => Yii::t('cms', 'ENTRY_TENANT_ID_LABEL'),
            'parent_id' => Yii::t('cms', 'ENTRY_PARENT_ID_LABEL'),
            'parent_status' => Yii::t('cms', 'ENTRY_PARENT_STATUS_LABEL'),
            'slug' => Yii::t('cms', 'ENTRY_SLUG_LABEL'),
            'title' => Yii::t('cms', 'ENTRY_TITLE_LABEL'),
            'description' => Yii::t('cms', 'ENTRY_DESCRIPTION_LABEL'),
            'publish_date' => Yii::t('cms', 'ENTRY_PUBLISH_DATE_LABEL'),
            'entry_count' => Yii::t('cms', 'ENTRY_ENTRY_COUNT_LABEL'),
            'section_count' => Yii::t('cms', 'ENTRY_SECTION_COUNT_LABEL'),
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Entry';
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%entry}}';
    }
}
