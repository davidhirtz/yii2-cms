<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Override;
use Stringable;
use Yii;

/**
 * The block a section places. Without one the section renders nothing, so the prompt is not a shortcut for
 * "leave it out" — it is the state the hint warns about.
 *
 * Whoever may edit blocks gets a button next to it opening the selected one in a new tab; each option carries its
 * block's URL, which the `data-select-link` script hands to the button on every change.
 *
 * @property Section|null $model
 */
class BlockIdSelectField extends SelectField
{
    public ?string $property = 'block_id';

    #[Override]
    public function isVisible(): bool
    {
        return parent::isVisible() && $this->model?->allowsBlock();
    }

    #[Override]
    protected function configure(): void
    {
        if ($this->prompt === false) {
            $this->prompt = Yii::t('cms', 'SECTION_BLOCK_ID_PROMPT');
        }

        if (!$this->model?->block_id) {
            $this->hint ??= Yii::t('cms', 'SECTION_BLOCK_ID_HINT');
        }

        if (!$this->items) {
            foreach ($this->findBlocks() as $block) {
                $name = (string)$block->getI18nAttribute('name');

                $this->items[$block->id] = $block->isEnabled()
                    ? $name
                    : ('[' . $block->getStatusName() . "] $name");

                if ($this->showsLinkButton()) {
                    $this->itemAttributes[$block->id]['data-url'] ??= Url::to($block->getAdminRoute());
                }
            }
        }

        if ($this->showsLinkButton()) {
            $this->attributes['data-select-link'] ??= true;
        }

        parent::configure();
    }

    #[Override]
    protected function getControl(): string|Stringable
    {
        if (!$this->showsLinkButton()) {
            return parent::getControl();
        }

        $blockId = $this->model?->block_id;
        $url = $blockId !== null ? $this->itemAttributes[$blockId]['data-url'] ?? null : null;

        return Div::make()
            ->class('form-action')
            ->addContent(parent::getControl())
            ->addContent(Button::make()
                ->secondary()
                ->icon('external-link-alt')
                ->tooltip(Yii::t('cms', 'COMMON_OPEN_ADMIN'))
                ->url($url ?? '#')
                ->attribute('hidden', $url === null)
                ->target('_blank'));
    }

    protected function showsLinkButton(): bool
    {
        return $this->webuser->can(Block::AUTH_BLOCK);
    }

    /**
     * @return list<Block>
     */
    protected function findBlocks(): array
    {
        return array_values(Block::find()
            ->withTranslations()
            ->orderBy([Block::instance()->getI18nAttributeName('name') => SORT_ASC])
            ->all());
    }
}
