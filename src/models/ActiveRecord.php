<?php

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\Module;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\behaviors\BlameableBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\db\I18nAttributesTrait;
use davidhirtz\yii2\skeleton\db\StatusAttributeTrait;
use davidhirtz\yii2\skeleton\db\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\models\traits\UpdatedByUserTrait;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use davidhirtz\yii2\skeleton\validators\UniqueValidator;
use davidhirtz\yii2\skeleton\web\Sitemap;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\helpers\Inflector;


/**
 * ActiveRecord is the base class for all `davidhirtz/yii2-cms` active record classes.
 *
 * @property int $id
 * @property int $status
 * @property int $type
 * @property string $content
 * @property int|false $position
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
    use UpdatedByUserTrait;

    public const SLUG_MAX_LENGTH = 100;

    public const EVENT_BEFORE_CLONE = 'beforeClone';
    public const EVENT_AFTER_CLONE = 'afterClone';

    /**
     * @var bool whether slugs should not automatically be checked and processed.
     */
    public bool $customSlugBehavior = false;

    /**
     * @var mixed used when $contentType is set to "html". Use an array with the first value containing the validator
     * class, following keys can be used to configure the validator, string containing the class name or false for
     * disabling the validation.
     */
    public array|string|null $htmlValidator = HtmlValidator::class;

    /**
     * @var string|false the content type, "html" enables html validators and WYSIWYG editor
     */
    public string|false $contentType = 'html';

    /**
     * @var array|string the class name of the unique validator
     */
    public array|string $slugUniqueValidator = UniqueValidator::class;

    /**
     * @var array|string|null {@see \yii\validators\UniqueValidator::$targetAttribute}
     */
    public array|string|null $slugTargetAttribute = null;

    /**
     * @var bool {@link ActiveRecord::isSlugRequired()}
     */
    private ?bool $_isSlugRequired = null;

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'DateTimeBehavior' => DateTimeBehavior::class,
            'TrailBehavior' => [
                'class' => TrailBehavior::class,
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
                DynamicRangeValidator::class,
                'skipOnEmpty' => false,
            ],
            [
                $this->getI18nAttributesNames(['content']),
                ...(array)($this->contentType == 'html' && $this->htmlValidator ? $this->htmlValidator : 'safe'),
            ],
        ]);
    }

    public function beforeValidate(): bool
    {
        $this->status ??= static::STATUS_DEFAULT;
        $this->type ??= static::TYPE_DEFAULT;

        return parent::beforeValidate();
    }

    public function beforeSave($insert): bool
    {
        $this->attachBehaviors([
            'BlameableBehavior' => BlameableBehavior::class,
            'TimestampBehavior' => TimestampBehavior::class,
        ]);

        if (!$this->position) {
            $this->position = $this->position !== false ? ($this->getMaxPosition() + 1) : 0;
        }

        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes): void
    {
        static::getModule()->invalidatePageCache();
        parent::afterSave($insert, $changedAttributes);
    }

    public function afterDelete(): void
    {
        static::getModule()->invalidatePageCache();
        parent::afterDelete();
    }

    /**
     * Triggers event before `$clone` was inserted. This can be used to hook into the clone process before the model is
     * validated and saved.
     *
     * @param static $clone
     */
    public function beforeClone(ActiveRecordInterface $clone): bool
    {
        $event = new ModelCloneEvent();
        $event->clone = $clone;

        $this->trigger(static::EVENT_BEFORE_CLONE, $event);
        return $event->isValid;
    }

    /**
     * Triggers event after `$clone` was inserted. This can be used to hook into the clone process after the model was
     * successfully moved or copied.
     *
     * @param static $clone
     */
    public function afterClone(ActiveRecordInterface $clone): void
    {
        $event = new ModelCloneEvent();
        $event->clone = $clone;

        $this->trigger(static::EVENT_AFTER_CLONE, $event);
    }

    /**
     * @return ActiveQuery
     */
    abstract public function findSiblings(): ActiveQuery;

    /**
     * @noinspection PhpUnused {@see Sitemap::generateUrls()}
     */
    public function generateSitemapUrls(int $offset = 0): array
    {
        $languages = $this->getSitemapLanguages();
        $sitemap = Yii::$app->sitemap;
        $urls = [];

        $query = $this->getSitemapQuery();

        if ($sitemap->useSitemapIndex) {
            $limit = $sitemap->maxUrlCount / count($languages);
            $query->limit($limit)->offset($offset * $limit);
        }

        /** @var self $record */
        foreach ($query->each() as $record) {
            foreach ($languages as $language) {
                if ($language) {
                    // Temporarily set location for I18n attributes to work
                    Yii::$app->language = $language;
                }

                if ($url = $record->getSitemapUrl($language)) {
                    $urls [] = $url;
                }
            }
        }

        return $urls;
    }

    /**
     * @return void
     */
    public function ensureSlug(string $attribute = 'name'): void
    {
        if ($this->isSlugRequired()) {
            foreach ($this->getI18nAttributeNames('slug') as $language => $attributeName) {
                if (!$this->$attributeName && ($name = $this->getI18nAttribute($attribute, $language))) {
                    $this->$attributeName = mb_substr((string)$name, 0, static::SLUG_MAX_LENGTH);
                }
            }
        }

        if (!$this->customSlugBehavior) {
            foreach ($this->getI18nAttributeNames('slug') as $attributeName) {
                $this->$attributeName = Inflector::slug($this->$attributeName);
            }
        }
    }

    /**
     * @noinspection PhpUnused {@see Sitemap::generateIndexUrls()}
     */
    public function getSitemapUrlCount(): int
    {
        $languages = $this->getSitemapLanguages();
        return $this->getSitemapQuery()->count() * count($languages);
    }

    /**
     * Returns an array of languages used for I18N URLs. This is only intended for {@link ActiveRecord::$i18nAttributes}
     * tables and not for {@link Module::$enableI18nTables} as the website structure might be different and thus rather
     * single sitemaps per language should be submitted.
     *
     * @return array
     */
    protected function getSitemapLanguages(): array
    {
        $manager = Yii::$app->getUrlManager();
        return $this->i18nAttributes && $manager->hasI18nUrls() ? array_keys($manager->languages) : [null];
    }

    /**
     * Returns an array with the attributes needed for the XML sitemap. This can be overridden to add additional fields
     * such as priority or images.
     */
    public function getSitemapUrl(string $language): array|false
    {
        if ($this->includeInSitemap($language)) {
            if ($route = $this->getRoute()) {
                return [
                    'loc' => $route + ['language' => $language],
                    'lastmod' => $this->updated_at,
                ];
            }
        }

        return false;
    }

    public function getSitemapQuery(): ActiveQuery
    {
        return static::find();
    }

    /**
     * Generates a unique slug if the slug is already taken.
     */
    public function generateUniqueSlug(): void
    {
        foreach ($this->getI18nAttributeNames('slug') as $attributeName) {
            if ($baseSlug = $this->getAttribute($attributeName)) {
                $iteration = 1;

                // Make sure the loop is limited in case a persistent error prevents the validation.
                while (!$this->validate() && $iteration < 100) {
                    $baseSlug = mb_substr((string)$baseSlug, 0, static::SLUG_MAX_LENGTH - 1 - ceil($iteration / 10), Yii::$app->charset);
                    $this->setAttribute($attributeName, $baseSlug . '-' . $iteration++);
                }
            }
        }
    }

    public function getMaxPosition(): int
    {
        return (int)$this->findSiblings()->max('[[position]]');
    }

    public static function updatePosition(array $models, array $order = [], string $attribute = 'position', ?string $index = null): int
    {
        static::getModule()->invalidatePageCache();
        return parent::updatePosition($models, $order, $attribute, $index);
    }

    public function getTrailAttributes(): array
    {
        return array_diff($this->attributes(), [
            'position',
            'asset_count',
            'entry_count',
            'updated_by_user_id',
            'updated_at',
            'created_at',
        ]);
    }

    public function getTrailModelAdminRoute(): array|false
    {
        return $this->getAdminRoute();
    }

    abstract public function getAdminRoute(): array|false;

    abstract public function getRoute(): array|false;

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function includeInSitemap(?string $language = null): bool
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

    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'entry_id' => Yii::t('cms', 'Entry'),
            'name' => Yii::t('cms', 'Title'),
            'content' => Yii::t('cms', 'Content'),
            'asset_count' => Yii::t('cms', 'Assets'),
        ]);
    }
}