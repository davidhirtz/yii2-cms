<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\nav\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\admin\widgets\traits\LinkButtonTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Toolbar;
use Yii;

/**
 * Class EntryToolbar
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\nav\EntryToolbar
 *
 * @property Entry $model
 */
class EntryToolbar extends Toolbar
{
    use LinkButtonTrait;
    use ModuleTrait;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->actions === null) {
            $this->actions = $this->hasForm() ? [$this->getFormSubmitButton()] : [$this->getCreateEntryButton()];
        }

        if ($this->links === null) {
            $this->links = $this->hasForm() ? [$this->getLinkButton()] : [];
        }

        parent::init();
    }

    /**
     * @return string
     */
    protected function getCreateEntryButton()
    {
        if (Yii::$app->getUser()->can('author')) {
            $type = Yii::$app->getRequest()->get('type', static::getModule()->defaultEntryType);
            return Html::a(Html::iconText('plus', Yii::t('cms', 'New Entry')), ['create', 'type' => $type], [
                'class' => 'btn btn-primary',
            ]);
        }

        return '';
    }
}