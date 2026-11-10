<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\CustomAttributes;

use Hirtz\Cms\Links\EntryLinks;
use Closure;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttributeGroupItem;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\LinkField;
use Override;
use Stringable;
use Yii;
use yii\base\Model;

/**
 * The skeleton's class resolves to this one through the container, see `Bootstrap::getDefaultConfig()`.
 */
class LinkCustomAttribute extends \Hirtz\Skeleton\Models\CustomAttributes\LinkCustomAttribute
{
    protected bool $entries = true;

    /**
     * @var list<int>
     */
    protected array $types = [];

    protected bool $anyTenant = false;

    public function entries(bool $entries = true): static
    {
        $this->entries = $entries;
        return $this;
    }

    public function types(int ...$types): static
    {
        $this->types = array_values($types);
        return $this;
    }

    public function anyTenant(bool $anyTenant = true): static
    {
        $this->anyTenant = $anyTenant;
        return $this;
    }

    /**
     * @return list<int>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function allowsEntries(): bool
    {
        return $this->entries;
    }

    #[Override]
    public function getJsonSchemaComment(): string
    {
        return $this->entries
            ? 'An absolute URL (https:, mailto:, tel:), a partial one starting with /, ? or #, or entry:<id> (optionally entry:<id>#anchor) for an entry.'
            : parent::getJsonSchemaComment();
    }

    /**
     * The URL rules skip a picked entry, which is checked against the scope only when it changed, so a target that
     * left it since blocks no unrelated save.
     */
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        $rules = parent::getValidationRules($owner);

        if (!$this->entries) {
            return $rules;
        }

        $isEntry = static fn (Model $model, string $attribute): bool => is_string($model->$attribute)
            && str_starts_with($model->$attribute, EntryLinks::SCHEME . ':');

        foreach ($rules as $key => $rule) {
            if (isset($rule['when']) && $rule['when'] instanceof Closure) {
                $when = $rule['when'];
                $rules[$key]['when'] = static fn (Model $model, string $attribute): bool => !$isEntry($model, $attribute)
                    && $when($model, $attribute);
            }
        }

        // Yii binds an inline validator to the model, so the definition is captured instead of `$this`.
        $definition = $this;

        $rules[] = [
            function (string $attribute) use ($definition, $owner): void {
                $definition->validateEntry($owner, $attribute);
            },
            'when' => $isEntry,
        ];

        return $rules;
    }

    public function validateEntry(Model $owner, string $attribute): void
    {
        $value = $owner->$attribute;
        $link = EntryLinks::parse($value);

        if ($link === null) {
            $owner->addError($attribute, Yii::t('yii', '{attribute} is not a valid URL.', [
                'attribute' => $owner->getAttributeLabel($attribute),
            ]));

            return;
        }

        if ($this->isBaselineValue($owner, $attribute, $value)) {
            return;
        }

        if (!$this->getEntryQuery($owner)->andWhere([Entry::tableName() . '.[[id]]' => $link['id']])->exists()) {
            $owner->addError($attribute, Yii::t('yii', '{attribute} is invalid.', [
                'attribute' => $owner->getAttributeLabel($attribute),
            ]));
        }
    }

    /**
     * @return EntryQuery<Entry>
     */
    public function getEntryQuery(Model $owner): EntryQuery
    {
        $query = Entry::find();

        if ($this->types) {
            $query->andWhere([Entry::tableName() . '.[[type]]' => $this->types]);
        }

        $tenantId = $this->anyTenant ? null : EntryLinks::findTenantId($owner);

        if ($tenantId !== null) {
            $query->andWhere([Entry::tableName() . '.[[tenant_id]]' => $tenantId]);
        }

        return $query;
    }

    /**
     * Inside a group, where no row has an old value, the value anywhere in the top-level group's baseline counts.
     */
    protected function isBaselineValue(Model $owner, string $name, mixed $value): bool
    {
        $record = $owner;

        while ($record instanceof CustomAttributeGroupItem) {
            $name = $record->getGroup()->name;
            $record = $record->getOwner();
        }

        if (!$record instanceof CustomAttributeInterface) {
            return false;
        }

        $baseline = $record->getCustomAttributeBaseline($name);

        if (!is_array($baseline)) {
            return $baseline === $value;
        }

        $found = false;

        array_walk_recursive($baseline, static function (mixed $item) use ($value, &$found): void {
            $found = $found || $item === $value;
        });

        return $found;
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        $field = parent::createField($owner);

        if (!$this->entries || !$field instanceof LinkField) {
            return $field;
        }

        return $field->prepare(function (LinkField $field) use ($owner): void {
            $property = (string)$field->getProperty();

            EntryLinks::configureField(
                $field,
                $field->getModel()?->$property,
                EntryLinks::getSuggestionRoute($owner, $this->types, $this->anyTenant, $field->getLanguage()),
                EntryLinks::findTenantId($owner),
                $field->getLanguage(),
            );
        });
    }

    /**
     * The picked entry's name, so the trail and a plain-text field show more than `entry:12`.
     */
    #[Override]
    public function formatValue(Model $owner, mixed $value): string|Stringable|array|null
    {
        $link = $this->entries ? EntryLinks::parse($value) : null;
        $entry = $link !== null ? EntryLinks::findAdminEntry($link['id']) : null;

        return $entry
            ? $entry->getI18nAttribute('name') . " ($value)"
            : parent::formatValue($owner, $value);
    }

    #[Override]
    public function formatVisibleValue(Model $owner, mixed $value, ?string $language): mixed
    {
        return $this->entries ? EntryLinks::getUrl($value, $language) : $value;
    }
}
