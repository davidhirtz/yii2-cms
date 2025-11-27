<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\models\traits\EntryRelationTrait;
use davidhirtz\yii2\cms\models\traits\SlugAttributeTrait;
use davidhirtz\yii2\media\models\interfaces\AssetParentInterface;
use davidhirtz\yii2\media\models\traits\AssetParentTrait;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\validators\RelationValidator;
use Override;
use Yii;
use yii\helpers\Inflector;

/**
 * @property int $entry_id
 * @property int $position
 * @property string $name
 * @property string|null $slug
 * @property string $content
 * @property int $asset_count
 * @property int $entry_count
 *
 * @property-read Asset[] $assets {@see static::getAssets()}
 * @property-read Entry[] $entries {@see static::getEntries()}
 * @property-read SectionEntry $sectionEntry {@see static::getSectionEntry()}
 * @property-read SectionEntry[] $sectionEntries {@see static::getSectionEntries()}
 */
class Section extends ActiveRecord implements AssetParentInterface
{
    use AssetParentTrait;
    use EntryRelationTrait;
    use SlugAttributeTrait;

    final public const string AUTH_SECTION_CREATE = 'sectionCreate';
    final public const string AUTH_SECTION_DELETE = 'sectionDelete';
    final public const string AUTH_SECTION_UPDATE = 'sectionUpdate';
    final public const string AUTH_SECTION_ORDER = 'sectionOrder';
    final public const string AUTH_SECTION_ASSET_CREATE = 'sectionAssetCreate';
    final public const string AUTH_SECTION_ASSET_DELETE = 'sectionAssetDelete';
    final public const string AUTH_SECTION_ASSET_UPDATE = 'sectionAssetUpdate';
    final public const string AUTH_SECTION_ASSET_ORDER = 'sectionAssetOrder';

    public array|string|null $slugTargetAttribute = ['entry_id', 'slug'];
    public bool|null $shouldUpdateEntryAfterSave = null;

    private ?array $_trailParents = null;

    #[  Override]
    public function rules(): array
    {
        return [...parent::rules(), ...$this->getI18nRules([
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
                ['name', 'slug', 'content'],
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
                'unique',
                'targetAttribute' => $this->slugTargetAttribute,
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
                'when' => fn () => $this->isAttributeChanged('slug')
            ],
        ])];
    }

    #[Override]
    public function safeAttributes(): array
    {
        return array_diff(parent::safeAttributes(), ['entry_id']);
    }

    public function validateEntryId(): void
    {
        if (!$this->entry->hasSectionsEnabled()) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    #[Override]
    public function beforeValidate(): bool
    {
        if ($this->slug && !$this->customSlugBehavior) {
            $this->slug = Inflector::slug($this->slug);
        }

        return parent::beforeValidate();
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        $this->slug = $this->slug ?: null;
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
                $this->updateRelatedAssets();

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

        if (!$this->entry->isDeleted()) {
            if ($this->asset_count) {
                foreach ($this->assets as $asset) {
                    $asset->setIsBatch($this->getIsBatch());
                    $asset->delete();
                }
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

    public function getAssets(): AssetQuery
    {
        /** @var AssetQuery $relation */
        $relation = $this->hasMany(Asset::class, ['section_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('section');

        return $relation;
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

    /**
     * @param Asset[] $assets
     */
    public function populateAssetRelations(?array $assets): void
    {
        $relations = [];

        if ($assets) {
            foreach ($assets as $asset) {
                if ($asset->section_id === $this->id) {
                    $asset->populateParentRelation($this);
                    $relations[$asset->id] = $asset;
                }
            }
        }

        $this->populateRelation('assets', $relations);
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
            $this->_trailParents = [$entry, $this->entry];
        }
    }

    protected function updateRelatedAssets(): void
    {
        if ($this->asset_count) {
            Asset::updateAll(['entry_id' => $this->entry_id], ['section_id' => $this->id]);
        }
    }

    public function getTrailParents(): array
    {
        return $this->_trailParents ?? [$this->entry];
    }

    public function getTrailModelName(): string
    {
        if ($this->id) {
            return Yii::t('skeleton', '{model} #{id}', [
                'model' => $this->getTypeName() ?: $this->getTrailModelType(),
                'id' => $this->id,
            ]);
        }

        return $this->getTrailModelType();
    }

    public function getTrailModelType(): string
    {
        return Yii::t('cms', 'Section');
    }

    public function getNameColumnContent(): ?string
    {
        if (isset(static::getTypes()[$this->type]['nameColumn'])) {
            $nameColumn = static::getTypes()[$this->type]['nameColumn'];
            return is_callable($nameColumn) ? call_user_func($nameColumn, $this) : $nameColumn;
        }

        return null;
    }

    public function getAdminRoute(): array|false
    {
        return $this->id ? ['/admin/section/update', 'id' => $this->id] : false;
    }

    public function getEntriesOrderBy(): ?array
    {
        return $this->getTypeOptions()['entriesOrderBy'] ?? null;
    }

    public function getEntriesTypes(): ?array
    {
        return $this->getTypeOptions()['entriesTypes'] ?? null;
    }

    public function getHtmlId(): ?string
    {
        return $this->getI18nAttribute('slug') ?: ('section-' . $this->id);
    }

    public function getRoute(): false|array
    {
        return ($route = $this->entry->getRoute()) ? [...$route, '#' => $this->getHtmlId()] : false;
    }

    public function getViewFile(): ?string
    {
        return $this->getTypeOptions()['viewFile'] ?? null;
    }

    public function getVisibleAssets(): array
    {
        return $this->hasAssetsEnabled() ? $this->assets : [];
    }

    public function hasAssetsEnabled(): bool
    {
        return static::getModule()->enableSectionAssets && $this->isAttributeVisible('#assets');
    }

    public function hasEntriesEnabled(): bool
    {
        return static::getModule()->enableSectionEntries && $this->isAttributeVisible('#entries');
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'entry_id' => Yii::t('cms', 'Entry'),
            'entry_count' => Yii::t('cms', 'Entries'),
            'slug' => Yii::t('cms', 'Url'),
            'section_count' => Yii::t('cms', 'Sections')
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Section';
    }

    #[Override]
    public static function tableName(): string
    {
        return static::getModule()->getTableName('section');
    }
}
