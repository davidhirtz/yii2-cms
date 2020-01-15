<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class HelpPanel
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\EntryHelpPanel
 */
abstract class HelpPanel extends \davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HelpPanel
{
    /**
     * @var Category|Entry|Section
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
     * @return string
     */
    protected function getLinkButton()
    {
        if (!$this->model->isDisabled()) {
            if ($route = $this->model->getRoute()) {
                $manager = Yii::$app->getUrlManager();
                $url = $this->isDraft() ? $manager->createDraftUrl($route) : $manager->createAbsoluteUrl($route);

                if ($url) {
                    return Html::a(Html::iconText($this->isDraft() ? 'lock-open' : 'globe', Yii::t('cms', 'Open website')), $url, [
                        'class' => 'btn btn-primary',
                        'target' => 'blank',
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function isDraft(): bool
    {
        return $this->model->isDraft();
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

    /**
     * @return array
     */
    abstract protected function getButtons(): array;
}