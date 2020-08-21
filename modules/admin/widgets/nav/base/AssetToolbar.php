<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\nav\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\modules\admin\widgets\traits\UpdateFileButtonTrait;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Toolbar;

/**
 * Class AssetToolbar
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\nav\AssetToolbar
 *
 * @property Asset $model
 */
class AssetToolbar extends Toolbar
{
    use UpdateFileButtonTrait;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->actions === null) {
            $this->actions = $this->hasForm() ? [$this->getFormSubmitButton()] : [];
        }

        if ($this->links === null) {
            $this->links = $this->hasForm() ? [$this->getUpdateFileButton()] : [];
        }

        parent::init();
    }
}