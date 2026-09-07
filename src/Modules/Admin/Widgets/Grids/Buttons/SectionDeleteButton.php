<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons;

use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Override;

/**
 * @see SectionController::actionDelete()
 *
 * @extends DeleteButton<Section>
 */
class SectionDeleteButton extends DeleteButton
{
    public function isVisible(): bool
    {
        return parent::isVisible()
            && $this->webuser->can(Section::AUTH_SECTION_DELETE, ['section' => $this->model]);
    }

    #[Override]
    protected function configure(): void
    {
        $this->url ??= ['/admin/cms/section/delete', 'id' => $this->model->id];

        parent::configure();
    }
}
