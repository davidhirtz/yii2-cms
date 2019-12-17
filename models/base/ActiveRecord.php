<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\db\I18nAttributesTrait;
use davidhirtz\yii2\skeleton\db\StatusAttributeTrait;
use davidhirtz\yii2\skeleton\db\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;
use Yii;


/**
 * Class ActiveRecord.
 * @package davidhirtz\yii2\cms\models\base
 *
 * @property int $id
 * @property int $status
 * @property int $type
 * @property string $content
 * @property int $position
 * @property int $updated_by_user_id
 * @property DateTime $updated_at
 * @property DateTime $created_at
 * @property User $updated
 */
abstract class ActiveRecord extends \davidhirtz\yii2\skeleton\db\ActiveRecord
{
    use I18nAttributesTrait, StatusAttributeTrait, TypeAttributeTrait,
        ModuleTrait;

    /**
     * @var bool whether slugs should not automatically be checked and processed.
     */
    public $customSlugBehavior = false;

    /**
     * @var mixed used when $contentType is set to "html". use array with the first value containing the
     * validator class, following keys can be used to configure the validator, string containing the class
     * name or false for disabling the validation.
     */
    public $htmlValidator = 'davidhirtz\yii2\skeleton\validators\HtmlValidator';

    /**
     * @var bool|string
     */
    public $contentType = 'html';

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'DateTimeBehavior' => DateTimeBehavior::class,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                ['status'],
                'validateStatus',
            ],
            [
                ['type'],
                'validateType',
                'skipOnEmpty' => false,
            ],
            array_merge([$this->getI18nAttributesNames(['content'])], (array)($this->contentType == 'html' && $this->htmlValidator ? $this->htmlValidator : 'safe')),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!$this->isAttributeChanged('updated_by_user_id')) {
            $this->attachBehavior('BlameableBehavior', 'davidhirtz\yii2\skeleton\behaviors\BlameableBehavior');
        }

        $this->attachBehavior('TimestampBehavior', 'davidhirtz\yii2\skeleton\behaviors\TimestampBehavior');

        if ($insert) {
            $this->position = $this->getMaxPosition() + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return UserQuery
     */
    public function getUpdated(): UserQuery
    {
        return $this->hasOne(User::class, ['id' => 'updated_by_user_id']);
    }

    /**
     * @return ActiveQuery
     */
    abstract public function findSiblings();

    /**
     * @return array
     */
    public function generateSitemapUrls(): array
    {
        $manager = Yii::$app->getUrlManager();
        $languages = $manager->i18nUrl || $manager->i18nSubdomain ? Yii::$app->getI18n()->getLanguages() : [static::getModule()->enableI18nTables ? Yii::$app->language : null];
        $urls = [];

        /** @var ActiveRecord $record */
        foreach (static::find()->each() as $record) {
            foreach ($languages as $language) {
                if ($record->includeInSitemap($language)) {
                    if ($route = $record->getRoute()) {
                        $urls [] = [
                            'loc' => $route + ['language' => $language],
                            'lastmod' => $record->updated_at,
                        ];
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * Generates a unique slug if the slug is already taken.
     */
    public function generateUniqueSlug()
    {
        foreach ($this->getI18nAttributeNames('slug') as $attributeName) {
            if ($baseSlug = $this->getAttribute($attributeName)) {
                $iteration = 1;
                while (!$this->validate($attributeName)) {
                    $this->setAttribute($attributeName, $baseSlug . '-' . $iteration++);
                }
            }
        }
    }

    /**
     * @param string|null $language
     * @return bool
     */
    public function includeInSitemap(/** @noinspection PhpUnusedParameterInspection */ $language = null): bool
    {
        return $this->isEnabled();
    }

    /**
     * @return int
     */
    public function getMaxPosition(): int
    {
        return (int)$this->findSiblings()->max('[[position]]');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name' => Yii::t('cms', 'Title'),
            'content' => Yii::t('cms', 'Content'),
            'asset_count' => Yii::t('cms', 'Assets'),
        ]);
    }
}