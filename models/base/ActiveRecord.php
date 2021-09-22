<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\db\I18nAttributesTrait;
use davidhirtz\yii2\skeleton\db\StatusAttributeTrait;
use davidhirtz\yii2\skeleton\db\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;
use Yii;


/**
 * Class ActiveRecord
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
    use I18nAttributesTrait;
    use ModuleTrait;
    use StatusAttributeTrait;
    use TypeAttributeTrait;

    public const SLUG_MAX_LENGTH = 100;

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
     * @var string|false the content type, "html" enables html validators and WYSIWYG editor
     */
    public $contentType = 'html';

    /**
     * @var string the class name of the unique validator
     */
    public $slugUniqueValidator = 'davidhirtz\yii2\skeleton\validators\UniqueValidator';

    /**
     * @var string|array {@see \yii\validators\UniqueValidator::$targetAttribute}
     */
    public $slugTargetAttribute;

    /**
     * @var bool {@link ActiveRecord::isSlugRequired()}
     */
    private $_isSlugRequired;

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'DateTimeBehavior' => 'davidhirtz\yii2\datetime\DateTimeBehavior',
            'TrailBehavior' => [
                'class' => 'davidhirtz\yii2\skeleton\behaviors\TrailBehavior',
                'modelClass' => static::class . (static::getModule()->enableI18nTables ? ('::' . Yii::$app->language) : ''),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [
                ['status', 'type'],
                'davidhirtz\yii2\skeleton\validators\DynamicRangeValidator',
                'skipOnEmpty' => false,
            ],
            array_merge(
                [$this->getI18nAttributesNames(['content'])],
                (array)($this->contentType == 'html' && $this->htmlValidator ? $this->htmlValidator : 'safe')
            ),
        ]);
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if ($this->status === null) {
            $this->status = static::STATUS_DEFAULT;
        }

        if ($this->type === null) {
            $this->type = static::TYPE_DEFAULT;
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritDoc
     */
    public function beforeSave($insert)
    {
        $this->attachBehaviors([
            'BlameableBehavior' => 'davidhirtz\yii2\skeleton\behaviors\BlameableBehavior',
            'TimestampBehavior' => 'davidhirtz\yii2\skeleton\behaviors\TimestampBehavior',
        ]);

        if ($this->position === null) {
            $this->position = $this->getMaxPosition() + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return UserQuery
     */
    public function getUpdated(): UserQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(User::class, ['id' => 'updated_by_user_id']);
    }

    /**
     * @return ActiveQuery
     */
    abstract public function findSiblings();

    /**
     * @param int $offset
     * @return array
     */
    public function generateSitemapUrls($offset = 0): array
    {
        $manager = Yii::$app->getUrlManager();
        $sitemap = Yii::$app->sitemap;
        $languages = static::getModule()->enableI18nTables || !$manager->hasI18nUrls() ? [Yii::$app->language] : Yii::$app->getI18n()->getLanguages();
        $urls = [];

        $query = $this->getSitemapQuery();

        if ($sitemap->useSitemapIndex) {
            $limit = $sitemap->maxUrlCount / count($languages);
            $query->limit($limit)->offset($offset * $limit);
        }

        /** @var self $record */
        foreach ($query->each() as $record) {
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
     * @return ActiveQuery
     */
    public function getSitemapQuery()
    {
        return static::find();
    }

    /**
     * Generates a unique slug if the slug is already taken.
     */
    public function generateUniqueSlug()
    {
        foreach ($this->getI18nAttributeNames('slug') as $attributeName) {
            if ($baseSlug = $this->getAttribute($attributeName)) {
                $iteration = 1;

                // Make sure the loop is limited in case a persistent error prevents the validation.
                while (!$this->validate() && $iteration < 100) {
                    $baseSlug = mb_substr($baseSlug, 0, static::SLUG_MAX_LENGTH - 1 - ceil($iteration / 10), Yii::$app->charset);
                    $this->setAttribute($attributeName, $baseSlug . '-' . $iteration++);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getTrailAttributes(): array
    {
        return array_diff($this->attributes(), [
            'position',
            'asset_count',
            'updated_by_user_id',
            'updated_at',
            'created_at',
        ]);
    }

    /**
     * @return int
     */
    public function getMaxPosition(): int
    {
        return (int)$this->findSiblings()->max('[[position]]');
    }

    /**
     * @return array|false
     */
    public function getTrailModelAdminRoute()
    {
        return $this->getAdminRoute();
    }

    /**
     * @return mixed
     */
    abstract public function getAdminRoute();

    /**
     * @return mixed
     */
    abstract public function getRoute();

    /**
     * @param string|null $language
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function includeInSitemap($language = null): bool
    {
        return $this->isEnabled();
    }

    /**
     * @return bool whether slugs are required, override this method to not rely on db schema.
     */
    public function isSlugRequired(): bool
    {
        if ($this->_isSlugRequired === null) {
            $schema = static::getDb()->getSchema();
            $this->_isSlugRequired = !$schema->getTableSchema(static::tableName())->getColumn('slug')->allowNull;
        }

        return $this->_isSlugRequired;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'entry_id' => Yii::t('cms', 'Entry'),
            'name' => Yii::t('cms', 'Title'),
            'content' => Yii::t('cms', 'Content'),
            'asset_count' => Yii::t('cms', 'Assets'),
        ]);
    }
}