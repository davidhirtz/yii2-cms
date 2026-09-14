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
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Modal;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\TitleTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * @see SectionController::actionCreateSet()
 */
class SectionSetButton extends Widget
{
    use IconTrait;
    use LabelTrait;
    use ModuleTrait;

    /**
     * @use ModelTrait<Entry>
     */
    use ModelTrait;

    use TitleTrait;

    #[Override]
    public function isVisible(): bool
    {
        return (bool)$this->model->id
            && $this->model->hasSectionsEnabled()
            && $this->webuser->can(Entry::AUTH_ENTRY)
            && $this->getSets() !== [];
    }

    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'layer-group';
        $this->label ??= Yii::t('cms', 'SECTION_SET_BUTTON');
        $this->title ??= Yii::t('cms', 'SECTION_SET_BUTTON');

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Button::make()
            ->primary()
            ->icon($this->icon)
            ->modal($this->getModal())
            ->text($this->label);
    }

    protected function getModal(): Modal
    {
        $select = $this->getSelect();

        return Modal::make()
            ->title($this->title)
            ->content(
                Label::make()
                    ->class('form-label')
                    ->text(Yii::t('cms', 'SECTION_SET_LABEL'))
                    ->for($select->getId()),
                $select,
            )
            ->footer(Button::make()
                ->primary()
                ->text($this->label)
                ->icon($this->icon)
                ->post(['/admin/cms/section/create-set', 'entry' => $this->model->id])
                ->attribute('hx-include', '#' . $select->getId()));
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
        return static::getModule()->getSectionSets();
    }
}
