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
use yii\db\ExpressionInterface;


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
     * @var string the class name of the unique validator, defaults to Yii default
     */
    public $slugUniqueValidator = 'unique';

    /**
     * @var string|array {@see \yii\validators\UniqueValidator::$targetAttribute}
     */
    public $slugTargetAttribute;

    /**
     * @var bool {@link ActiveRecord::isSlugRequired()}
     */
    private $_isSlugRequired;

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
     * @param int|ExpressionInterface|null $offset
     * @return array
     */
    public function generateSitemapUrls($offset = null): array
    {
        $manager = Yii::$app->getUrlManager();
        $languages = $manager->i18nUrl || $manager->i18nSubdomain ? Yii::$app->getI18n()->getLanguages() : [static::getModule()->enableI18nTables ? Yii::$app->language : null];
        $query = $this->getSitemapQuery()->limit($offset);
        $urls = [];

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
                $baseSlug = mb_substr($baseSlug, 0, static::SLUG_MAX_LENGTH);
                $iteration = 1;

                // Make sure the loop is limited in case a persistent error prevents the validation.
                while (!$this->validate($attributeName) && $iteration < 100) {
                    $baseSlug = mb_substr($baseSlug, 0, static::SLUG_MAX_LENGTH - ceil($iteration / 10));
                    $this->setAttribute($attributeName, $baseSlug . '-' . $iteration++);
                }
            }
        }
    }

    /**
     * @return int
     */
    public function getMaxPosition(): int
    {
        return (int)$this->findSiblings()->max('[[position]]');
    }

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
            'name' => Yii::t('cms', 'Title'),
            'content' => Yii::t('cms', 'Content'),
            'asset_count' => Yii::t('cms', 'Assets'),
        ]);
    }
}