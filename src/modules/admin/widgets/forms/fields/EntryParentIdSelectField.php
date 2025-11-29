<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\widgets\forms\fields;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\modules\admin\widgets\forms\traits\ParentIdSelectFieldTrait;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\helpers\ArrayHelper;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\forms\fields\SelectField;
use Override;
use Yii;

/**
 * @template T of Entry
 * @property T $model
 */
class EntryParentIdSelectField extends SelectField
{
    use ModuleTrait;
    use ParentIdSelectFieldTrait;

    /**
     * @var T[]
     */
    private array $entries;

    #[Override]
    protected function configure(): void
    {
        $this->setItemsFromEntries($this->getEntries());
        $this->promptAttributes = ArrayHelper::remove($this->attributes, 'promptAttributes', []);

        parent::configure();
    }

    /**
     * @param T[] $entries
     */
    protected function setItemsFromEntries(array $entries, ?int $parentId = null): void
    {
        foreach ($entries as $entry) {
            if ($entry->parent_id === $parentId) {
                $name = Html::encode($entry->getI18nAttribute('name') ?: Yii::t('cms', '[ No title ]'));
                $count = count($entry->getAncestorIds());
                $indent = ($count ? (str_repeat($this->indent, $count) . ' ') : '');

                $item = [
                    'label' => $indent . $name,
                    'disabled' => !$this->model->getIsNewRecord()
                        && in_array($this->model->id, [...$entry->getAncestorIds(), $entry->id], true),
                ];

                foreach ($this->model->getI18nAttributeNames('slug') as $language => $attribute) {
                    $item['data-value'][] = $this->getParentIdOptionDataValue($entry, $language);
                }

                $this->addItem($entry->id, $item);

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
        $this->entries ??= array_filter($this->findEntries(), fn (Entry $entry) => $entry->hasDescendantsEnabled());
        return $this->entries;
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
