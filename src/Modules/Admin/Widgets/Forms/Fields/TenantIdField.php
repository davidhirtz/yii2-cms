<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields;

use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Override;
use Yii;

/**
 * Reloads the page on change, because the tenant decides which entries the parent select offers and which host the
 * slug field spells out. That used to be a script of its own fetching the page and replacing the parent select's
 * `innerHTML`, which reached neither.
 *
 * @template T of Entry
 * @property Entry $model
 */
class TenantIdField extends SelectField
{
    public ?string $property = 'tenant_id';

    #[Override]
    protected function configure(): void
    {
        $this->attributes['required'] ??= true;

        $this->label ??= Yii::t('cms', 'ENTRY_TENANT_ID_LABEL');

        if (!$this->items) {
            foreach (TenantCollection::getAll() as $tenant) {
                $this->items[$tenant->id] = !$tenant->isEnabled()
                    ? ('[' . $tenant->getStatusName() . "] $tenant->name")
                    : $tenant->name;
            }
        }

        if (count($this->items) > 1) {
            $this->reloadsForm();
        }

        parent::configure();
    }
}
