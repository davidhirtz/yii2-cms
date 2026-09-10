<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Models\Asset;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Interfaces\FileRelationGridContainerInterface;
use Hirtz\Media\Traits\FilePropertyTrait;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class FileAssetGridContainer extends Widget implements FileRelationGridContainerInterface
{
    use FilePropertyTrait;
    use GridTrait;

    protected function renderContent(): string|Stringable
    {
        $content = '';

        foreach (Asset::instance()->getFileCountAttributeNames() as $language => $attributeName) {
            if ($this->file->$attributeName) {
                $content .= GridContainer::make()
                    ->title($this->getTitle())
                    ->grid(FileAssetGridView::make()
                        ->file($this->file)
                        ->language($language));
            }
        }

        return $content;
    }

    protected function getTitle(): string
    {
        return Lang::t('cms', 'COMMON_ENTRIES');
    }
}
