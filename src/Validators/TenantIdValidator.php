<?php

declare(strict_types=1);

namespace Hirtz\Cms\Validators;

use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Mcp\SchemaBuilder;
use Hirtz\Skeleton\Validators\Interfaces\AttributeTypeInterface;
use Hirtz\Skeleton\Validators\Interfaces\JsonSchemaInterface;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Override;
use Yii;
use yii\base\Model;
use yii\base\NotSupportedException;
use yii\validators\Validator;

/**
 * An empty value resolves to the default tenant, so imports and single-tenant projects can create entries without
 * knowing about tenants at all.
 */
class TenantIdValidator extends Validator implements AttributeTypeInterface, JsonSchemaInterface
{
    /** @var string[] */
    public $attributes = ['tenant_id'];
    public $skipOnEmpty = false;

    #[Override]
    public function getAttributeType(): string
    {
        return AttributeTypecastBehavior::TYPE_INTEGER;
    }

    #[Override]
    public function getJsonSchema(Model $model, string $attribute): array
    {
        $names = array_map(fn (Tenant $tenant): string => $tenant->name, TenantCollection::getAll());

        return [
            'type' => ['integer', 'null'],
            'enum' => [...array_keys($names), null],
            'description' => SchemaBuilder::describeEnum($names) . ' Empty for the default tenant.',
        ];
    }

    /**
     * @param Entry $model
     */
    #[Override]
    public function validateAttribute($model, $attribute): void
    {
        $tenantId = (int)$model->getAttribute($attribute);

        $tenant = $tenantId
            ? TenantCollection::getById($tenantId)
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
