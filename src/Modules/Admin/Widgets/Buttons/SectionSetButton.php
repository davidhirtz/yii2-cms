<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Buttons;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Html\Label;
use Hirtz\Skeleton\Html\Option;
use Hirtz\Skeleton\Html\Select;
use Hirtz\Skeleton\Widgets\Buttons\ConfirmButton;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

/**
 * @see SectionController::actionCreateSet()
 */
class SectionSetButton extends ConfirmButton
{
    use ModuleTrait;

    /**
     * @use ModelTrait<Entry>
     */
    use ModelTrait;

    protected bool $pushHistory = false;

    #[Override]
    public function isVisible(): bool
    {
        return (bool)$this->model->id
            && $this->model->allowsSections()
            && $this->webuser->can(Entry::AUTH_ENTRY)
            && $this->getSets() !== [];
    }

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'layer-group';
        $this->label ??= Yii::t('cms', 'SECTION_SET_BUTTON');
        $this->url ??= ['/admin/cms/section/create-set', 'entry' => $this->model->id];

        $select = $this->getSelect();

        $this->addContent(
            Label::make()
                ->class('form-label')
                ->text(Yii::t('cms', 'SECTION_SET_LABEL'))
                ->for($select->getId()),
            $select,
        );

        $this->include ??= '#' . $select->getId();

        parent::configure();
    }

    protected function getSelect(): Select
    {
        $select = Select::make()
            ->class('input')
            ->name('set');

        foreach ($this->getSets() as $set) {
            $select->addOption(Option::make()
                ->label($set->getName())
                ->value($set->value));
        }

        return $select;
    }

    /**
     * @return array<int, SectionSet>
     */
    protected function getSets(): array
    {
        return array_filter(
            static::getModule()->getSectionSets(),
            fn (SectionSet $set): bool => $set->isAvailable($this->model),
        );
    }
}
