<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models;

use davidhirtz\yii2\cms\models\traits\SitemapTrait;
use davidhirtz\yii2\cms\models\traits\VisibleAttributeTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use davidhirtz\yii2\skeleton\behaviors\BlameableBehavior;
use davidhirtz\yii2\skeleton\behaviors\TimestampBehavior;
use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\db\ActiveRecord as BaseActiveRecord;
use davidhirtz\yii2\skeleton\models\interfaces\DraftStatusAttributeInterface;
use davidhirtz\yii2\skeleton\models\interfaces\TypeAttributeInterface;
use davidhirtz\yii2\skeleton\models\traits\DraftStatusAttributeTrait;
use davidhirtz\yii2\skeleton\models\traits\I18nAttributesTrait;
use davidhirtz\yii2\skeleton\models\traits\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\models\traits\UpdatedByUserTrait;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use davidhirtz\yii2\skeleton\validators\HtmlValidator;
use Yii;

/**
 * @property int $id
 * @property int $status
 * @property int $type
 * @property string|null $content
 * @property int|false|null $position
 * @property int|null $updated_by_user_id
 * @property DateTime|null $updated_at
 * @property DateTime $created_at
 *
 * @mixin TrailBehavior
 */
abstract class ActiveRecord extends BaseActiveRecord implements DraftStatusAttributeInterface, TypeAttributeInterface
{
    use DraftStatusAttributeTrait;
    use I18nAttributesTrait;
    use ModuleTrait;
    use SitemapTrait;
    use TypeAttributeTrait;
    use UpdatedByUserTrait;
    use VisibleAttributeTrait;

    /**
     * @var string|false the content type, "html" enables html validators and WYSIWYG editor
     */
    public string|false $contentType = 'html';

    /**
     * @var array|string|null used when `$contentType` is set to "html". Use an array with the first value containing a
     * validator class, following keys can be used to configure the validator, string containing the class name or null
     * for disabling the validation.
     */
    public array|string|null $htmlValidator = HtmlValidator::class;

    #[\Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'DateTimeBehavior' => DateTimeBehavior::class,
            'TrailBehavior' => [
                'class' => TrailBehavior::class,
                'modelClass' => static::getModule()->getI18nClassName(static::class),
            ],
        ];
    }

    #[\Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getTraitRules(),
            [
                ['status', 'type'],
                DynamicRangeValidator::class,
                'skipOnEmpty' => false,
            ],
            [
                $this->getI18nAttributesNames(['content']),
                ...(array)($this->contentType == 'html' && $this->htmlValidator ? $this->htmlValidator : 'safe'),
            ],
            [
                $this->getI18nAttributesNames(['content']),
                'string',
                'max' => 65535,
            ],
        ];
    }

    #[\Override]
    public function beforeValidate(): bool
    {
        $this->status ??= static::STATUS_DEFAULT;
        $this->type ??= static::TYPE_DEFAULT;

        return parent::beforeValidate();
    }

    #[\Override]
    public function beforeSave($insert): bool
    {
        $this->attachBehaviors([
            'BlameableBehavior' => BlameableBehavior::class,
            'TimestampBehavior' => TimestampBehavior::class,
        ]);

        $this->setDefaultPosition();

        return parent::beforeSave($insert);
    }

    #[\Override]
    public function afterSave($insert, $changedAttributes): void
    {
        static::getModule()->invalidatePageCache();
        parent::afterSave($insert, $changedAttributes);
    }

    #[\Override]
    public function afterDelete(): void
    {
        static::getModule()->invalidatePageCache();
        parent::afterDelete();
    }

    abstract public function findSiblings(): ActiveQuery;

    protected function setDefaultPosition(): void
    {
        if (!$this->position) {
            $this->position = $this->position !== false ? ($this->getMaxPosition() + 1) : 0;
        }
    }

    public function getMaxPosition(): int
    {
        return (int)$this->findSiblings()->max('[[position]]');
    }

    public function getCssClass(): string
    {
        return $this->getTypeOptions()['cssClass'] ?? '';
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

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            ...$this->getTraitAttributeLabels(),
            'entry_id' => Yii::t('cms', 'Entry'),
            'name' => Yii::t('cms', 'Title'),
            'content' => Yii::t('cms', 'Content'),
            'asset_count' => Yii::t('media', 'Assets'),
        ];
    }
}
