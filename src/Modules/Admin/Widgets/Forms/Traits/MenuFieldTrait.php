<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Traits\MenuAttributeTrait;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use Stringable;
use Yii;

/**
 * @property Entry&MenuAttributeTrait $model
 */
trait MenuFieldTrait
{
    protected function getShowInMenuField(): ?Stringable
    {
        if (!$this->model->hasShowInMenuEnabled() && !$this->model->isMenuItem()) {
            return null;
        }

        $field = CheckboxField::make()
            ->property('show_in_menu');

        if ($this->model->parent_id) {
            foreach ($this->model->ancestors as $ancestor) {
                if (!$ancestor->isMenuItem()) {
                    return $field->addClass('text-invalid')
                        ->tooltip(Yii::t('cms', "Parent entry \"{entry}\" is not a menu item", [
                            'entry' => $ancestor->getI18nAttribute('name'),
                        ]));
                }
            }
        }

        return $field;
    }
}
