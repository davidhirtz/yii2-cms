<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\panels\base;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\admin\widgets\traits\LinkButtonTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;

/**
 * Class HelpPanel
 * @package davidhirtz\yii2\cms\modules\admin\widgets\base
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\CategoryHelpPanel
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\EntryHelpPanel
 * @see \davidhirtz\yii2\cms\modules\admin\widgets\panels\SectionHelpPanel
 */
abstract class HelpPanel extends \davidhirtz\yii2\skeleton\modules\admin\widgets\panels\HelpPanel
{
    use LinkButtonTrait;

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
    protected function getCloneButton()
    {
        return Html::a(Html::iconText('paste', Yii::t('cms', 'Duplicate')), ['clone', 'id' => $this->model->id], [
            'class' => 'btn btn-primary',
            'data-method' => 'post',
        ]);
    }

    /**
     * @return array
     */
    abstract protected function getButtons(): array;
}