<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\media\models\AssetParentInterface;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;
use yii\base\Widget;
use yii\db\ActiveQuery;
use yii\helpers\Inflector;

/**
 * Class Entry
 * @package davidhirtz\yii2\cms\models\base
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
 * @property Asset[] $assets {@link \davidhirtz\yii2\cms\models\Entry::getAssets()}
 * @property Section[] $sections {@link \davidhirtz\yii2\cms\models\Entry::getSections()}
 * @property EntryCategory $entryCategory {@link \davidhirtz\yii2\cms\models\Entry::getEntryCategory()}
 * @property EntryCategory[] $entryCategories {@link \davidhirtz\yii2\cms\models\Entry::getEntryCategories()}
 *
 * @method static \davidhirtz\yii2\cms\models\Entry findOne($condition)
 */
class Entry extends ActiveRecord implements AssetParentInterface
{
    /**
     * @var bool|string
     */
    public $contentType = false;

    /**
     * @var array|string
     */
    public $dateTimeValidator = '\davidhirtz\yii2\datetime\DateTimeValidator';

    /**
     * @inheritdoc
     */
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
                'filter',
                'filter' => 'trim',
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
                'comboNotUnique' => Yii::t('yii', '{attribute} "{value}" has already been taken.'),
                'when' => function () {
                    return $this->isAttributeChanged('slug');
                }
            ],
            array_merge([$this->getI18nAttributeNames('publish_date')], (array)$this->dateTimeValidator),
        ]));
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate(): bool
    {
        if (!$this->slug && $this->isSlugRequired()) {
            $this->slug = $this->name;
        }

        if (!$this->customSlugBehavior) {
            $this->slug = Inflector::slug($this->slug);
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        if ($isValid = parent::beforeDelete()) {
            if ($this->asset_count || $this->section_count) {
                foreach ($this->assets as $asset) {
                    $asset->delete();
                }
            }

            if ($this->section_count) {
                foreach ($this->sections as $section) {
                    $section->delete();
                }
            }

            if ($this->category_ids) {
                foreach ($this->entryCategories as $entryCategory) {
                    $entryCategory->delete();
                }
            }
        }

        return $isValid;
    }

    /**
     * @return ActiveQuery
     */
    public function getEntryCategory()
    {
        return $this->hasOne(EntryCategory::class, ['entry_id' => 'id'])
            ->inverseOf('entry');
    }

    /**
     * @return ActiveQuery
     */
    public function getEntryCategories()
    {
        return $this->hasMany(EntryCategory::class, ['entry_id' => 'id'])
            ->inverseOf('entry');
    }

    /**
     * @return SectionQuery
     */
    public function getSections()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(Section::class, ['entry_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');
    }

    /**
     * @return AssetQuery
     */
    public function getAssets(): AssetQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(Asset::class, ['entry_id' => 'id'])
            ->orderBy(['position' => SORT_ASC])
            ->indexBy('id')
            ->inverseOf('entry');
    }

    /**
     * @inheritdoc
     * @return EntryQuery
     */
    public static function find()
    {
        return new EntryQuery(get_called_class());
    }

    /**
     * @return EntryQuery
     */
    public function findSiblings()
    {
        return static::find();
    }

    /**
     * @param array $assetIds
     */
    public function updateAssetOrder($assetIds)
    {
        $assets = $this->getAssets()
            ->select(['id', 'position'])
            ->andWhere(['id' => $assetIds])
            ->withoutSections()
            ->all();

        if (Asset::updatePosition($assets, array_flip($assetIds))) {
            Trail::createOrderTrail($this, Yii::t('cms', 'Asset order changed'));
        }
    }

    /**
     * @param array $sectionIds
     */
    public function updateSectionOrder($sectionIds)
    {
        $sections = $this->getSections()
            ->select(['id', 'position'])
            ->andWhere(['id' => $sectionIds])
            ->all();

        if (Section::updatePosition($sections, array_flip($sectionIds))) {
            Trail::createOrderTrail($this, Yii::t('cms', 'Section order changed'));
        }
    }

    /**
     * @param array $attributes
     * @return \davidhirtz\yii2\cms\models\Entry
     */
    public function clone($attributes = [])
    {
        /** @var \davidhirtz\yii2\cms\models\Entry $clone */
        $clone = new static();
        $clone->setAttributes(array_merge($this->getAttributes(), $attributes ?: ['status' => static::STATUS_DRAFT]));
        $clone->generateUniqueSlug();

        if ($clone->insert()) {
            foreach ($this->getCategoryIds() as $categoryId) {
                $entryCategory = new EntryCategory(['category_id' => $categoryId]);
                $entryCategory->populateEntryRelation($clone);
                $entryCategory->insert();
            }

            foreach ($this->sections as $section) {
                $section->clone(['entry_id' => $clone->id]);
            }

            $assets = $this->getAssets()->withoutSections()->all();

            foreach ($assets as $asset) {
                $assetClone = new Asset();
                $assetClone->setAttributes(array_merge($asset->getAttributes(), ['entry_id' => $clone->id]));
                $assetClone->populateRelation('entry', $clone);
                $assetClone->insert();
            }
        }

        return $clone;
    }

    /**
     * @param Asset[]|null $assets
     */
    public function populateAssetRelations($assets = null)
    {
        if ($assets === null) {
            $assets = $this->assets;
        }

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

    /**
     * Recalculates {@link \davidhirtz\yii2\cms\models\Entry::$category_ids}.
     * @return $this
     */
    public function recalculateCategoryIds()
    {
        $this->category_ids = ArrayHelper::createCacheString($this->getEntryCategories()
            ->select(['category_id'])
            ->column());

        return $this;
    }

    /**
     * Recalculates {@link \davidhirtz\yii2\cms\models\Entry::$section_count}.
     * @return $this
     */
    public function recalculateSectionCount()
    {
        $this->section_count = (int)$this->getSections()->count();
        return $this;
    }

    /**
     * @return array
     */
    public function getCategoryIds(): array
    {
        return ArrayHelper::cacheStringToArray($this->category_ids);
    }

    /**
     * @return int
     */
    public function getCategoryCount(): int
    {
        return count($this->getCategoryIds());
    }

    /**
     * @return array
     */
    public function getTrailAttributes(): array
    {
        return array_diff(parent::getTrailAttributes(), [
            'category_ids',
            'section_count',
            'updated_at',
            'created_at',
        ]);
    }

    /**
     * @return string
     */
    public function getTrailModelName()
    {
        if ($this->id) {
            return $this->getI18nAttribute('name') ?: Yii::t('skeleton', '{model} #{id}', [
                'model' => $this->getTrailModelType(),
                'id' => $this->id,
            ]);
        }

        return $this->getTrailModelType();
    }

    /**
     * @return string
     */
    public function getTrailModelType(): string
    {
        return $this->getTypeName() ?: Yii::t('cms', 'Entry');
    }

    /**
     * @return array|false
     */
    public function getAdminRoute()
    {
        return ['/admin/entry/update', 'id' => $this->id];
    }

    /**
     * @return array|false
     */
    public function getRoute()
    {
        return array_filter(['/cms/site/view', 'entry' => $this->getI18nAttribute('slug')]);
    }

    /**
     * @return EntryActiveForm|Widget
     */
    public function getActiveForm()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::getTypes()[$this->type]['activeForm'] ?? EntryActiveForm::class;
    }

    /**
     * @return bool
     */
    public function hasAssetsEnabled(): bool
    {
        return static::getModule()->enableEntryAssets;
    }

    /**
     * @return bool
     */
    public function hasCategoriesEnabled(): bool
    {
        return static::getModule()->enableCategories;
    }

    /**
     * @return bool
     */
    public function hasSectionsEnabled(): bool
    {
        return static::getModule()->enableSections;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'slug' => Yii::t('cms', 'Url'),
            'title' => Yii::t('cms', 'Meta title'),
            'description' => Yii::t('cms', 'Meta description'),
            'publish_date' => Yii::t('cms', 'Published'),
            'branchCount' => Yii::t('cms', 'Entries'),
            'section_count' => Yii::t('cms', 'Sections'),
        ]);
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return 'Entry';
    }

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return static::getModule()->getTableName('entry');
    }
}