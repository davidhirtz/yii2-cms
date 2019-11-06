<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;

use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HelpPanel;
use Yii;

/**
 * Class SectionHelpPanel
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\SectionHelpPanel
 */
class SectionHelpPanel extends HelpPanel
{
    /**
     * @var Section
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
            $isDraft = $this->model->isDraft() || $this->model->entry->isDraft();
            $url = $isDraft ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);

            return Html::a(Html::iconText($isDraft ? 'lock-open' : 'globe', Yii::t('cms', 'View Section')), $url, [
                'class' => 'btn btn-primary',
                'target' => 'blank',
            ]);
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