<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\SectionCreateButton;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\SectionDeleteButton;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\Traits\LinkButtonTrait;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\DuplicateButton;
use Hirtz\Skeleton\Widgets\Navs\ActionDropdown;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Stringable;

class SectionActionDropdown extends ActionDropdown
{
    /**
     * @use ModelTrait<Section>
     */
    use ModelTrait;
    use LinkButtonTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addItem(
            $this->getCopyButton(),
            $this->getDuplicateButton(),
            $this->getLinkButton(),
            $this->getDeleteButton(),
        );

        parent::configure();
    }

    /**
     * @see SectionController::actionEntries()
     */
    protected function getCopyButton(): Stringable
    {
        return Button::make()
            ->primary()
            ->text(Lang::t('cms', 'SECTION_MOVE_COPY'))
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
