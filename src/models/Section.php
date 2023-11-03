<?php

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\models\traits\EntryRelationTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\SectionGridView;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\media\models\AssetParentInterface;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\validators\RelationValidator;
use Yii;
use yii\helpers\Inflector;

/**
 * @property int $entry_id
 * @property int $position
 * @property string $name
 * @property string $slug
 * @property string $content
 * @property int $asset_count
 * @property int $entry_count
 *
 * @property-read Asset[] $assets {@see static::getAssets()}
 * @property-read Entry $entries {@see static::getEntries()}
 * @property-read SectionEntry $sectionEntry {@see static::getSectionEntry()}
 * @property-read SectionEntry[] $sectionEntries {@see static::getSectionEntries()}
 */
class Section extends ActiveRecord implements AssetParentInterface
{
    use EntryRelationTrait;

    public array|string|null $slugTargetAttribute = ['entry_id', 'slug'];
    private ?array $_trailParents = null;

    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getI18nRules([
            [
                ['entry_id'],
                'required',
            ],
            [
                ['entry_id'],
                'filter',
                'filter' => 'intval',
            ],
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
                'max' => 250,
            ],
            [
                ['slug'],
                'string',
                'max' => static::SLUG_MAX_LENGTH,
            ],
            [
                ['slug'],
                'unique',
                'targetAttribute' => $this->slugTargetAttribute,
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
                'when' => fn() => $this->isAttributeChanged('slug')
            ],
        ]));
    }

    public function validateEntryId(): void
    {
        if (!$this->entry->hasSectionsEnabled()) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    public function beforeValidate(): bool
    {
        if ($this->slug && !$this->customSlugBehavior) {
            $this->slug = Inflector::slug($this->slug);
        }

        return parent::beforeValidate();
    }

    public function beforeSave($insert): bool
    {
        $this->slug = $this->slug ? (string)$this->slug : null;

        // Handle section move / clone, inserts will be handled by parent implementation
        if (!$insert && $this->isAttributeChanged('entry_id')) {
            $this->position = $this->getMaxPosition() + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * Updates related entries after save if {@link Section::getIsBatch} is false.
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes): void
    {
        if (!$this->getIsBatch()) {
            if (array_key_exists('entry_id', $changedAttributes)) {
                $this->updateOldEntryRelation($changedAttributes['entry_id'] ?? false);
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

    public function afterDelete(): void
    {
        if (!$this->entry->isDeleted()) {
            $this->entry->recalculateSectionCount()->update();
        }

        parent::afterDelete();
    }

    public function getAssets(): AssetQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(Asset::class, ['section_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('section');
    }

    public function getEntries(): EntryQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(Entry::class, ['id' => 'entry_id'])
            ->via('sectionEntries');
    }

    public function getSectionEntry(): ActiveQuery
    {
        return $this->hasOne(SectionEntry::class, ['section_id' => 'id'])
            ->inverseOf('section');
    }

    public function getSectionEntries(): ActiveQuery
    {
        return $this->hasMany(SectionEntry::class, ['section_id' => 'id'])
            ->inverseOf('section');
    }

    public function findSiblings(): SectionQuery
    {
        return static::find()->where(['entry_id' => $this->entry_id]);
    }

    public static function find(): SectionQuery
    {
        return Yii::createObject(SectionQuery::class, [static::class]);
    }

    public function updateAssetOrder(array $assetIds): void
    {
        $assets = $this->getAssets()
            ->select(['id', 'position'])
            ->andWhere(['id' => $assetIds])
            ->all();

        if (Asset::updatePosition($assets, array_flip($assetIds))) {
            $trail = Trail::createOrderTrail($this, Yii::t('cms', 'Asset order changed'));
            Trail::createOrderTrail($this->entry, Yii::t('cms', 'Section asset order changed'), [
                'trail_id' => $trail->id,
            ]);

            $this->updated_at = new DateTime();
            $this->update();
        }
    }

    public function updateSectionEntryOrder(array $sectionEntryIds): void
    {
        $sectionEntries = $this->getSectionEntry()
            ->select(['id', 'position'])
            ->andWhere(['id' => $sectionEntryIds])
            ->all();

        if (SectionEntry::updatePosition($sectionEntries, array_flip($sectionEntryIds))) {
            Trail::createOrderTrail($this, Yii::t('cms', 'Entry order changed'));
            $this->updateAttributesBlameable(['updated_by_user_id', 'updated_at']);
        }
    }

    public function clone(array $attributes = []): static
    {
        $entry = ArrayHelper::remove($attributes, 'entry');

        if (!$entry) {
            // Only set status to draft if clone is not triggered by entry
            $attributes['status'] ??= static::STATUS_DRAFT;
        }

        $clone = new static();
        $clone->setAttributes(array_merge($this->getAttributes($this->safeAttributes()), $attributes), false);

        if ($entry) {
            $clone->populateEntryRelation($entry);
            $clone->setIsBatch(true);
        }

        $clone->generateUniqueSlug();

        if ($this->beforeClone($clone) && $clone->insert()) {
            if ($this->asset_count) {
                $assets = $this->getAssets()->all();
                $assetCount = 0;

                foreach ($assets as $asset) {
                    $asset->clone([
                        'section' => $clone,
                        'position' => ++$assetCount,
                    ]);
                }

                $clone->updateAttributes(['asset_count' => $assetCount]);
            }

            if ($this->entry_count) {
                $entries = $this->getEntries()->all();
                Yii::debug(count($entries));
                $entryCount = 0;

                foreach ($entries as $entry) {
                    $sectionEntry = SectionEntry::create();
                    $sectionEntry->populateEntryRelation($entry);
                    $sectionEntry->populateSectionRelation($clone);
                    $sectionEntry->setIsBatch(true);
                    $sectionEntry->position = ++$entryCount;
                    $sectionEntry->insert();
                }

                $clone->updateAttributes(['entry_count' => $entryCount]);
            }

            $this->afterClone($clone);
        }

        return $clone;
    }

    /**
     * @param Asset[] $assets
     */
    public function populateAssetRelations(?array $assets): void
    {
        $relations = [];

        if ($assets) {
            foreach ($assets as $asset) {
                if ($asset->section_id == $this->id) {
                    $asset->populateRelation('section', $this);
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
     *
     * @param int|null $entryId
     */
    protected function updateOldEntryRelation(?int $entryId): void
    {
        if ($entryId) {
            $entry = Entry::findOne($entryId);

            if ($entry) {
                $entry->recalculateSectionCount()->update();
                $this->_trailParents = [$entry, $this->entry];
            }
        }
    }

    /**
     * Updates related asset relations after the section was moved to another entry. Override this method if assets
     * should be further manipulated after the section's entry was changed.
     */
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

    /**
     * @return string|null custom name for {@link SectionGridView::nameColumn()}
     */
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

    public function getHtmlId(): ?string
    {
        return $this->getI18nAttribute('slug') ?: ('section-' . $this->id);
    }

    public function getRoute(): false|array
    {
        return ($route = $this->entry->getRoute()) ? array_merge($route, ['#' => $this->getHtmlId()]) : false;
    }

    public function getViewFile(): ?string
    {
        return $this->getTypeOptions()['viewFile'] ?? null;
    }

    public function hasAssetsEnabled(): bool
    {
        return static::getModule()->enableSectionAssets;
    }

    public function hasEntriesEnabled(): bool
    {
        return static::getModule()->enableSectionEntries;
    }

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

    public function formName(): string
    {
        return 'Section';
    }

    public static function tableName(): string
    {
        return static::getModule()->getTableName('section');
    }
}