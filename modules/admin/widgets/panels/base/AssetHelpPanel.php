<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\modules\admin\widgets\traits\UpdateFileButtonTrait;
use Yii;

/**
 * Class AssetHelpPanel
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\AssetHelpPanel
 *
 * @property Asset $model
 */
class AssetHelpPanel extends HelpPanel
{
    use UpdateFileButtonTrait;

    /**
     * @return array
     */
    protected function getButtons(): array
    {
        return array_filter([
            $this->getUpdateFileButton(),
        ]);
    }
}