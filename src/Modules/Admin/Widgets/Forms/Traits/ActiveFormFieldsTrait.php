<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Traits;

use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TextareaField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TinyMceField;
use Stringable;

trait ActiveFormFieldsTrait
{
    protected function getStatusField(): ?Stringable
    {
        return SelectField::make()
            ->property('status');
    }

    /**
     * Reloads the form when the selected type renders different custom attribute fields. Types sharing one definition
     * list get no `hx-*` attributes at all, so a form without custom attributes never pays for a round trip.
     */
    protected function getTypeField(): ?Stringable
    {
        $field = SelectField::make()
            ->property('type');

        $fingerprints = $this->getTypeFingerprints();

        if (count(array_unique($fingerprints)) < 2) {
            return $field;
        }

        return $field
            ->reloadsForm()
            ->itemAttributes(array_map(
                static fn (string $fingerprint): array => ['data-fingerprint' => $fingerprint],
                $fingerprints,
            ))
            ->attribute('data-fingerprint', $fingerprints[$this->model->type] ?? '')
            ->attribute(
                'hx-trigger',
                'change[this.selectedOptions[0].dataset.fingerprint !== this.dataset.fingerprint]'
            );
    }

    /**
     * A type instance carries no relation, so a relation-dependent definition has to fingerprint the same for every
     * type — only the difference between the types decides whether the form reloads.
     *
     * @return array<int|string, string>
     */
    protected function getTypeFingerprints(): array
    {
        $fingerprints = [];

        foreach ($this->model::getTypeInstances() as $type => $instance) {
            $fingerprints[$type] = implode('', array_map(
                static fn (CustomAttribute $definition): string => $definition->getFingerprint(),
                $instance->getCustomAttributeDefinitions(),
            ));
        }

        return $fingerprints;
    }

    protected function getNameField(): ?Stringable
    {
        return InputField::make()
            ->property('name');
    }

    protected function getContentField(): ?Stringable
    {
        if (!$this->model->contentType) {
            return null;
        }

        return $this->model->contentType === 'html'
            ? TinyMceField::make()
                ->property('content')
                ->validator($this->model->htmlValidator)
            : TextareaField::make()
                ->property('content');
    }

    protected function getLinkField(): ?Stringable
    {
        return InputField::make()
            ->property('link');
    }
}
