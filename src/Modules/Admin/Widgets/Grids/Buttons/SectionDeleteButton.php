<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons;

use Hirtz\Cms\Models\Entry;
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
    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible()
            && $this->webuser->can(Entry::AUTH_ENTRY);
    }

    #[Override]
    protected function configure(): void
    {
        $this->url ??= ['/admin/cms/section/delete', 'id' => $this->model->id];

        parent::configure();
    }
}
