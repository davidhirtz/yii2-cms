<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\cms\models\traits\FooterAttributeTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
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
