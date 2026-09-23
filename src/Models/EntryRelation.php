<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Cms\Models\Queries\EntryRelationQuery;
use Hirtz\Cms\Models\Traits\EntryRelationTrait;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Behaviors\BlameableBehavior;
use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Log\ActiveRecordErrorLogger;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\Traits\TrailModelTrait;
use Hirtz\Skeleton\Models\Traits\UpdatedByUserTrait;
use Hirtz\Skeleton\Validators\RelationValidator;
use Hirtz\Skeleton\Validators\UniqueValidator;
use Override;
use Yii;
use yii\base\NotSupportedException;

/**
 * One row per entry linked to a model. Every registered subclass shares this table and is dispatched on
 * `model_class`, so `updateAll()` and `deleteAll()` are unscoped and must name it themselves.
 *
 * @template TModel of (ActiveRecord&EntryRelationModelInterface) = ActiveRecord&EntryRelationModelInterface
 *
 * @property int $id
 * @property class-string<EntryRelationModelInterface> $model_class
 * @property int $model_id
 * @property int $entry_id
 * @property int $position
 * @property DateTime $updated_at
 *
 * @property-read TModel $model {@see static::getModel()}
 *
 * @mixin TrailBehavior
 */
class EntryRelation extends ActiveRecord implements TrailModelInterface
{
    use AdminModelTrait;
    use EntryRelationTrait;
    use ModuleTrait;
    use TrailModelTrait;
    use UpdatedByUserTrait;

    /**
     * @return class-string<EntryRelationModelInterface>
     */
    public static function getModelClass(): string
    {
        throw new NotSupportedException(static::class . ' must implement "getModelClass()".');
    }

    public static function getAdminControllerRoute(): string
    {
        throw new NotSupportedException(static::class . ' must implement "getAdminControllerRoute()".');
    }

    /**
     * An entry relation is only ever edited through the model it hangs on, so it answers that model's permission.
     * Read off the class rather than the record: a grid asks per row, and a bare instance has to answer as well.
     */
    public function getPermissionName(): string
    {
        return static::getModelClass()::instance()->getPermissionName();
    }

