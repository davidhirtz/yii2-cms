<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\BlockIdSelectField;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\ActiveFormFieldsTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits\SlugFieldTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Traits\CustomAttributeFieldsTrait;
use Override;
use Stringable;
use Yii;

/**
 * @property Section $model
 */
class SectionActiveForm extends ActiveForm
{
    use ActiveFormFieldsTrait;
    use CustomAttributeFieldsTrait;
    use SlugFieldTrait;

    protected int|false $maxBaseUrlLength = 70;

    public function maxBaseUrlLength(int|false $maxBaseUrlLength): static
    {
        $this->maxBaseUrlLength = $maxBaseUrlLength;
        return $this;
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        return [
            $this->getStatusField(),
            $this->getTypeField(),
            $this->getBlockIdField(),
            ...$this->getCustomAttributeFields(except: ['slug']),
            $this->getSlugField(),
        ];
    }

    protected function getBlockIdField(): ?Stringable
    {
        return BlockIdSelectField::make();
    }

    #[Override]
    public function getSlugBaseUrl(?string $language = null): string
    {
        $entryRoute = $this->model->entry->getRoute();

        if (!$entryRoute) {
            return '';
        }

        $manager = Yii::$app->getUrlManager();

        $route = [
            ...$entryRoute,
            'language' => $manager->i18nUrl ? $language : null,
            '#' => '',
        ];

        $isDraft = in_array(Entry::STATUS_DRAFT, [
            $this->model->entry->status,
            $this->model->entry->parent_status,
        ], true);

        return $isDraft ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);
    }

    /**
     * Cut before the `#`, so the anchor the slug adds stays in sight.
     */
    protected function formatSlugBaseUrl(string $url): string
    {
        return $this->maxBaseUrlLength && mb_strlen($url) > $this->maxBaseUrlLength
            ? mb_substr($url, 0, $this->maxBaseUrlLength) . '…#'
            : $url;
    }

    protected function hasSlugField(): bool
    {
        return $this->model->entry->getRoute() !== false;
    }
}
