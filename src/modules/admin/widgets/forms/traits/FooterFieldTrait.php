<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Cms\Models\traits\FooterAttributeTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use yii\widgets\ActiveField;

/**
 * @property FooterAttributeTrait $model
 * @mixin EntryActiveForm
 * @noinspection PhpUnused
 */
trait FooterFieldTrait
{
    protected function showInFooterField(array $options = []): string|ActiveField
    {
        if (!$this->model->hasShowInFooterEnabled() && !$this->model->isFooterItem()) {
            return '';
        }

        return $this->field($this->model, 'show_in_footer', $options)->checkbox();
    }
}
