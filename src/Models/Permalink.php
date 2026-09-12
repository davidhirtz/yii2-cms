<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Queries\PermalinkQuery;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Validators\UniqueValidator;
use Override;
use Yii;

/**
 * The resolvable URL of an {@see Entry}, one record per entry and language.
 *
 * `uri` holds the full path the request is matched against, `slug` only the leaf segment the editor
 * edits on the related model. `tenant_id` is denormalized from the entry so the unique index can see it.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $entry_id
 * @property string $language
 * @property string $uri
 * @property string $slug
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 *
 * @property-read Entry $entry {@see static::getEntry()}
 */
class Permalink extends ActiveRecord
{
    use ModuleTrait;

    final public const string LANGUAGE_ALL = '*';

    /**
     * @var array<int, string>
     */
    public array $uriTargetAttribute = ['tenant_id', 'language', 'uri'];

    public int $slugMaxLength = 100;
    public int $uriMaxLength = 255;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'DateTimeBehavior' => DateTimeBehavior::class,
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['tenant_id', 'entry_id', 'language', 'uri', 'slug'],
                'required',
            ],
            [
                ['uri', 'slug'],
                'trim',
            ],
            [
                ['language'],
                'string',
                'max' => 16,
            ],
            [
                ['slug'],
                'string',
                'max' => $this->slugMaxLength,
            ],
            [
                ['uri'],
                'string',
                'max' => $this->uriMaxLength,
            ],
            [
                ['uri'],
                UniqueValidator::class,
                'targetAttribute' => $this->uriTargetAttribute,
            ],
            [
                ['entry_id'],
                UniqueValidator::class,
                'targetAttribute' => ['entry_id', 'language'],
            ],
            [
                ['uri'],
                $this->validateUri(...),
            ],
        ];
    }

    protected function validateUri(): void
    {
        if ($this->hasErrors('uri')) {
            return;
        }

        $param = explode('/', $this->uri)[0];
        $path = Yii::getAlias("@webroot/$this->uri");

        if (
            in_array($param, Yii::$app->getUrlManager()->getImmutableRuleParams(), true)
            || is_dir($path)
            || is_file($path)
        ) {
            $this->addError('uri', Yii::t('cms', 'PERMALINK_PROTECTED_ERROR', [
                'uri' => $this->uri,
            ]));
        }
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        $this->attachBehaviors([
            'TimestampBehavior' => TimestampBehavior::class,
        ]);

        return parent::beforeSave($insert);
    }

    /**
     * @return PermalinkQuery<static>
     */
    #[Override]
    public static function find(): PermalinkQuery
    {
        return Yii::createObject(PermalinkQuery::class, [static::class]);
    }

    public function getEntry(): EntryQuery
    {
        /** @var EntryQuery $relation */
        $relation = $this->hasOne(Entry::class, ['id' => 'entry_id']);
        return $relation;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'tenant_id' => Yii::t('cms', 'PERMALINK_TENANT_ID_LABEL'),
            'entry_id' => Yii::t('cms', 'PERMALINK_ENTRY_ID_LABEL'),
            'uri' => Yii::t('cms', 'PERMALINK_URI_LABEL'),
            'slug' => Yii::t('cms', 'PERMALINK_SLUG_LABEL'),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%permalink}}';
    }
}
