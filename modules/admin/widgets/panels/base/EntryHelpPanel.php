<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HelpPanel;
use Yii;

/**
 * Class EntryHelpPanel
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\EntryHelpPanel
 */
class EntryHelpPanel extends HelpPanel
{
    /**
     * @var Entry
     */
    public $model;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->title === null) {
            $this->title = Yii::t('cms', 'Operations');
        }

        if ($this->content === null) {
            $this->content = $this->renderButtonToolbar($this->getButtons());
        }

        parent::init();
    }

    /**
     * @return array
     */
    protected function getButtons(): array
    {
        return array_filter([
            $this->getLinkButton(),
            $this->getCloneButton(),
        ]);
    }

    /**
     * @return string
     */
    protected function getLinkButton()
    {
        if ($route = $this->model->getRoute()) {
            $manager = Yii::$app->getUrlManager();
            $url = $this->model->isDraft() ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);

            if($url) {
                return Html::a(Html::iconText($this->model->isDraft() ? 'lock-open' : 'globe', Yii::t('cms', 'View Entry')), $url, [
                    'class' => 'btn btn-primary',
                    'target' => 'blank',
                ]);
            }
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getCloneButton()
    {
        return Html::a(Html::iconText('clone', Yii::t('cms', 'Duplicate')), ['clone', 'id' => $this->model->id], [
            'class' => 'btn btn-primary',
            'data-method' => 'post',
        ]);
    }
}