<?php

namespace davidhirtz\yii2\cms\models\events;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\models\File;
use Yii;
use yii\base\ModelEvent;

class FileBeforeDeleteEventHandler
{
    public function __construct(protected readonly ModelEvent $event, protected readonly File $file)
    {
        $this->handleEvent();
    }

    protected function handleEvent(): void
    {
        $i18n = Yii::$app->getI18n();

        foreach (Asset::instance()->getFileCountAttributeNames() as $language => $attributeName) {
            if ($this->file->$attributeName) {
                $i18n->callback($language, $this->deleteRelatedAssets(...));
            }
        }
    }

    protected function deleteRelatedAssets(): void
    {
        Yii::debug('Deleting related assets before deleting file ...');

        $assets = Asset::find()
            ->andWhere(['file_id' => $this->file->id])
            ->all();

        foreach ($assets as $asset) {
            $asset->delete();
        }
    }
}
