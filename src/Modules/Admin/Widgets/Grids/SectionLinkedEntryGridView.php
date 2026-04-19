<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionEntryController;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\SectionEntryDeleteButton;
use Hirtz\Skeleton\Widgets\Buttons\ButtonGroup;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\GridToolbarItem;
use Override;
use Stringable;
use Traversable;
use Yii;
use yii\helpers\Inflector;

/**
 * @property EntryActiveDataProvider|null $provider
 */
class SectionLinkedEntryGridView extends EntryGridView
{
    protected Section $section;

    public function section(Section $section): static
    {
        $this->section = $section;
        return $this;
    }

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

        $this->provider ??= Yii::$container->get(EntryActiveDataProvider::class, config: [
            'section' => $this->section,
            'pagination' => false,
        ]);

        $this->layout = $this->section->entry_count ? '{items}{footer}' : '{footer}';

        /** @see SectionEntryController::actionOrder() */
        $this->orderRoute = ['section-entry/order', 'section' => $this->provider->section->id];


        $this->footer ??= [
            GridToolbarItem::make()
                ->class('form-row')
                ->content(ButtonGroup::make()
                    ->class('form-content')
                    ->content($this->getSelectEntriesButton())),
        ];

        parent::configure();
    }

    protected function getSelectEntriesButton(): string|Stringable
    {
        $entryTypes = $this->provider->section->getEntriesTypes();

        return CreateButton::make()
            ->label(Yii::t('cms', 'Link entries'))
            ->icon('link')
            ->url([
                'section-entry/index',
                'section' => $this->provider->section->id,
                'type' => $entryTypes ? current($entryTypes) : null,
            ]);
    }

    #[Override]
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        if (!$this->webuser->can(Section::AUTH_SECTION_UPDATE, ['entry' => $entry])) {
            yield;
        }

        if ($this->isSortable() && $this->provider->getCount() > 1) {
            yield $this->getSortableButton();
        }

        yield Yii::createObject(SectionEntryDeleteButton::class, [$entry, $this->provider->section]);
    }
}
