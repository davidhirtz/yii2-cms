# Upgrade Guide

## 3.0.0 — Menu and footer attributes are wired up by hand

`Models\ActiveRecord::rules()` and `attributeLabels()` no longer call the skeleton's `ModelTrait::getTraitRules()`
/ `getTraitAttributeLabels()`, which discovered trait hooks by reflection and naming convention. Both are removed
from the skeleton; see `bundles/yii2-skeleton/UPGRADE.md`.

`Models\Traits\MenuAttributeTrait` and `Models\Traits\FooterAttributeTrait` keep their methods under shorter
names and are called by the model that uses them:

| removed                                   | replacement                   |
|-------------------------------------------|-------------------------------|
| `getMenuAttributeTraitRules()`            | `getMenuAttributeRules()`     |
| `getMenuAttributeTraitAttributeLabels()`  | `getMenuAttributeLabels()`    |
| `getFooterAttributeTraitRules()`          | `getFooterAttributeRules()`   |
| `getFooterAttributeTraitAttributeLabels()`| `getFooterAttributeLabels()`  |

```php
class Entry extends \Hirtz\Cms\Models\Entry
{
    use MenuAttributeTrait;

    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getMenuAttributeRules(),
        ];
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            ...$this->getMenuAttributeLabels(),
        ];
    }
}
```

Without the `rules()` spread `show_in_menu` is neither safe nor validated, so it is not loaded from a form post
and `Skeleton\Widgets\Forms\Fields\Field` renders nothing for it — the checkbox added by
`Modules\Admin\Widgets\Forms\Traits\MenuFieldTrait` disappears from the entry form without an error. Grep for
`use MenuAttributeTrait` and `use FooterAttributeTrait` and check every hit.
