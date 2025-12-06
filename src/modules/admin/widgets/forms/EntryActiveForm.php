<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\helpers\FrontendLink;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\fields\CategoryParentIdSelectField;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\EntryParentIdFieldTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\MetaFieldsTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\SlugFieldTrait;
use davidhirtz\yii2\datetime\DateTimeInput;
use davidhirtz\yii2\skeleton\widgets\forms\fields\DateTimeField;
use davidhirtz\yii2\skeleton\widgets\forms\fields\Field;
use davidhirtz\yii2\skeleton\widgets\forms\fields\InputField;
use Override;
use Stringable;
use Yii;
use yii\widgets\ActiveField;

/**
 * @property Entry $model
 */
class EntryActiveForm extends ActiveForm
{
    use EntryParentIdFieldTrait;
    use MetaFieldsTrait;
    use SlugFieldTrait;

    /**
     * @return void
     */
    #[Override]
    protected function configure(): void
    {
        $this->rows ??= [
            [
                $this->getStatusField(),
                $this->getTypeField(),
                $this->getParentIdField(),
                $this->getNameField(),
                $this->getContentField(),
                $this->getPublishDateField(),
            ],
            [
                $this->getTitleField(),
                $this->getDescriptionField(),
                $this->getSlugField(),
            ]
        ];

        parent::configure();
    }

    protected function getPublishDateField(): ?Stringable
    {
        return DateTimeField::make()
            ->property('publish_date');
    }

    protected function getSlugBaseUrl(?string $language = null): string
    {
        $manager = Yii::$app->getUrlManager();
        $route = ['/', 'language' => $manager->i18nUrl || $manager->i18nSubdomain ? $language : null];
        $url = $this->model->isEnabled() ? $manager->createAbsoluteUrl($route) : $manager->createDraftUrl($route);

        return rtrim($url, '/') . '/';
    }

    protected function hasSlugField(): bool
    {
        return !$this->model->isIndex() || !$this->model->isEnabled();
    }
}
