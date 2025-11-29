<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\CategoryParentIdFieldTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\MetaFieldsTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\SlugFieldTrait;
use Override;
use Yii;

/**
 * @property Category $model
 */
class CategoryActiveForm extends ActiveForm
{
    use CategoryParentIdFieldTrait;
    use MetaFieldsTrait;
    use SlugFieldTrait;

    #[Override]
    public function configure(): void
    {
        $this->rows ??= [
            [
                $this->getStatusField(),
                $this->getParentIdField(),
                $this->getTypeField(),
                $this->getNameField(),
                $this->getContentField(),
            ],
            [
                $this->getTitleField(),
                $this->getDescriptionField(),
                $this->getSlugField(),
            ],
        ];

        parent::configure();
    }

    protected function getSlugBaseUrl(?string $language = null): string
    {
        if (!$this->model->getRoute()) {
            return '';
        }

        return Yii::$app->getI18n()->callback($language, function (): string {
            $route = [...$this->model->getRoute(), 'category' => ''];
            $url = Yii::$app->getUrlManager()->createAbsoluteUrl($route);
            $url = rtrim($url, '/');

            if (!str_ends_with($url, '=')) {
                $url .= '/';
            }

            return $url;
        });
    }
}
