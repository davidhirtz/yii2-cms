<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryRelationControllerTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\TypeFilterDropdown;
use Override;
use Stringable;
use Traversable;
use Yii;

/**
 * @extends EntryGridView<Entry>
 */
class EntryRelationGridView extends EntryGridView
{
    protected string $layout = '{header}{summary}{items}{pager}';

    #[Override]
    protected function configure(): void
    {
        $this->rowAttributes ??= fn (Entry $entry) => [
            'class' => $entry->entryRelation ? ['is-selected'] : [],
        ];

        parent::configure();
    }

    #[Override]
    protected function getTypeDropdown(): ?Stringable
    {
        return TypeFilterDropdown::make()
            ->items($this->getTypeDropdownItems())
            ->model(Entry::instance())
            ->visible($this->showTypeDropdown);
    }

    /**
     * @return array<int, Type>
     */
    protected function getTypeDropdownItems(): array
    {
        $items = Entry::instance()::getTypeDefinitions();
        $entryTypes = $this->provider->relatedModel?->getEntriesTypes();

        if ($entryTypes !== null) {
            $items = array_intersect_key($items, array_flip($entryTypes));
        }

        return $items;
    }

    #[Override]
    protected function isPicker(): bool
    {
        return true;
    }

    /**
     * @see EntryRelationControllerTrait::actionCreate()
     * @return Traversable<int, Stringable>
     */
    #[Override]
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        yield $this->getAdminLinkButton($entry);

        $canUpdate = $this->webuser->can(Entry::AUTH_ENTRY);

        if (!$canUpdate || $entry->entryRelation) {
            return;
        }

        $model = $this->provider->relatedModel;
        $allowedTypes = $model?->getEntriesTypes();

        if ($model && ($allowedTypes === null || in_array($entry->type, $allowedTypes, true))) {
            yield Button::make()
                ->primary()
                ->icon('star')
                ->tooltip(Yii::t('cms', 'ENTRY_RELATION_ADD_TO_MODEL'))
                ->post(['create', $model->getParamName() => $model->id, 'entry' => $entry->id]);
        }
    }

}
