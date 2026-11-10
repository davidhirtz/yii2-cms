<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\CustomAttributes;

use Hirtz\Cms\Links\EntryLinks;
use Hirtz\Cms\Links\EntryUriScheme;
use Hirtz\Skeleton\Validators\HtmlValidator;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\TinyMceField;
use Override;
use yii\base\Model;

/**
 * The skeleton's class resolves to this one through the container, see `Bootstrap::getDefaultConfig()`.
 */
class HtmlCustomAttribute extends \Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute
{
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        $rules = parent::getValidationRules($owner);

        foreach ($rules as $key => $rule) {
            $class = $rule['class'] ?? $rule[0] ?? null;

            if (is_string($class) && is_a($class, HtmlValidator::class, true)) {
                $rules[$key]['uriSchemes'] = [
                    ...$rule['uriSchemes'] ?? [],
                    EntryLinks::SCHEME => new EntryUriScheme(),
                ];
            }
        }

        return $rules;
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        $field = parent::createField($owner);
        $route = EntryLinks::getPickerRoute($owner);

        return $field instanceof TinyMceField && $route !== null
            ? $field->linkPickerUrl($route)->linkPickerTitle(EntryLinks::getPickerTitle())
            : $field;
    }

    #[Override]
    public function formatVisibleValue(Model $owner, mixed $value, ?string $language): mixed
    {
        return is_string($value) ? EntryLinks::replaceHtml($value, $language) : $value;
    }
}
