<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Cms\Modules\Admin\Widgets\Buttons\EntryRelationCreateButton;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryRelationControllerTrait;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Skeleton\Widgets\Grids\Columns\Buttons\DeleteGridButton;
use Hirtz\Skeleton\Widgets\Grids\GridSummary;
use Override;
use Stringable;
use Traversable;
use Yii;
use yii\helpers\Inflector;

/**
 * @property EntryActiveDataProvider|null $provider
 */
/**
 * @extends EntryGridView<Entry>
 */
class LinkedEntryGridView extends EntryGridView
{
    public bool $showSelection = false;

    protected string $layout = '{items}{footer}';

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'entry-relation-grid';

        $this->rowAttributes ??= function (Entry $entry): array {
            $allowedTypes = $this->provider->relatedModel?->getEntriesTypes();

            return [
                'id' => implode('-', [
                    Inflector::camel2id((string)$entry->entryRelation?->formName()),
                    ...(array)$entry->entryRelation?->getPrimaryKey(true),
                ]),
                'class' => $allowedTypes && !in_array($entry->type, $allowedTypes, true)
                    ? ['invalid']
                    : null,
            ];
        };

        $model = $this->provider->relatedModel;
        $this->layout = $model?->entry_count ? '{items}{footer}' : '{summary}{footer}';

        /** @see EntryRelationControllerTrait::actionOrder() */
        $this->orderRoute = ['order', ...$model ? [$model->getParamName() => $model->id] : []];

        parent::configure();
    }

    #[Override]
    protected function getSummary(): ?GridSummary
    {
        return parent::getSummary()
            ->emptyMessage(Yii::t('cms', 'ENTRY_RELATION_GRID_SUMMARY_EMPTY'))
            ->emptyLink(Yii::t('cms', 'ENTRY_RELATION_GRID_SUMMARY_EMPTY_LINK'), $this->getEntryRelationCreateRoute())
            ->visible(fn (): bool => $this->provider->getCount() === 0);
    }

    /**
     * @return Traversable<int, Stringable>
     */
    #[Override]
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        if (!$this->webuser->can(Entry::AUTH_ENTRY)) {
            return;
        }

        if ($this->isSortable() && $this->provider->getCount() > 1) {
            yield $this->getSortableButton();
        }

        yield $this->getDeleteButton($entry);
    }

    /**
     * @see EntryRelationControllerTrait::actionDelete()
     */
    #[Override]
    protected function getDeleteButton(Entry $entry): Stringable
    {
        $model = $this->provider->relatedModel;

        return DeleteGridButton::make()
            ->label(Yii::t('cms', 'ENTRY_RELATION_BUTTON_REMOVE'))
            ->url(['delete', ...$model ? [$model->getParamName() => $model->id] : [], 'entry' => $entry->id])
            ->title(Yii::t('cms', 'ENTRY_RELATION_REMOVE_TITLE'));
    }

    /**
     * The route `EntryRelationCreateButton` leads to, `null` without the relation's permission.
     *
     * @return array<int|string, mixed>|null
     */
    protected function getEntryRelationCreateRoute(): ?array
    {
        $model = $this->provider->relatedModel;

        if (!$model instanceof ActiveRecord) {
            return null;
        }

        $entryRelationClass = $model->getEntryRelationClass();

        if (!$this->webuser->can($entryRelationClass::instance()->getPermissionName())) {
            return null;
        }

        $entryTypes = $model->getEntriesTypes();

        return [
            $entryRelationClass::getAdminControllerRoute() . '/create',
            ...Application::current()->getRequest()->getQueryParams(),
            $model->getParamName() => $model->id,
            'type' => $entryTypes ? current($entryTypes) : null,
        ];
    }
}
