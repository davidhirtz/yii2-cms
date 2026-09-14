<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Closure;
use Hirtz\Cms\Models\CustomAttributes\SlugCustomAttribute;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Queries\SectionQuery;
use Hirtz\Cms\Models\Traits\EntryRelationTrait;
use Hirtz\Media\Models\Interfaces\AssetModelInterface;
use Hirtz\Media\Models\Traits\AssetModelTrait;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;
use Hirtz\Skeleton\Models\Traits\TranslatableAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Search\SearchText;
use yii\db\ActiveQuery;
use Hirtz\Skeleton\Validators\RelationValidator;
use Override;
use Yii;

/**
 * @property int $entry_id
 * @property int $position
 * @property string|null $name
 * @property string|null $slug
 * @property string|null $content
 * @property int $asset_count
 * @property int $entry_count
 *
 * @property-read SectionAsset[] $assets {@see static::getAssets()}
 * @property-read Entry[] $entries {@see static::getEntries()}
 * @property-read SectionEntry $sectionEntry {@see static::getSectionEntry()}
 * @property-read SectionEntry[] $sectionEntries {@see static::getSectionEntries()}
 */
class Section extends ActiveRecord implements AssetModelInterface, SearchableInterface
{
    use AssetModelTrait;
    use EntryRelationTrait;
    use SearchableTrait;
    use TranslatableAttributesTrait;

    /**
     * The marker that hides the linked entries panel, listed among a type's hidden fields.
     */
    final public const string FIELD_ENTRIES = '#entries';

    final public const int SLUG_MAX_LENGTH = 100;

    public bool|null $shouldUpdateEntryAfterSave = null;

    private ?array $trailParents = null;

