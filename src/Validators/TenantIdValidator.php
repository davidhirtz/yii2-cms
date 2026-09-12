<?php

declare(strict_types=1);

namespace Hirtz\Cms\Validators;

use Hirtz\Cms\Models\Entry;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Override;
use Yii;
use yii\base\NotSupportedException;
use yii\validators\Validator;

/**
 * An empty value resolves to the default tenant, so imports and single-tenant projects can create entries without
 * knowing about tenants at all.
 */
class TenantIdValidator extends Validator
{
    /** @var string[] */
    public $attributes = ['tenant_id'];
    public $skipOnEmpty = false;

    /**
     * @param Entry $model
     */
    #[Override]
    public function validateAttribute($model, $attribute): void
    {
        $tenantId = (int)$model->getAttribute($attribute);

        $tenant = $tenantId
            ? TenantCollection::getAll()[$tenantId] ?? null
            : TenantCollection::getDefault();

        if (!$tenant) {
            if ($tenantId) {
                $model->addInvalidAttributeError($attribute);
            } else {
                $model->addError($attribute, Yii::t('cms', 'ENTRY_TENANT_ID_ERROR'));
            }

            return;
        }

        $model->populateTenantRelation($tenant);
    }

    #[Override]
    public function validate($value, &$error = null): bool
    {
        throw new NotSupportedException(static::class . ' does not support validate().');
    }
}
