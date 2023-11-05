<?php

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\models\traits\AssetParentTrait;
use davidhirtz\yii2\cms\models\traits\EntryRelationTrait;
use davidhirtz\yii2\cms\models\traits\SlugAttributeTrait;
use davidhirtz\yii2\media\models\interfaces\AssetParentInterface;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
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
    use AssetParentTrait;
    use EntryRelationTrait;
    use SlugAttributeTrait;

    public array|string|null $slugTargetAttribute = ['entry_id', 'slug'];
    private ?array $_trailParents = null;

    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getI18nRules([
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
                'max' => $this->slugMaxLength,
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
     * Updates related entries after save if {@see Section::getIsBatch} is false.
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