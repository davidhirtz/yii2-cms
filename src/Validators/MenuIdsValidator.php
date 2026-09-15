<?php

declare(strict_types=1);

namespace Hirtz\Cms\Validators;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Override;
use yii\base\NotSupportedException;
use yii\validators\Validator;

/**
 * Normalizes the posted list into a `list<int>|null` of declared menus. An undeclared or unavailable menu is
 * dropped rather than reported: the declaration is project configuration a record cannot answer for, and a
 * menu that stops being available must not lock every later save of the entries already in it.
 */
class MenuIdsValidator extends Validator
{
    use ModuleTrait;

    /** @var string[] */
    public $attributes = ['menu_ids'];
    public $skipOnEmpty = false;

    /**
     * @param Entry $model
     */
    #[Override]
    public function validateAttribute($model, $attribute): void
    {
        $menuIds = [];

        foreach ((array)$model->getAttribute($attribute) as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $menu = static::getModule()->findMenu((int)$value);

            if ($menu && $menu->isAvailableOrStored($model)) {
                $menuIds[$menu->value] = $menu->value;
            }
        }

        $model->setAttribute($attribute, $menuIds ? array_values($menuIds) : null);
    }

    #[Override]
    public function validate($value, &$error = null): bool
    {
        throw new NotSupportedException(static::class . ' does not support validate().');
    }
}
