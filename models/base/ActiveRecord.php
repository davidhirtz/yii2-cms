<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\db\I18nAttributesTrait;
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
     * @var string
     */
    public $htmlValidator='davidhirtz\yii2\skeleton\validators\HtmlValidator';

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
            'DateTimeBehavior' => [
                'class' => DateTimeBehavior::class,
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
                'range' => array_keys(static::getTypes()) ?: [static::TYPE_DEFAULT],
            ],
            [
                ['content'],
                $this->contentType=='html' ? $this->htmlValidator : 'safe',
            ],
        ]);
    }

    /**
     * @return bool
     */
    public function beforeValidate()
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
            'TimestampBehavior' => 'davidhirtz\yii2\skeleton\behaviors\TimestampBehavior',
            'BlameableBehavior' => 'davidhirtz\yii2\skeleton\behaviors\BlameableBehavior',
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
                'name' => Yii::t('skeleton', 'Enabled'),
                'icon' => 'globe',
            ],
            static::STATUS_DISABLED => [
                'name' => Yii::t('skeleton', 'Disabled'),
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('skeleton', 'ID'),
            'status' => Yii::t('skeleton', 'Status'),
            'type' => Yii::t('skeleton', 'Type'),
            'name' => Yii::t('cms', 'Title'),
            'position' => Yii::t('cms', 'Order'),
            'content' => Yii::t('cms', 'Content'),
            'file_count' => Yii::t('cms', 'Files'),
            'updated_by_user_id' => Yii::t('skeleton', 'User'),
            'updated_at' => Yii::t('skeleton', 'Last Update'),
            'created_at' => Yii::t('skeleton', 'Created'),
        ];
    }
}