<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\SectionCreateButton;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\SectionSetButton;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\Traits\LinkButtonTrait;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\SectionDeleteButton;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\DuplicateButton;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

/**
 * The actions of one section, or — given the index page's provider instead of a model — the ones that add a
 * section to its entry.
 */
class SectionActionDropdown extends ActionDropdown
{
    /**
     * @use ModelTrait<Section>
     */
    use ModelTrait;

    /**
     * @use ProviderTrait<SectionActiveDataProvider|null>
     */
    use ProviderTrait;

    use LinkButtonTrait;

    #[Override]
    protected function configure(): void
    {
        if ($this->provider) {
            $this->addItem(
                $this->getCreateButton($this->provider->entry),
                $this->getSetButton($this->provider->entry),
            );
        } else {
            $this->addItem(
                $this->getCopyButton(),
                $this->getDuplicateButton(),
                $this->getLinkButton(),
                $this->getDeleteButton(),
            );
        }

        parent::configure();
    }

    /**
     * @see SectionController::actionCreate()
     */
    protected function getCreateButton(Entry $entry): ?Stringable
    {
        return SectionCreateButton::make()
            ->model($entry);
    }

    /**
     * @see SectionController::actionCreateSet()
     */
    protected function getSetButton(Entry $entry): ?Stringable
    {
        return SectionSetButton::make()
            ->model($entry);
    }

    /**
     * @see SectionController::actionEntries()
     */
    protected function getCopyButton(): Stringable
    {
        return Button::make()
            ->primary()
            ->text(Yii::t('cms', 'SECTION_MOVE_COPY'))
            ->icon('copy')
            ->url(['entries', 'id' => $this->model->id]);
    }

    /**
     * @see SectionController::actionDuplicate()
     */
    protected function getDuplicateButton(): Stringable
    {
        return DuplicateButton::make()
            ->model($this->model);
    }

    protected function getDeleteButton(): ?Stringable
    {
        return SectionDeleteButton::make()
            ->model($this->model);
    }

    protected function isDraft(): bool
    {
        return $this->model->isDraft() || $this->model->entry->isDraft();
    }
}
