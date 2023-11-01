<?php

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\datetime\DateTimeInput;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;
use yii\widgets\ActiveField;

/**
 * EntryActiveForm is a widget that builds an interactive HTML form for {@see Entry}. By default, it implements fields
 * only for default attributes defined in the base model.
 *
 * @property Entry $model
 */
class EntryActiveForm extends ActiveForm
{
    use ModuleTrait;

    public bool $hasStickyButtons = true;

    private ?array $_entries = null;

    public function init(): void
    {
        $this->fields ??= [
            $this->statusField(),
            $this->typeField(),
            $this->parentIdField(),
            $this->nameField(),
            $this->contentField(),
            $this->publishDateField(),
            '-',
            $this->titleField(),
            $this->descriptionField(),
            $this->slugField(),
        ];

        parent::init();
    }

    public function parentIdField(): ActiveField|string
    {
        if (!static::getModule()->enableNestedEntries
            || !$this->model->hasParentEnabled()
            || !$this->getEntries()) {
            return '';
        }

        return $this->field($this->model, 'parent_id')->dropdownList($this->getParentIdItems(), $this->getParentIdOptions());
    }

    public function publishDateField(): ActiveField|string
    {
        return $this->field($this->model, 'publish_date')->widget(DateTimeInput::class);
    }

    /**
     * Returns the entries that can be used as parent entries. This can be overridden by the entry's active form class.
     * @return Entry[]
     */
    protected function getEntries(): array
    {
        if ($this->_entries === null) {
            $entries = Entry::find()
                ->select($this->model->getI18nAttributesNames(['id', 'parent_id', 'name', 'path', 'slug', 'parent_slug', 'entry_count', 'section_count']))
                ->whereHasDescendantsEnabled()
                ->orderBy(static::getModule()->defaultEntryOrderBy)
                ->indexBy('id')
                ->all();

            $this->_entries = array_filter($entries, fn(Entry $entry): bool => $entry->hasDescendantsEnabled());
        }

        return $this->_entries;
    }

    /**
     * Returns the select options form the parent dropdown, disabling items that are descendants of the current entry.
     */
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
                $parentIdItems[$entry->id] = ($count ? ('&nbsp;' . str_repeat('–', $count) . ' ') : '') .
                    Html::encode($entry->getI18nAttribute('name'));

                if ($entry->entry_count) {
                    $this->getParentIdItems($entries, $entry->id);
                }
            }
        }

        return $parentIdItems;
    }
}