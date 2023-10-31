<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\SectionEntry;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\Module;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeValidator;
use davidhirtz\yii2\media\models\AssetParentInterface;
use davidhirtz\yii2\skeleton\behaviors\RedirectBehavior;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;
use yii\db\ActiveQuery;

/**
 * Entry is the base model class for all CMS entries, which can contain related {@link Section} models and
 * {@link Asset} models. Entries can be organized by {@link EntryCategory} relations.
 *
 * @see \davidhirtz\yii2\cms\models\Entry
 *
 * @property int $position
 * @property string $name
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property string $content
 * @property DateTime $publish_date
 * @property string $category_ids
 * @property int $section_count
 * @property int $asset_count
 *
 * @property Asset[] $assets {@link static::getAssets()}
 * @property SectionEntry $sectionEntry {@link static::getSectionEntry()}
 * @property Section[] $sections {@link static::getSections()}
 * @property EntryCategory $entryCategory {@link static::getEntryCategory()}
 * @property EntryCategory[] $entryCategories {@link static::getEntryCategories()}
 *
 * @method static static findOne($condition)
 */
class Entry extends ActiveRecord implements AssetParentInterface
{
    public string|false $contentType = false;

    /**
     * @var array|string the validator used to verify the publishing date.
     */
    public array|string $dateTimeValidator = DateTimeValidator::class;

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'RedirectBehavior' => RedirectBehavior::class,
        ]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), $this->getI18nRules([
            [
                ['name'],
                'required',
            ],
            [
                ['slug'],
                'required',
                'when' => function () {
                    return $this->isSlugRequired();
                }
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
                ['slug'],
                'string',
                'max' => static::SLUG_MAX_LENGTH,
            ],
            [
                ['slug'],
                $this->slugUniqueValidator,
                'targetAttribute' => $this->slugTargetAttribute,
            ],
            array_merge([$this->getI18nAttributeNames('publish_date')], (array)$this->dateTimeValidator),
        ]));
    }

    public function beforeValidate(): bool
    {
        $this->ensureSlug();
        return parent::beforeValidate();
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

        return parent::beforeSave($insert);
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
        }

        return $isValid;
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(Section::class, ['entry_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');
    }

    public function getAssets(): AssetQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(Asset::class, ['entry_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');
    }

    public static function find(): EntryQuery
    {
        return Yii::createObject(EntryQuery::class, [get_called_class()]);
    }

    public function findSiblings(): EntryQuery
    {
        return static::find();
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

    public function updateAssetOrder(array $assetIds): void
    {
        $assets = $this->getAssets()
            ->select(['id', 'position'])
            ->andWhere(['id' => $assetIds])
            ->withoutSections()
            ->all();

        if (Asset::updatePosition($assets, array_flip($assetIds))) {
            Trail::createOrderTrail($this, Yii::t('cms', 'Asset order changed'));

            $this->updated_at = new DateTime();
            $this->update();
        }
    }

    public function updateSectionOrder(array $sectionIds): void
    {
        $sections = $this->getSections()
            ->select(['id', 'position'])
            ->andWhere(['id' => $sectionIds])
            ->all();

        if (Section::updatePosition($sections, array_flip($sectionIds))) {
            Trail::createOrderTrail($this, Yii::t('cms', 'Section order changed'));

            $this->updated_at = new DateTime();
            $this->update();
        }
    }

    public function clone(array $attributes = []): static
    {
        $attributes['status'] ??= static::STATUS_DRAFT;

        /** @var \davidhirtz\yii2\cms\models\Entry $clone */
        $clone = new static();
        $clone->setAttributes(array_merge($this->getAttributes($this->safeAttributes()), $attributes), false);
        $clone->asset_count = $this->asset_count;
        $clone->section_count = $this->section_count;
        $clone->generateUniqueSlug();

        if ($this->beforeClone($clone) && $clone->insert()) {
            foreach ($this->getCategoryIds() as $categoryId) {
                $entryCategory = EntryCategory::create();
                $entryCategory->category_id = $categoryId;
                $entryCategory->populateEntryRelation($clone);
                $entryCategory->insert();
            }

            if ($this->section_count) {
                $sectionCount = 1;

                foreach ($this->sections as $section) {
                    $section->clone([
                        'entry' => $clone,
                        'position' => $sectionCount++,
                    ]);
                }
            }

            if ($this->asset_count) {
                $assets = $this->getAssets()->withoutSections()->all();
                $assetCount = 1;

                foreach ($assets as $asset) {
                    $asset->clone([
                        'entry' => $clone,
                        'position' => $assetCount++,
                    ]);
                }
            }

            $this->afterClone($clone);
        }

        return $clone;
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

    public function recalculateCategoryIds(): static
    {
        $this->category_ids = ArrayHelper::createCacheString($this->getEntryCategories()
            ->select(['category_id'])
            ->column());

        return $this;
    }

    public function recalculateSectionCount(): static
    {
        $this->section_count = (int)$this->getSections()->count();
        return $this;
    }

    public function getCategoryIds(): array
    {
        return ArrayHelper::cacheStringToArray($this->category_ids);
    }

    public function getCategoryCount(): int
    {
        return count($this->getCategoryIds());
    }

    /**
     * Extends the default XML sitemap url by image URLs if related assets were found. This is automatically the
     * case if {@link Module::$enableImageSitemaps} is set to `true`.
     */
    public function getSitemapUrl(string $language): false|array
    {
        if ($url = parent::getSitemapUrl($language)) {
            /** @var Asset[]|false $assets */
            if ($assets = $this->getRelatedRecords()['assets'] ?? false) {
                foreach ($assets as $asset) {
                    if ($imageUrl = $asset->getSitemapUrl($language)) {
                        $url['images'][] = $imageUrl;
                    }
                }
            }
        }

        return $url;
    }

    public function getTrailAttributes(): array
    {
        return array_diff(parent::getTrailAttributes(), [
            'category_ids',
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

    public function getAdminRoute(): false|array
    {
        return $this->id ? ['/admin/entry/update', 'id' => $this->id] : false;
    }

    public function getRoute(): false|array
    {
        return array_filter(['/cms/site/view', 'entry' => $this->getI18nAttribute('slug')]);
    }

    /**
     * @return class-string
     */
    public function getActiveForm(): string
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::getTypes()[$this->type]['activeForm'] ?? EntryActiveForm::class;
    }

    public function hasAssetsEnabled(): bool
    {
        return static::getModule()->enableEntryAssets;
    }

    public function hasCategoriesEnabled(): bool
    {
        return static::getModule()->enableCategories;
    }

    public function hasSectionsEnabled(): bool
    {
        return static::getModule()->enableSections;
    }

    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'slug' => Yii::t('cms', 'Url'),
            'title' => Yii::t('cms', 'Meta title'),
            'description' => Yii::t('cms', 'Meta description'),
            'publish_date' => Yii::t('cms', 'Published'),
            'entry_count' => Yii::t('cms', 'Entries'),
            'section_count' => Yii::t('cms', 'Sections'),
        ]);
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