    /**
     * @param array<string, mixed> $row
     */
    #[Override]
    public static function instantiate($row): static
    {
        /** @var class-string<static> $class */
        $class = static::getModule()->getEntryRelationClass($row['model_class'] ?? null) ?? static::class;

        return $class::create();
    }

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'TrailBehavior' => TrailBehavior::class,
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['model_class', 'model_id'],
                'required',
            ],
            [
                ['model_class'],
                'in',
                'range' => array_map(
                    fn (string $class): string => $class::getModelClass(),
                    static::getModule()->getEntryRelationClasses()
                ),
            ],
            [
                ['model_id'],
                'filter',
                'filter' => 'intval',
            ],
            [
                ['entry_id'],
                RelationValidator::class,
                'required' => true,
            ],
            [
                ['entry_id'],
                UniqueValidator::class,
                'targetAttribute' => ['model_class', 'model_id', 'entry_id'],
            ],
            [
                ['entry_id'],
                $this->validateEntry(...),
            ],
        ];
    }

    protected function validateEntry(): void
    {
        if ($this->hasErrors('entry_id')) {
            return;
        }

        $allowedTypes = $this->model->getEntriesTypes();

        if ($allowedTypes && !in_array($this->entry->type, $allowedTypes, true)) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    #[Override]
    public function beforeValidate(): bool
    {
        if (static::class !== self::class) {
            $this->model_class ??= static::getModelClass();
        }

        return parent::beforeValidate();
    }

    #[Override]
    public function afterValidate(): void
    {
        if (!$this->getIsNewRecord()) {
            foreach (['model_class', 'model_id'] as $attribute) {
                if ($this->isAttributeChanged($attribute)) {
                    $this->addInvalidAttributeError($attribute);
                }
            }
        }

        parent::afterValidate();
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        $this->attachBehaviors([
            'BlameableBehavior' => BlameableBehavior::class,
            'TimestampBehavior' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => null,
            ],
        ]);

        $this->position ??= $this->getMaxPosition() + 1;

        return parent::beforeSave($insert);
    }

    /**
     * @param array<string, mixed> $changedAttributes
     */
    #[Override]
    public function afterSave($insert, $changedAttributes): void
    {
        if ($insert && !$this->getIsBatch()) {
            $this->updateModelEntryCount();
            static::getModule()->invalidatePageCache();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    #[Override]
    public function afterDelete(): void
    {
        if (!$this->getIsBatch()) {
            if (!$this->model->isDeleted()) {
                $this->updateModelEntryCount();
            }

            static::getModule()->invalidatePageCache();
        }

        parent::afterDelete();
    }

    public function updateModelEntryCount(): bool|int
    {
        $model = $this->model;
        $model->recalculateEntryCount();

        if ($model->update(false) === false) {
            ActiveRecordErrorLogger::log($model);
            return false;
        }

        return true;
    }

    /**
     * The base is unscoped and returns mixed subclasses from one query; every subclass is scoped to its own
     * `model_class`, which is what makes `findOne()`, the relations and the eager load safe.
     *
     * @return EntryRelationQuery<static>
     */
    #[Override]
    public static function find(): EntryRelationQuery
    {
        /** @var EntryRelationQuery<static> $query */
        $query = Yii::createObject(EntryRelationQuery::class, [static::class]);

        return static::class === self::class ? $query : $query->whereModelClass(static::getModelClass());
    }

    /**
     * @return EntryRelationQuery<static>
     */
    public function findSiblings(): EntryRelationQuery
    {
        return static::find()->andWhere([
            'model_class' => $this->model_class,
            'model_id' => $this->model_id,
        ]);
    }

    public function getMaxPosition(): int
    {
        return (int)$this->findSiblings()->max('[[position]]');
    }

    /**
     * @return TModel
     */
    public function getModel(): EntryRelationModelInterface
    {
        if (!$this->isRelationPopulated('model')) {
            $this->populateRelation('model', Yii::createObject($this->model_class)::findOne($this->model_id));
        }

        /** @var TModel */
        return $this->getRelatedRecords()['model'];
    }

    public function populateModelRelation(EntryRelationModelInterface $model): void
    {
        $this->populateRelation('model', $model);
        $this->model_class = $model->getEntryRelationClass()::getModelClass();
        $this->model_id = $model->id;
    }

    /**
     * @noinspection PhpUnused
     * @return list<string>
     */
    public function getTrailAttributes(): array
    {
        return array_values(array_diff($this->attributes(), [
            'id',
            'position',
            'updated_by_user_id',
            'updated_at',
            'created_at',
        ]));
    }

    /**
     * @noinspection PhpUnused
     * @return list<TrailModelInterface>
     */
    public function getTrailParents(): ?array
    {
        return [$this->entry, $this->model];
    }

    /**
     * @return array<int|string, mixed>|false
     */
    public function getAdminRoute(): array|false
    {
        return false;
    }

    public function getAdminName(): string
    {
        return Yii::t('cms', 'ENTRY_RELATION_ENTRY_RELATION');
    }

    public function getAdminType(): string
    {
        return Yii::t('skeleton', 'COMMON_RELATION');
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'model_class' => Yii::t('cms', 'ENTRY_RELATION_MODEL_CLASS_LABEL'),
            'model_id' => Yii::t('skeleton', 'COMMON_ID_LABEL'),
            'entry_id' => Yii::t('cms', 'ENTRY_RELATION_ENTRY_ID_LABEL'),
            'updated_at' => Yii::t('cms', 'ENTRY_RELATION_UPDATED_AT_LABEL'),
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'EntryRelation';
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%entry_relation}}';
    }
}
