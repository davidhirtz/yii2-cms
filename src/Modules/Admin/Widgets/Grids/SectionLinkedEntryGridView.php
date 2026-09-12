<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionEntryController;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Override;
use Stringable;
use Traversable;
use yii\helpers\Inflector;

/**
 * @property EntryActiveDataProvider|null $provider
 */
class SectionLinkedEntryGridView extends EntryGridView
{
    #[Override]
    protected string $layout = '{items}{footer}';

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'section-entry-grid';

        $this->rowAttributes ??= function (Entry $entry): array {
            $allowedTypes = $this->provider->section->getEntriesTypes();

            return [
                'id' => implode('-', [
                    Inflector::camel2id($entry->sectionEntry->formName()),
                    ...$entry->sectionEntry->getPrimaryKey(true),
                ]),
                'class' => $allowedTypes && !in_array($entry->type, $allowedTypes, true)
                    ? ['invalid']
                    : null,
            ];
        };

        $this->layout = $this->provider->section->entry_count ? '{items}{footer}' : '{footer}';

        /** @see SectionEntryController::actionOrder() */
        $this->orderRoute = ['order', 'section' => $this->provider->section->id];

        parent::configure();
    }

    #[Override]
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        if (!$this->webuser->can(Section::AUTH_SECTION_UPDATE, ['entry' => $entry])) {
            return;
        }

        if ($this->isSortable() && $this->provider->getCount() > 1) {
            yield $this->getSortableButton();
        }

        yield $this->getDeleteButton($entry);
    }

    /**
     * @see SectionEntryController::actionDelete()
     */
    #[Override]
    protected function getDeleteButton(Entry $entry): Stringable
    {
        return DeleteGridButton::make()
            ->url(['section-entry/delete', 'section' => $this->provider->section->id, 'entry' => $entry->id])
            ->title(Lang::t('cms', 'SECTION_ENTRY_REMOVE_TITLE'));
    }
}
