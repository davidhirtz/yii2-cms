<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\FileAssetParentGridView;
use Hirtz\Media\Models\File;
use Hirtz\Skeleton\Widgets\Bootstrap\Panel;
use Yii;
use yii\base\Widget;

class FileAssetParentPanel extends Widget
{
    public File $file;

    public function run(): void
    {
        foreach (Asset::instance()->getFileCountAttributeNames() as $language => $attributeName) {
            if ($this->file->$attributeName) {
                echo Panel::widget([
                    'title' => $this->getTitle($language),
                    'content' => FileAssetParentGridView::widget([
                        'file' => $this->file,
                        'language' => $language,
                    ]),
                ]);
            }
        }
    }

    protected function getTitle(string $language): string
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin')->getModule('cms');
        $title = $module->getName();

        if ($language !== Yii::$app->language) {
            $title .= ' (' . strtoupper($language) . ')';
        }

        return $title;
    }
}
