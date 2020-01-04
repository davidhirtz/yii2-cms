<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\AssetActiveForm;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\media\models\AssetInterface;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\models\queries\FileQuery;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\Widget;

/**
 * Class Asset.
 * @package davidhirtz\yii2\cms\models\base
 *
 * @property int $id
 * @property int $entry_id
 * @property int $section_id
 * @property int $file_id
 * @property int $position
 * @property string $name
 * @property string $content
 * @property string $alt_text
 * @property string $link
 * @property int $updated_by_user_id
 * @property DateTime $updated_at
 * @property DateTime $created_at
 * @property Entry $entry
 * @property Section $section
 * @property Entry|Section $parent
 * @property File $file
 * @property User $updated
 *
 * @method static \davidhirtz\yii2\cms\models\Asset findOne($condition)
 */
class Asset extends \davidhirtz\yii2\cms\models\base\ActiveRecord implements AssetInterface
{
    /**
     * Constants.
     */
    const TYPE_VIEWPORT_MOBILE = 2;
    const TYPE_VIEWPORT_DESKTOP = 3;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                ['section_id'],
                'validateSectionId',
            ],
            [
                ['file_id', 'entry_id'],
                'required',
            ],
            [
                ['entry_id'],
                'validateEntryId',
            ],
            [
                $this->getI18nAttributesNames(['name', 'alt_text', 'link']),
                'string',
                'max' => 250,
            ],
        ]);
    }

    /**
     * Validates section relation and sets entry id, thus this needs to be called before entry validation.
     */
    public function validateSectionId()
    {
        if ($this->section) {
            if ($this->getIsNewRecord()) {
                $this->entry_id = $this->section->entry_id;
            } elseif ($this->isAttributeChanged('entry_id')) {
                $this->addInvalidAttributeError('section_id');
            }
        }
    }

    /**
     * Validates entry and populates relation.
     */
    public function validateEntryId()
    {
        if (!$this->entry || (!$this->getIsNewRecord() && $this->isAttributeChanged('entry_id'))) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            $this->recalculateAssetCount();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        $this->recalculateAssetCount();
        parent::afterDelete();
    }

    /**
     * @return EntryQuery
     */
    public function getEntry(): EntryQuery
    {
        return $this->hasOne(Entry::class, ['id' => 'entry_id']);
    }

    /**
     * @return SectionQuery
     */
    public function getSection(): SectionQuery
    {
        return $this->hasOne(Section::class, ['id' => 'section_id']);
    }

    /**
     * @return FileQuery
     */
    public function getFile(): FileQuery
    {
        return $this->hasOne(File::class, ['id' => 'file_id']);
    }

    /**
     * @return AssetQuery
     */
    public function findSiblings()
    {
        return static::find()->where(['entry_id' => $this->entry_id, 'section_id' => $this->section_id]);
    }

    /**
     * @return AssetQuery
     */
    public static function find()
    {
        return Yii::createObject(AssetQuery::class, [get_called_class()]);
    }

    /**
     * Recalculates related asset count.
     */
    public function recalculateAssetCount()
    {
        $parent = $this->getParent();
        $parent->asset_count = $this->findSiblings()->count();
        $parent->update(false);

        if (!$this->file->isDeleted()) {
            $this->file->setAttribute('cms_asset_count', static::find()->where(['file_id' => $this->file_id])->count());
            $this->file->update(false);
        }
    }

    /**
     * @return Entry|Section
     */
    public function getParent()
    {
        return $this->section_id ? $this->section : $this->entry;
    }

    /**
     * @param array|string $transformations
     * @param string $extension
     * @return array|string
     */
    public function getSrcset($transformations = null, $extension = null)
    {
        $transformations = is_string($transformations) ? [$transformations] : $transformations;
        $srcset = [];

        if ($this->file->isTransformableImage()) {
            foreach ($transformations as $name) {
                if ($url = $this->file->getTransformationUrl($name, $extension)) {
                    $option = $this->file->getTransformationOptions($name);
                    $srcset[isset($option['width']) ? $option['width'] : $this->file->width] = $url;
                }
            }
        }

        return $srcset ? $srcset : $this->file->getUrl();
    }

    /**
     * @return string
     */
    public function getAutoplayLink()
    {
        return $this->link ? ($this->link . (strpos($this->link, '?') !== false ? '&' : '?') . 'autoplay=1') : '';
    }

    /**
     * @return array
     */
    public static function getViewportTypes(): array
    {
        return [
            static::TYPE_DEFAULT => [
                'name' => Yii::t('cms', 'All devices'),
            ],
            static::TYPE_VIEWPORT_MOBILE => [
                'name' => Yii::t('cms', 'Mobile'),
            ],
            static::TYPE_VIEWPORT_DESKTOP => [
                'name' => Yii::t('cms', 'Desktop'),
            ],
        ];
    }

    /**
     * @return AssetActiveForm|Widget
     */
    public function getActiveForm()
    {
        return static::getTypes()[$this->type]['activeForm'] ?? AssetActiveForm::class;
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'alt_text' => Yii::t('cms', 'Alt text'),
            'link' => Yii::t('cms', 'Link'),
        ]);
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return 'Asset';
    }

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return static::getModule()->getTableName('cms_asset');
    }
}