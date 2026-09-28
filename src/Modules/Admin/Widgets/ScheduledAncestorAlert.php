<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets;

use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Html\Traits\TagContentTrait;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * On the form of a live entry whose parent is still scheduled: the entry is reachable at its own address before the
 * parent goes live ({@see Entry::findScheduledAncestor()}).
 */
class ScheduledAncestorAlert extends Widget
{
    use ContainerTrait;
    use IconTrait;
    use TagContentTrait;

    protected ?Entry $entry = null;

    public function entry(?Entry $entry): static
    {
        $this->entry = $entry;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $ancestor = $this->entry?->findScheduledAncestor();

        if ($ancestor && !$this->content) {
            $this->addText(Yii::t('cms', 'ENTRY_SCHEDULED_ANCESTOR_ALERT', [
                'name' => (string)$ancestor->getI18nAttribute('name'),
                'date' => $ancestor->publish_date ? Yii::$app->getFormatter()->asDatetime($ancestor->publish_date, 'short') : '',
            ]));
        }

        $this->icon ??= 'clock';

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Alert::make()
            ->attributes($this->attributes)
            ->content(...$this->content)
            ->warning()
            ->icon($this->icon);
    }

    #[Override]
    public function isVisible(): bool
    {
        return $this->content !== [] && parent::isVisible();
    }
}
