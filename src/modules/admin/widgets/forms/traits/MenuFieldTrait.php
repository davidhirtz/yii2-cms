<?php

declare(strict_types=1);

namespace Hirtz\Cms\modules\admin\widgets\forms\traits;

use Hirtz\Cms\models\traits\MenuAttributeTrait;
use Hirtz\Cms\modules\admin\widgets\forms\EntryActiveForm;
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
                    $options['checkOptions']['class'] ??= 'form-check-input';
                    $options['checkOptions']['labelOptions'] ??= [
                        'class' => 'form-check-label text-invalid',
                        'data-tooltip' => '',
                        'title' => Yii::t('cms', "Parent entry \"{entry}\" is not a menu item", [
                            'entry' => $ancestor->getI18nAttribute('name'),
                        ]),
                    ];
                    break;
                }
            }
        }

        return $options;
    }
}
