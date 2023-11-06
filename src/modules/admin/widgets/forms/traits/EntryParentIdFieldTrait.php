<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\traits;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;
use yii\widgets\ActiveField;

trait EntryParentIdFieldTrait
{
    use ModuleTrait;

    private ?array $_entries = null;

    public function parentIdField(): ActiveField|string
    {
        if (!static::getModule()->enableNestedEntries
            || !$this->model->hasParentEnabled()
            || !$this->getEntries()) {
            return '';
        }

        return $this->field($this->model, 'parent_id')->dropDownList($this->getParentIdItems(), $this->getParentIdOptions());
    }

    /**
     * @return Entry[]
     */
    protected function getEntries(): array
    {
        if ($this->_entries === null) {
            $entries = Entry::find()
                ->select($this->model->getI18nAttributesNames(['id', 'parent_id', 'name', 'path', 'slug', 'parent_slug', 'entry_count', 'section_count']))
                ->whereHasDescendantsEnabled()
                ->orderBy($this->getParentIdItemsOrderBy())
                ->indexBy('id')
                ->all();

            $this->_entries = array_filter($entries, fn(Entry $entry): bool => $entry->hasDescendantsEnabled());
        }

        return $this->_entries;
    }

    protected function getParentIdOptions(): array
    {
        $options = [
            'encode' => false,
            'prompt' => ['text' => ''],
        ];

        foreach ($this->model->getI18nAttributeNames('slug') as $language => $attribute) {
            $options['data-form-target'][] = $this->getSlugId($language);
            $options['prompt']['options']['data-value'][] = $this->getSlugBaseUrl($language);
        }

        foreach ($this->getEntries() as $entry) {
            foreach ($this->model->getI18nAttributeNames('slug') as $language => $attribute) {
                $options['options'][$entry->id]['data-value'][] = $this->getParentIdOptionDataValue($entry, $language);
            }

            if (!$this->model->getIsNewRecord() && in_array($this->model->id, array_merge($entry->getAncestorIds(), [$entry->id]))) {
                $options['options'][$entry->id]['disabled'] = true;
            }
        }

        return $options;
    }

    protected function getParentIdOptionDataValue(Entry $entry, ?string $language = null): string
    {
        return Yii::$app->getI18n()->callback($language, fn() => rtrim(Yii::$app->getUrlManager()->createAbsoluteUrl($entry->getRoute()), '/') . '/');
    }

    protected function getParentIdItems(?array $entries = null, ?int $parentId = null): array
    {
        static $parentIdItems = [];

        foreach ($entries ?? $this->getEntries() as $entry) {
            if ($entry->parent_id == $parentId) {
                $count = count($entry->getAncestorIds());
                $parentIdItems[$entry->id] = ($count ? ('&nbsp;' . str_repeat('–', $count) . ' ') : '')
                    . Html::encode($entry->getI18nAttribute('name') ?: Yii::t('cms', '[ No title ]'));

                if ($entry->entry_count) {
                    $this->getParentIdItems($entries, $entry->id);
                }
            }
        }

        return $parentIdItems;
    }

    protected function getParentIdItemsOrderBy(): array
    {
        return static::getModule()->defaultEntryOrderBy ?? ['position' => SORT_ASC];
    }
}