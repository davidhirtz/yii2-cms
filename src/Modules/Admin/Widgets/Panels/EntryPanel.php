<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\EntryController;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Modal;
use Stringable;
use Yii;

/**
 * @template T of Entry
 * @extends AbstractPanel<T>
 */
class EntryPanel extends AbstractPanel
{
    protected function getButtons(): array
    {
        return array_filter([
            $this->getDuplicateButton(),
            $this->getReplaceIndexButton(),
            $this->getLinkButton(),
        ]);
    }

}
