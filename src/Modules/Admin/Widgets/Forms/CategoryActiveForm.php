<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ActiveFormFieldsTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\CategoryParentIdFieldTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\MetaFieldsTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\SlugFieldTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Override;
use Yii;

/**
 * @property Category $model
 */
class CategoryActiveForm extends ActiveForm
{
    use ActiveFormFieldsTrait;
    use CategoryParentIdFieldTrait;
    use MetaFieldsTrait;
    use SlugFieldTrait;

    #[Override]
    protected function configure(): void
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
