<?php

declare(strict_types=1);

namespace Hirtz\Cms\models;

use Hirtz\Cms\models\traits\SitemapTrait;
use Hirtz\Cms\models\traits\VisibleAttributeTrait;
use Hirtz\Cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\datetime\DateTimeBehavior;
use Hirtz\Skeleton\behaviors\BlameableBehavior;
use Hirtz\Skeleton\behaviors\TimestampBehavior;
use Hirtz\Skeleton\behaviors\TrailBehavior;
use Hirtz\Skeleton\db\ActiveQuery;
use Hirtz\Skeleton\db\ActiveRecord as BaseActiveRecord;
use Hirtz\Skeleton\models\interfaces\DraftStatusAttributeInterface;
use Hirtz\Skeleton\models\interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\models\interfaces\TrailModelInterface;
use Hirtz\Skeleton\models\interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\models\traits\DraftStatusAttributeTrait;
use Hirtz\Skeleton\models\traits\I18nAttributesTrait;
use Hirtz\Skeleton\models\traits\TrailModelTrait;
use Hirtz\Skeleton\models\traits\TypeAttributeTrait;
use Hirtz\Skeleton\models\traits\UpdatedByUserTrait;
use Hirtz\Skeleton\validators\DynamicRangeValidator;
use Hirtz\Skeleton\validators\HtmlValidator;
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
abstract class ActiveRecord extends BaseActiveRecord implements
    DraftStatusAttributeInterface,
    I18nAttributeInterface,
    TrailModelInterface,
    TypeAttributeInterface
{
    use DraftStatusAttributeTrait;
    use I18nAttributesTrait;
    use ModuleTrait;
    use SitemapTrait;
    use TrailModelTrait;
    use TypeAttributeTrait;
    use UpdatedByUserTrait;
    use VisibleAttributeTrait;

    /**
     * @var string|false the content type, "html" enables HTML validators and WYSIWYG editor
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
                ...(array)($this->contentType === 'html' && $this->htmlValidator ? $this->htmlValidator : 'safe'),
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
