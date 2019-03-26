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
            [
                $this->getI18nAttributeNames(['content']),
                $this->contentType=='html' ? $this->htmlValidator : 'safe',
            ],
        ]);
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

        if ($insert) {
            $this->position = $this->findSiblings()->max('[[position]]') + 1;
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