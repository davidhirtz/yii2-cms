<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\ModelTimestampTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\ContentFieldTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\StatusFieldTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\TypeFieldTrait;
use Yii;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveField;

/**
 * @property Asset|Category|Entry|Section $model
 */
class ActiveForm extends \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm
{
    use ModelTimestampTrait;
    use ModuleTrait;
    use ContentFieldTrait;
    use StatusFieldTrait;
    use TypeFieldTrait;

    public bool $hasStickyButtons = true;

    public function descriptionField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'description', $options)->textarea();
    }

    public function slugField(array $options = []): ActiveField|string
    {
        $language = ArrayHelper::remove($options, 'language', Yii::$app->sourceLanguage);
        $attribute = $this->model->getI18nAttributeName('slug', $language);
        $options = array_merge(['enableClientValidation' => false], $options);

        return $this->field($this->model, $attribute, $options)->slug([
            'baseUrl' => Html::tag('span', $this->getSlugBaseUrl($language), ['id' => $this->getSlugId($language)]),
        ]);
    }

    public function getSlugBaseUrl(?string $language = null): string
    {
        $manager = Yii::$app->getUrlManager();
        return rtrim($manager->createAbsoluteUrl(['/', 'language' => $manager->i18nUrl || $manager->i18nSubdomain ? $language : null]), '/') . '/';
    }

    public function getSlugId(?string $language = null): string
    {
        return $this->getId() . '-' . $this->model->getI18nAttributeName('slug', $language);
    }
}