<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\traits\EntryRelationTrait;
use davidhirtz\yii2\cms\models\traits\SectionRelationTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\panels\FileAssetParentPanel;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\models\interfaces\AssetInterface;
use davidhirtz\yii2\media\models\traits\AssetTrait;
use davidhirtz\yii2\media\models\traits\EmbedUrlTrait;
use davidhirtz\yii2\skeleton\models\interfaces\DraftStatusAttributeInterface;
use davidhirtz\yii2\skeleton\validators\RelationValidator;
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
 * @property string|null $embed_url
 * @property int|null $updated_by_user_id
 * @property DateTime $updated_at
 * @property DateTime $created_at
 *
 * @property-read Entry|Section $parent {@see static::getParent()}
 */
class Asset extends ActiveRecord implements AssetInterface, DraftStatusAttributeInterface
{
    use AssetTrait;
    use EmbedUrlTrait;
    use EntryRelationTrait;
    use SectionRelationTrait;

    final public const AUTH_ASSET_DELETE = 'assetDelete';
    final public const AUTH_ASSET_UPDATE = 'assetUpdate';

    public bool|null $shouldUpdateParentAfterInsert = null;

    /**
     * The section validation needs to be called before the entry validation. As it sets the necessary entry relation
     * for the entry validation to work.
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getEmbedUrlTraitRules(),
            ...$this->getI18nRules([
                [
                    ['name', 'alt_text', 'link'],
                    'string',
                    'max' => 255,
                ],
            ]),
            [
                ['entry_id', 'file_id'],
                RelationValidator::class,
                'required' => true,
            ],
        ];
    }

    public function beforeValidate(): bool
    {
        if ($this->section) {
            $this->populateEntryRelation($this->section->entry);
        }

        return parent::beforeValidate();
    }

    public function afterValidate(): void
    {
        if (!$this->getIsNewRecord()) {
            if ($this->isAttributeChanged('entry_id')) {
                $this->addInvalidAttributeError('entry_id');
            }

            if ($this->isAttributeChanged('section_id')) {
                $this->addInvalidAttributeError('section_id');
            }
        }

        parent::afterValidate();
    }

    public function beforeSave($insert): bool
    {
        $this->shouldUpdateParentAfterInsert ??= !$this->getIsBatch();
        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes): void
    {
        if ($insert) {
            if ($this->shouldUpdateParentAfterInsert) {
                $this->updateParentAfterInsert();
            }
        } elseif ($changedAttributes) {
            $this->parent->updated_at = $this->updated_at;
            $this->parent->update();
        }

        if (array_key_exists('file_id', $changedAttributes)) {
            $file = File::findOne($changedAttributes['file_id']);

            if ($file) {
                $file->{$this->getFileCountAttributeName()} = static::find()->where(['file_id' => $file->id])->count();
                $file->update();
            }

            $this->updateFileRelatedCount();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public function afterDelete(): void
    {
        // Entry needs to be checked separately here because `Entry::beforeDelete()` deletes all related assets before
        // deleting the sections.
        if (!$this->entry->isDeleted() && (!$this->section_id || !$this->section->isDeleted())) {
            $this->updateParentAfterDelete();
        }

        if (!$this->file->isDeleted()) {
            $this->updateFileRelatedCount();
        }

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

    public function populateParentRelation(Entry|Section $parent): void
    {
        if ($parent instanceof Entry) {
            $this->populateEntryRelation($parent);
        } else {
            $this->populateSectionRelation($parent);
        }
    }

    public function populateSectionRelation(?Section $section): void
    {
        $this->populateEntryRelation($section?->entry);

        $this->populateRelation('section', $section);
        $this->section_id = $section->id ?? null;
    }

    protected function updateParentAfterDelete(): bool|int
    {
        return $this->updateParentAssetCount();
    }

    protected function updateParentAfterInsert(): bool|int
    {
        return $this->updateParentAssetCount();
    }

    public function updateFileRelatedCount(): bool|int
    {
        $this->file->{$this->getFileCountAttributeName()} = static::find()->where(['file_id' => $this->file_id])->count();
        return $this->file->update();
    }

    protected function updateParentAssetCount(): bool|int
    {
        $this->parent->asset_count = $this->findSiblings()->count();
        return $this->parent->update();
    }

    public function getFileCountAttributeName(): string
    {
        return static::getModule()->enableI18nTables
            ? Yii::$app->getI18n()->getAttributeName('cms_asset_count')
            : 'cms_asset_count';
    }

    public function getTrailAttributes(): array
    {
        return array_diff($this->attributes(), [
            // Used by `yii2-cms-hotspot` extension
            'hotspot_count',
        ]);
    }

    public function getSitemapUrl(?string $language = null): array|false
    {
        if ($this->includeInSitemap($language)) {
            $content = $this->getI18nAttribute('content');

            if ($this->contentType == 'html') {
                $content = strip_tags((string)$content);
            }

            return array_filter([
                'loc' => $this->file->getUrl(),
                'title' => $this->getAltText(),
                'caption' => $content,
            ]);
        }

        return false;
    }

    public function includeInSitemap($language = null): bool
    {
        return $this->isEnabled() && $this->file->hasPreview();
    }

    public function getParent(): Entry|Section
    {
        return $this->section_id ? $this->section : $this->entry;
    }

    public function getFilePanelClass(): string
    {
        return FileAssetParentPanel::class;
    }

    public function getFileCountAttributeNames(): array
    {
        $languages = static::getModule()->getLanguages();
        $attributes = array_map(fn ($lang) => Yii::$app->getI18n()->getAttributeName('cms_asset_count', $lang), $languages);

        return array_combine($languages, $attributes);
    }

    public function getAdminRoute(): false|array
    {
        return $this->id ? ['/admin/cms/asset/update', 'id' => $this->id] : false;
    }

    public function getRoute(): array|false
    {
        return false;
    }

    public function getTrailModelType(): string
    {
        return Yii::t('cms', 'Asset');
    }

    public function getTrailParents(): array
    {
        return array_filter([$this->section, $this->entry, $this->file]);
    }

    public function isEntryAsset(): bool
    {
        return !$this->section_id;
    }

    public function isSectionAsset(): bool
    {
        return (bool)$this->section_id;
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            ...$this->getEmbedUrlTraitAttributeLabels(),
            'section_id' => Yii::t('cms', 'Section'),
            'file_id' => Yii::t('media', 'File'),
            'content' => Yii::t('media', 'Caption'),
            'alt_text' => Yii::t('cms', 'Alt text'),
            'link' => Yii::t('cms', 'Link'),
        ];
    }

    public function formName(): string
    {
        return 'Asset';
    }

    public static function tableName(): string
    {
        return static::getModule()->getTableName('cms_asset');
    }
}