    /**
     * @var Closure(): list<SectionSet>|list<SectionSet>|null what the container configured, see
     * {@see static::setSectionSets()}
     */
    private Closure|array|null $configuredSectionSets = null;

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getI18nRules([
            [
                ['entry_id'],
                RelationValidator::class,
                'required' => true,
            ],
            [
                ['entry_id'],
                $this->validateEntryId(...),
            ],
            [
                ['slug'],
                $this->validateSlug(...),
            ],
        ])];
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
     * @return list<CustomAttribute>
     */
    protected function getDefaultCustomAttributes(): array
    {
        return [
            TextCustomAttribute::make('name')
                ->label(Yii::t('cms', 'MODEL_NAME_LABEL'))
                ->translatable($this->isTranslatableAttribute('name')),
            HtmlCustomAttribute::make('content')
                ->label(Yii::t('cms', 'MODEL_CONTENT_LABEL'))
                ->translatable($this->isTranslatableAttribute('content')),
            SlugCustomAttribute::make('slug')
                ->max(self::SLUG_MAX_LENGTH)
                ->label(Yii::t('cms', 'SECTION_SLUG_LABEL'))
                ->translatable($this->isTranslatableAttribute('slug')),
        ];
    }

    #[Override]
    public function safeAttributes(): array
    {
        return array_diff(parent::safeAttributes(), ['entry_id']);
    }

    /**
     * The slug is the section's HTML id, so it only has to be unique among the sections of its entry.
     */
    protected function validateSlug(string $attribute): void
    {
        if ($this->$attribute && in_array($this->$attribute, $this->findSiblingSlugs($attribute), true)) {
            $this->addError($attribute, Yii::t('yii', '{attribute} "{value}" has already been taken.', [
                'attribute' => $this->getAttributeLabel($attribute),
                'value' => $this->$attribute,
            ]));
        }
    }

    public function generateUniqueSlug(): void
    {
        foreach ($this->getI18nAttributeNames('slug') as $name) {
            $slug = $this->getAttribute($name);

            if (!$slug) {
                continue;
            }

            $slugs = $this->findSiblingSlugs($name);

            for ($i = 1; $i < 100 && in_array($this->getAttribute($name), $slugs, true); $i++) {
                $length = self::SLUG_MAX_LENGTH - 1 - (int)ceil($i / 10);
                $this->setAttribute($name, mb_substr((string)$slug, 0, $length) . '-' . $i);
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function findSiblingSlugs(string $attribute): array
    {
        $slugs = [];

        foreach ($this->findSiblings()->all() as $section) {
            if ($section->id !== $this->id && ($slug = $section->getAttribute($attribute))) {
                $slugs[] = (string)$slug;
            }
        }

        return $slugs;
    }

    public function validateEntryId(): void
    {
        if (!$this->entry->hasSectionsEnabled()) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        $this->shouldUpdateEntryAfterSave ??= !$this->getIsBatch();

        // Handle section move / clone, inserts will be handled by parent implementation
        if (!$insert && $this->isAttributeChanged('entry_id')) {
            $this->position = $this->getMaxPosition() + 1;
        }

        return parent::beforeSave($insert);
    }

    #[Override]
    public function afterSave($insert, $changedAttributes): void
    {
        if ($this->shouldUpdateEntryAfterSave) {
            if (array_key_exists('entry_id', $changedAttributes)) {
                $this->updateOldEntryRelation($changedAttributes['entry_id'] ?? null);

                $this->entry->recalculateSectionCount();
            }

            if ($changedAttributes) {
                $this->entry->updated_at = $this->updated_at;
            }

            $this->entry->update();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    #[Override]
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Unconditional: an entry deletion no longer sweeps the section assets through a shared column.
        if ($this->asset_count) {
            foreach ($this->assets as $asset) {
                $asset->setIsBatch($this->getIsBatch());
                $asset->delete();
            }
        }

        return true;
    }

    #[Override]
    public function afterDelete(): void
    {
        if (!$this->entry->isDeleted()) {
            $this->entry->recalculateSectionCount()->update();
        }

        parent::afterDelete();
    }

    public function getEntries(): EntryQuery
    {
        /** @var EntryQuery $relation */
        $relation = $this->hasMany(Entry::class, ['id' => 'entry_id'])
            ->via('sectionEntries');

        return $relation;
    }

    /**
     * @return ActiveQuery<SectionEntry>
     */
    public function getSectionEntry(): ActiveQuery
    {
        return $this->hasOne(SectionEntry::class, ['section_id' => 'id'])
            ->inverseOf('section');
    }

    /**
     * @return ActiveQuery<SectionEntry>
     */
    public function getSectionEntries(): ActiveQuery
    {
        return $this->hasMany(SectionEntry::class, ['section_id' => 'id'])
            ->inverseOf('section');
    }

    public function findSiblings(): SectionQuery
    {
        return static::find()->where(['entry_id' => $this->entry_id]);
    }

    #[Override]
    public static function find(): SectionQuery
    {
        return Yii::createObject(SectionQuery::class, [static::class]);
    }

    public function recalculateEntryCount(): static
    {
        $this->entry_count = $this->getSectionEntries()->count();
        return $this;
    }

    /**
     * Updates the old entry relation after the section was moved to another entry. Override this method if the old
     * entry should be further manipulated after the section's entry was changed.
     */
    protected function updateOldEntryRelation(?int $entryId): void
    {
        $entry = Entry::findOne($entryId);

        if ($entry) {
            $entry->recalculateSectionCount()->update();
            $this->trailParents = [$entry, $this->entry];
        }
    }

    public function getTrailParents(): array
    {
        return $this->trailParents ?? [$this->entry];
    }

    public function getAdminType(): string
    {
        return Yii::t('cms', 'COMMON_SECTION');
    }

    public function getNameColumnContent(): ?string
    {
        $nameColumn = $this->getType()?->getNameColumn();
        return $nameColumn instanceof Closure ? $nameColumn($this) : $nameColumn;
    }

    public function getAdminRoute(): array|false
    {
        return $this->id ? ['/admin/cms/section/update', 'id' => $this->id] : false;
    }

    public function getSearchAttributes(): array
    {
        return ['name', 'content'];
    }

    public function getSearchWeight(): float
    {
        return 0.7;
    }

    public function getSearchTenantId(): ?int
    {
        return $this->entry?->tenant_id;
    }

    /**
     * @return SectionQuery<static>
     */
    public static function findSearchable(): SectionQuery
    {
        return static::find()->with('entry');
    }

    /**
     * Not the entry's name: with the title boost, that would rank every section of an entry beside the entry itself.
     */
    public function getSearchTitle(?string $language = null): string
    {
        $name = (string)$this->getSearchAttributeValue('name', $language);
        return mb_substr(SearchText::normalize($name !== '' ? $name : $this->getTypeName()), 0, 255);
    }

    protected function getSearchResultTitle(): string
    {
        return implode(' › ', array_filter([$this->entry?->getSearchTitle(), $this->getSearchTitle()]));
    }

    protected function isSearchResultVisible(): bool
    {
        return Yii::$app->has('user') && Yii::$app->getUser()->can(Entry::AUTH_ENTRY);
    }

    public function getEntriesOrderBy(): ?array
    {
        return $this->getType()?->getEntriesOrderBy();
    }

    /**
     * @return list<int>|null
     */
    public function getEntriesTypes(): ?array
    {
        return $this->getType()?->getEntriesTypes();
    }

    public function getHtmlId(): ?string
    {
        return $this->getI18nAttribute('slug') ?: ('section-' . $this->id);
    }

    public function getRoute(): false|array
    {
        return ($route = $this->entry->getRoute()) ? [...$route, '#' => $this->getHtmlId()] : false;
    }

    #[Override]
    public static function getTypeClass(): string
    {
        return SectionType::class;
    }

    #[Override]
    public function getType(): ?SectionType
    {
        /** @var SectionType|null */
        return static::findType(static::normalizeTypeValue($this->type ?? null));
    }

    /**
     * A closure for the same reason {@see TypeAttributeTrait::setTypes()} takes one: a set's name is a
     * {@see Yii::t()} result, and a literal in a configuration file resolves before the application has an `i18n`
     * component.
     *
     * @param Closure(): list<SectionSet>|list<SectionSet> $sectionSets
     */
    public function setSectionSets(Closure|array $sectionSets): void
    {
        $this->configuredSectionSets = $sectionSets;
    }

    /**
     * Override this method to declare section sets — an override owns them, and the configured list is ignored.
     *
     * @return list<SectionSet>
     */
    public function getSectionSets(): array
    {
        if ($this->configuredSectionSets instanceof Closure) {
            return ($this->configuredSectionSets)();
        }

        return $this->configuredSectionSets ?? [];
    }

    /**
     * @return class-string<SectionSet>
     */
    public static function getSectionSetClass(): string
    {
        return SectionSet::class;
    }

    /**
     * @return array<int, SectionSet>
     */
    public static function getSectionSetDefinitions(): array
    {
        return DefinitionRegistry::get(static::class, 'getSectionSets', static::getSectionSetClass());
    }

    public static function findSectionSet(?int $value): ?SectionSet
    {
        return $value === null ? null : (static::getSectionSetDefinitions()[$value] ?? null);
    }

    public function getViewFile(): ?string
    {
        return $this->getType()?->getViewFile();
    }

    public function getVisibleAssets(): array
    {
        return $this->hasAssetsEnabled() && $this->isAttributeVisible(self::FIELD_ASSETS) ? $this->assets : [];
    }

    public function getAssetClass(): string
    {
        return SectionAsset::class;
    }

    public function hasAssetsEnabled(): bool
    {
        return static::getModule()->enableSectionAssets;
    }

    public function hasEntriesEnabled(): bool
    {
        return static::getModule()->enableSectionEntries;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'entry_id' => Yii::t('cms', 'SECTION_ENTRY_ID_LABEL'),
            'entry_count' => Yii::t('cms', 'SECTION_ENTRY_COUNT_LABEL'),
            'section_count' => Yii::t('cms', 'SECTION_SECTION_COUNT_LABEL')
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Section';
    }

    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%section}}';
    }
}
