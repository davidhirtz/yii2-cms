<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\cms\models\traits\MenuAttributeTrait;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\EntryActiveForm;
use Yii;
use yii\widgets\ActiveField;

/**
 * @property MenuAttributeTrait $model
 * @mixin EntryActiveForm
 */
trait MenuFieldTrait
{
    protected function showInMenuField(array $options = []): string|ActiveField
    {
        if (!$this->model->hasShowInMenuEnabled() && !$this->model->isMenuItem()) {
            return '';
        }

        $options = $this->getShowInMenuOptions($options);

        return $this->field($this->model, 'show_in_menu', $options)->checkbox();
    }

    protected function getShowInMenuOptions(array $options = []): array
    {
        if ($this->model->parent_id) {
            foreach ($this->model->ancestors as $ancestor) {
                if (!$ancestor->isMenuItem()) {
                    $options['options']['class'] = 'form-group row disabled';
                    $options['options']['title'] = Yii::t('cms', "Parent entry \"{entry}\" is not a menu item", [
                        'entry' => $ancestor->getI18nAttribute('name'),
                    ]);
                    break;
                }
            }
        }

        return $options;
    }
}
