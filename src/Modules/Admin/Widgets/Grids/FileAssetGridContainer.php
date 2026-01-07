<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Modules\Admin\Widgets\Grids\Interfaces\FileRelationGridContainerInterface;
use Hirtz\Media\Traits\FilePropertyTrait;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

class FileAssetGridContainer extends Widget implements FileRelationGridContainerInterface
{
    use FilePropertyTrait;
    use GridTrait;
    use ModuleTrait;

    protected function renderContent(): string|Stringable
    {
        $content = '';

        foreach (Asset::instance()->getFileCountAttributeNames() as $language => $attributeName) {
            if ($this->file->$attributeName) {
                $content .= GridContainer::make()
                    ->title($this->getTitle($language))
                    ->grid(FileAssetGridView::make()
                        ->file($this->file)
                        ->language($language));
            }
        }

        return $content;
    }

    protected function getTitle(string $language): string
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('cms');
        $title = $module->getName();

        if ($language !== Yii::$app->language && self::getModule()->enableI18nTables) {
            $title .= ' (' . strtoupper($language) . ')';
        }

        return $title;
    }
}
