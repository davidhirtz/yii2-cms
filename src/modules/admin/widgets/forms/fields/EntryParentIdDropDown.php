<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\fields;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\Html;
use Yii;
use yii\widgets\InputWidget;

/**
 * @template T of Entry
 * @property T $model
 */
class EntryParentIdDropDown extends InputWidget
{
    use ModuleTrait;
    use ParentIdFieldTrait;

    /**
     * @var array|Entry[]
     */
    private array $_entries;

    public function init(): void
    {
        $this->setItemsFromEntries($this->getEntries());
        $this->prepareOptions();

        parent::init();
    }

    protected function prepareOptions(): void
    {
        foreach ($this->getEntries() as $entry) {
            foreach ($this->model->getI18nAttributeNames('slug') as $language => $attribute) {
                $this->options['options'][$entry->id]['data-value'][] = $this->getParentIdOptionDataValue($entry, $language);
            }

            if (!$this->model->getIsNewRecord()
                && in_array($this->model->id, [...$entry->getAncestorIds(), $entry->id])) {
                $this->options['options'][$entry->id]['disabled'] = true;
            }
        }
    }

    /**
     * @param T[] $entries
     */
    protected function setItemsFromEntries(array $entries, ?int $parentId = null): void
    {
        foreach ($entries as $entry) {
            if ($entry->parent_id == $parentId) {
                $name = Html::encode($entry->getI18nAttribute('name') ?: Yii::t('cms', '[ No title ]'));
                $count = count($entry->getAncestorIds());
                $indent = ($count ? (str_repeat($this->indent, $count) . ' ') : '');

                $this->items[$entry->id] = $indent . $name;

                if ($entry->entry_count) {
                    $this->setItemsFromEntries($entries, $entry->id);
                }
            }
        }
    }

    /**
     * @return T[]
     */
    protected function getEntries(): array
    {
        $this->_entries ??= array_filter($this->findEntries(), fn (Entry $entry) => $entry->hasDescendantsEnabled());
        return $this->_entries;
    }

    /**
     * @return T[]
     */
    protected function findEntries(): array
    {
        return $this->getEntryQuery()
            ->whereHasDescendantsEnabled()
            ->orderBy($this->getOrderBy())
            ->indexBy('id')
            ->all();
    }

    protected function getEntryQuery(): EntryQuery
    {
        return Entry::find()
            ->replaceI18nAttributes();
    }

    protected function getOrderBy(): array
    {
        return static::getModule()->defaultEntryOrderBy ?? ['position' => SORT_ASC];
    }
}
