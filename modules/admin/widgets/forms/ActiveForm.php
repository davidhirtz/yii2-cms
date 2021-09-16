<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\ModelTimestampTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\ContentFieldTrait;
use Yii;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveField;

/**
 * Class ActiveForm
 * @package davidhirtz\yii2\cms\modules\admin\widgets\forms
 *
 * @property Asset|Category|Entry|Section $model
 */
class ActiveForm extends \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm
{
    use ModelTimestampTrait;
    use ContentFieldTrait;

    /**
     * @param array $options
     * @return ActiveField|string
     */
    public function statusField($options = [])
    {
        return count($statuses = $this->getStatuses()) > 1 ? $this->field($this->model, 'status', $options)->dropdownList($statuses) : '';
    }

    /**
     * @param array $options
     * @return ActiveField|string
     */
    public function typeField($options = [])
    {
        return count($types = $this->getTypes()) > 1 ? $this->field($this->model, 'type', $options)->dropdownList($types) : '';
    }

    /**
     * @param array $options
     * @return string
     */
    public function descriptionField($options = []): string
    {
        $attribute = $this->model->getI18nAttributeName('description', ArrayHelper::remove($options, 'language'));
        return $this->field($this->model, $attribute, $options)->textarea();
    }

    /**
     * @param array $options
     * @return string
     */
    public function slugField($options = []): string
    {
        $language = ArrayHelper::remove($options, 'language');
        $attribute = $this->model->getI18nAttributeName('slug', $language);
        $options = array_merge(['enableClientValidation' => false], $options);

        return $this->field($this->model, $attribute, $options)->slug([
            'baseUrl' => Html::tag('span', $this->getSlugBaseUrl($language), ['id' => $this->getSlugId($language)]),
        ]);
    }

    /**
     * Renders user information footer.
     */
    public function renderFooter()
    {
        if ($items = array_filter($this->getFooterItems())) {
            echo $this->listRow($items);
        }
    }

    /**
     * @return array
     */
    protected function getFooterItems(): array
    {
        return $this->getTimestampItems();
    }

    /**
     * @return array
     */
    protected function getStatuses(): array
    {
        return ArrayHelper::getColumn($this->model::getStatuses(), 'name');
    }

    /**
     * @return array
     */
    protected function getTypes(): array
    {
        return ArrayHelper::getColumn($this->model::getTypes(), 'name');
    }

    /**
     * @return array
     */
    public function getTypeToggleOptions()
    {
        $toggle = [];

        foreach ($this->model::getTypes() as $type => $typeOptions) {
            if ($hidden = ($typeOptions['hiddenFields'] ?? false)) {
                $toggle[] = [$type, $hidden];
            }
        }

        return $this->getToggleOptions($toggle);
    }

    /**
     * @param string|null $language
     * @return string
     */
    protected function getSlugBaseUrl($language = null): string
    {
        $manager = Yii::$app->getUrlManager();
        return rtrim($manager->createAbsoluteUrl(['/', 'language' => $manager->i18nUrl || $manager->i18nSubdomain ? $language : null]), '/') . '/';
    }

    /**
     * @param string|null $language
     * @return string
     */
    protected function getSlugId($language = null): string
    {
        return $this->getId() . '-' . $this->model->getI18nAttributeName('slug', $language);
    }
}