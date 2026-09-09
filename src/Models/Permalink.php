<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Cms\Models\Queries\PermalinkQuery;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Validators\UniqueValidator;
use Override;
use Yii;

/**
 * The resolvable URL of an {@see Entry} or {@see Category}, one record per model and language.
 *
 * `uri` holds the full path the request is matched against, `slug` only the leaf segment the editor
 * edits on the related model.
 *
 * @property int $id
 * @property string $language
 * @property string $uri
 * @property string $slug
 * @property string $model
 * @property int $model_id
 * @property int|null $parent_id
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 *
 * @property-read static|null $parent {@see static::getParent()}
 */
class Permalink extends ActiveRecord
{
    use ModuleTrait;

    /**
     * @var array<int, string> the attributes a permalink must be unique by, extended by `yii2-cms-tenant`.
     */
    public array $uriTargetAttribute = ['language', 'uri'];

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
                ['language', 'uri', 'slug', 'model', 'model_id'],
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
                ['model'],
                'string',
                'max' => 255,
            ],
            [
                ['uri'],
                UniqueValidator::class,
                'targetAttribute' => $this->uriTargetAttribute,
            ],
            [
                ['model_id'],
                UniqueValidator::class,
                'targetAttribute' => ['model', 'model_id', 'language'],
            ],
            [
                ['uri'],
                $this->validateUri(...),
            ],
        ];
    }

    /**
     * Prevents a permalink from shadowing a static route or a file in the web root, which would leave the record
     * unreachable without any error.
     */
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
            $this->addError('uri', Lang::t('cms', 'PERMALINK_PROTECTED_ERROR', [
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

    public function getParent(): PermalinkQuery
    {
        /** @var PermalinkQuery $relation */
        $relation = $this->hasOne(static::class, ['id' => 'parent_id']);
        return $relation;
    }

    public function populateParentRelation(?self $parent): void
    {
        $this->populateRelation('parent', $parent);
        $this->parent_id = $parent?->id;
    }

    /**
     * @return PermalinkQuery<static>
     */
    #[Override]
    public static function find(): PermalinkQuery
    {
        return Yii::createObject(PermalinkQuery::class, [static::class]);
    }

    /**
     * @param class-string $class
     */
    public function isModel(string $class): bool
    {
        return is_a($this->model, $class, true);
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'uri' => Lang::t('cms', 'PERMALINK_URI_LABEL'),
            'slug' => Lang::t('cms', 'PERMALINK_SLUG_LABEL'),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return static::getModule()->getTableName('permalink');
    }
}
