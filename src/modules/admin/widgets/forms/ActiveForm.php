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
 * @property Asset|Category|Entry|Section $model
 */
class ActiveForm extends \davidhirtz\yii2\skeleton\widgets\bootstrap\ActiveForm
{
    use ModelTimestampTrait;
    use ContentFieldTrait;

    public function statusField(array $options = []): ActiveField|string
    {
        return count($statuses = $this->getStatuses()) > 1 ? $this->field($this->model, 'status', $options)->dropdownList($statuses) : '';
    }

    public function typeField(array $options = []): ActiveField|string
    {
        return count($types = $this->getTypes()) > 1 ? $this->field($this->model, 'type', $options)->dropdownList($types) : '';
    }

    public function descriptionField(array $options = []): ActiveField|string
    {
        $attribute = $this->model->getI18nAttributeName('description', ArrayHelper::remove($options, 'language'));
        return $this->field($this->model, $attribute, $options)->textarea();
    }

    public function nameField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'name', $options);
    }

    public function titleField(array $options = []): ActiveField|string
    {
        return $this->field($this->model, 'title', $options);
    }

    public function slugField(array $options = []): ActiveField|string
    {
        $language = ArrayHelper::remove($options, 'language');
        $attribute = $this->model->getI18nAttributeName('slug', $language);
        $options = array_merge(['enableClientValidation' => false], $options);

        return $this->field($this->model, $attribute, $options)->slug([
            'baseUrl' => Html::tag('span', $this->getSlugBaseUrl($language), ['id' => $this->getSlugId($language)]),
        ]);
    }

    public function renderFooter(): void
    {
        if ($items = array_filter($this->getFooterItems())) {
            echo $this->listRow($items);
        }
    }

    protected function getFooterItems(): array
    {
        return $this->getTimestampItems();
    }

    protected function getStatuses(): array
    {
        return ArrayHelper::getColumn($this->model::getStatuses(), 'name');
    }
    protected function getTypes(): array
    {
        return ArrayHelper::getColumn($this->model::getTypes(), 'name');
    }

    /** @noinspection PhpUnused */
    public function getTypeToggleOptions(): array
    {
        $toggle = [];

        foreach ($this->model::getTypes() as $type => $typeOptions) {
            if ($hidden = ($typeOptions['hiddenFields'] ?? false)) {
                $toggle[] = [$type, $hidden];
            }
        }

        return $this->getToggleOptions($toggle);
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