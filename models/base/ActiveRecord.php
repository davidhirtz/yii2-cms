<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\components\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\db\I18nAttributesTrait;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;


/**
 * Class ActiveRecord.
 * @package davidhirtz\yii2\cms\models\base
 *
 * @property int $id
 * @property int $status
 * @property int $type
 * @property int $updated_by_user_id
 * @property DateTime $updated_at
 * @property DateTime $created_at
 * @property User $updated
 */
abstract class ActiveRecord extends \davidhirtz\yii2\skeleton\db\ActiveRecord
{
    use ModuleTrait, I18nAttributesTrait;

    /**
     * Constants.
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    const TYPE_DEFAULT = 1;

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'DateTimeBehavior' => [
                'class' => DateTimeBehavior::class,
            ],
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
                'required',
            ],
            [
                ['status', 'type'],
                'filter',
                'filter' => 'intval',
            ],
            [
                ['status'],
                'in',
                'range' => array_keys(static::getStatuses()),
            ],
            [
                ['type'],
                'in',
                'range' => array_keys(static::getTypes()),
            ],
        ]);
    }

    /**
     * @return UserQuery
     */
    public function getUpdated(): UserQuery
    {
        return $this->hasOne(User::class, ['id' => 'updated_by_user_id']);
    }

    /**
     * @return bool
     */
    public function afterValidate()
    {
        if (!$this->type) {
            $this->type = static::TYPE_DEFAULT;
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->attachBehaviors([
            'TimestampBehavior' => [
                'class' => TimestampBehavior::class,
                'value' => function () {
                    return new DateTime;
                },
            ],
            'BlameableBehavior' => [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    static::EVENT_BEFORE_INSERT => ['updated_by_user_id'],
                    static::EVENT_BEFORE_UPDATE => ['updated_by_user_id'],
                ],
            ],
        ]);

        return parent::beforeSave($insert);
    }

    /**
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            static::STATUS_ENABLED => [
                'name' => Yii::t('app', 'Enabled'),
                'icon' => 'globe',
            ],
            static::STATUS_DISABLED => [
                'name' => Yii::t('app', 'Disabled'),
                'icon' => 'lock',
            ],
        ];
    }

    /**
     * @return string|null
     */
    public function getStatusName(): string
    {
        return $this->status ? static::getStatuses()[$this->status]['name'] : null;
    }

    /**
     * @return string|null
     */
    public function getStatusIcon(): string
    {
        return $this->status ? static::getStatuses()[$this->status]['icon'] : null;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->status == static::STATUS_ENABLED;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->status == static::STATUS_DISABLED;
    }

    /**
     * @return array
     */
    public static function getTypes(): array
    {
        return [];
    }

    /**
     * @return string|null
     */
    public function getTypeName(): string
    {
        return $this->type ? static::getTypes()[$this->type]['name'] : null;
    }
}