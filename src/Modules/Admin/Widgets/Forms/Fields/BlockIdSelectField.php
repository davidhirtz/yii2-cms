<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Override;
use Yii;

/**
 * The block a section places. Without one the section renders nothing, so the prompt is not a shortcut for
 * "leave it out" — it is the state the hint warns about.
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
            }
        }

        parent::configure();
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
