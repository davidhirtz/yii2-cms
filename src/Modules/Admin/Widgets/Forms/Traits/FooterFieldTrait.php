<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Traits\FooterAttributeTrait;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use Stringable;

/**
 * @property Entry&FooterAttributeTrait $model
 */
trait FooterFieldTrait
{
    protected function getShowInFooterField(): ?Stringable
    {
        if (!$this->model->hasShowInFooterEnabled() && !$this->model->isFooterItem()) {
            return null;
        }

        return CheckboxField::make()
            ->property('show_in_footer');
    }
}
