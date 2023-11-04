<?php

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\traits\EntryRelationTrait;
use davidhirtz\yii2\cms\models\traits\SectionRelationTrait;
use davidhirtz\yii2\cms\modules\admin\Module;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\AssetParentGridView;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\media\models\interfaces\AssetInterface;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\models\traits\AssetTrait;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use Yii;

/**
 * @property int $id
 * @property int $entry_id
 * @property int $section_id
 * @property int $file_id
 * @property int $position
 * @property string|null $name
 * @property string|null $content
 * @property string|null $alt_text
 * @property string|null $link
 * @property int|null $updated_by_user_id
 * @property DateTime $updated_at
 * @property DateTime $created_at
 *
 * @property-read Entry|Section $parent {@see static::getParent()}
 */
class Asset extends ActiveRecord implements AssetInterface
{
    use AssetTrait;
    use EntryRelationTrait;
    use SectionRelationTrait;

    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['section_id'],
                $this->validateSectionId(...),
            ],
            [
                ['file_id', 'entry_id'],
                'required',
            ],
            [
                ['entry_id'],
                $this->validateEntryId(...),
            ],
            [
                $this->getI18nAttributesNames(['name', 'alt_text', 'link']),
                'string',
                'max' => 250,
            ]
        ];
    }

    public function beforeValidate(): bool
    {
        if ($this->autoplayLinkAttributeName) {
            $this->validateAutoplayLink();
        }

        return parent::beforeValidate();
    }

    /**
     * Validates section relation and sets entry relation, thus this needs to be called before entry validation. As
     * this method gets skipped on empty `section_id`, this only sets the relation while
     * {@see Section::validateEntryId()} will validate the section's entry_id.
     */
    public function validateSectionId(): void
    {
        if ($this->section) {
            $this->populateEntryRelation($this->section->entry);
        }
    }

    public function validateEntryId(): void
    {
        if (!$this->entry || (!$this->getIsNewRecord() && $this->isAttributeChanged('entry_id'))) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    public function afterSave($insert, $changedAttributes): void
    {
        if ($insert) {
            if (!$this->getIsBatch()) {
                $this->updateParentAfterInsert();
            }

            $this->updateOrDeleteFileByAssetCount();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public function afterDelete(): void
    {
        // Entry needs to be checked separately here because `Entry::beforeDelete()` deletes all related assets before
        // deleting the sections.
        if (!$this->entry->isDeleted() && (!$this->section_id || !$this->section->isDeleted())) {
            $parent = $this->getParent();
            $parent->asset_count = $this->findSiblings()->count();
            $parent->update();
        }

        $this->updateOrDeleteFileByAssetCount();

        parent::afterDelete();
    }

    public function findSiblings(): AssetQuery
    {
        return static::find()->where(['entry_id' => $this->entry_id, 'section_id' => $this->section_id]);
    }

    public static function find(): AssetQuery
    {
        return Yii::createObject(AssetQuery::class, [static::class]);
    }

    public function clone(array $attributes = []): static
    {
        $entry = ArrayHelper::remove($attributes, 'entry');
        $section = ArrayHelper::remove($attributes, 'section');

        $clone = new static();
        $clone->setAttributes(array_merge($this->getAttributes($this->safeAttributes()), $attributes), false);

        if ($entry) {
            $clone->populateEntryRelation($entry);
        }

        if ($section) {
            $clone->populateSectionRelation($section);
        }

        if ($entry || $section) {
            $clone->setIsBatch(true);
        }

        if ($clone->insert()) {
            $this->afterClone($clone);
        }

        return $clone;
    }

    public function populateSectionRelation(?Section $section): void
    {
        if ($section) {
            $this->populateRelation('entry', $section->entry);
        }

        $this->populateRelation('section', $section);
        $this->section_id = $section->id ?? null;
    }

    public function updateParentAfterInsert(): bool|int
    {
        $parent = $this->getParent();
        $parent->asset_count = $this->findSiblings()->count();
        return $parent->update();
    }

    public function getSitemapUrl(string $language): array|false
    {
        if ($this->includeInSitemap($language)) {
            return array_filter([
                'loc' => $this->file->getUrl(),
                'title' => $this->getAltText(),
                'caption' => ($content = $this->getI18nAttribute('content')) && $this->contentType == 'html' ?
                    strip_tags((string)$content) :
                    $content,
            ]);
        }

        return false;
    }

    /**
     * Includes only assets which are considered an image.
     *
     * @param null $language
     * @return bool
     */
    public function includeInSitemap($language = null): bool
    {
        return $this->isEnabled() && $this->file->hasPreview();
    }

    /**
     * @return array
     */
    public function getTrailParents(): array
    {
        return $this->isSectionAsset() ? [$this->section, $this->entry, $this->file] : [$this->entry, $this->file];
    }

    /**
     * @return string
     */
    public function getTrailModelType(): string
    {
        return Yii::t('cms', 'Asset');
    }

    public function getParent(): Entry|Section
    {
        return $this->section_id ? $this->section : $this->entry;
    }

    /**
     * @return class-string
     */
    public function getParentGridView(): string
    {
        return AssetParentGridView::class;
    }

    public function getParentName(): string
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('cms');
        return $module->name;
    }

    public function getFileCountAttribute(): string
    {
        return static::getModule()->enableI18nTables ? Yii::$app->getI18n()->getAttributeName('cms_asset_count') : 'cms_asset_count';
    }

    public function getAdminRoute(): false|array
    {
        return $this->id ? ['/admin/cms/asset/update', 'id' => $this->id] : false;
    }

    public function getRoute(): array|false
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isEntryAsset(): bool
    {
        return !$this->section_id;
    }

    /**
     * @return bool
     */
    public function isSectionAsset(): bool
    {
        return (bool)$this->section_id;
    }

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'section_id' => Yii::t('cms', 'Section'),
            'file_id' => Yii::t('media', 'File'),
            'alt_text' => Yii::t('cms', 'Alt text'),
            'link' => Yii::t('cms', 'Link')
        ];
    }

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return static::getModule()->getTableName('cms_asset');
    }
}