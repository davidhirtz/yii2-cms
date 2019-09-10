<?php

namespace davidhirtz\yii2\cms\models\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\User;

/**
 * Class EntryCategory.
 * @package davidhirtz\yii2\cms\models\base
 *
 * @property int $entry_id
 * @property int $category_id
 * @property int $position
 * @property int $updated_by_user_id
 * @property DateTime $updated_at
 * @property DateTime $created_at
 * @property Entry $entry
 * @property Category $category
 * @property User $updated
 *
 * @method static \davidhirtz\yii2\cms\models\EntryCategory findOne($condition)
 */
class EntryCategory extends \davidhirtz\yii2\skeleton\db\ActiveRecord
{
    use ModuleTrait;

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->attachBehaviors([
            'TimestampBehavior' => [
                'class' => 'davidhirtz\yii2\skeleton\behaviors\TimestampBehavior',
                'createdAtAttribute' => null,
            ],
            'BlameableBehavior' => 'davidhirtz\yii2\skeleton\behaviors\BlameableBehavior',
        ]);

        if ($insert) {
            $this->position = $this->findSiblings()->max('[[position]]') + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return \davidhirtz\yii2\skeleton\db\ActiveQuery
     */
    public function findSiblings()
    {
        return self::find()->where(['category_id' => $this->category_id]);
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return 'EntryCategory';
    }

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return static::getModule()->getTableName('entry_category');
    }
}