<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\modules\admin\Module;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\FileAssetParentGridView;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
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

        if ($language != Yii::$app->language) {
            $title .= ' (' . strtoupper($language) . ')';
        }

        return $title;
    }
}
