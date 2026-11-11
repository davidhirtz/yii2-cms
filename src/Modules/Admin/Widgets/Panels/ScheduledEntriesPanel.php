<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Panels\DashboardPanel;
use Override;
use Stringable;
use Yii;

/**
 * Renders nothing until an entry is scheduled, read off the cached next publish time first.
 */
class ScheduledEntriesPanel extends DashboardPanel
{
    use ModuleTrait;

    protected int $limit = 8;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->roles ??= [Entry::AUTH_ENTRY];
        parent::__construct($config);
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->title ??= Yii::t('cms', 'ENTRY_SCHEDULED_TITLE');
        $this->icon ??= 'calendar-days';

        parent::configure();
    }

    #[Override]
    protected function getBody(): string|Stringable
    {
        if (static::getModule()->getNextPublishTime() === null) {
            return '';
        }

        $entries = Entry::find()
            ->whereScheduled()
            ->withTranslations()
            ->orderBy(['publish_date' => SORT_ASC, 'id' => SORT_ASC])
            ->limit($this->limit)
            ->all();

        if (!$entries) {
            return '';
        }

        $list = Ul::make()->class('dashboard-list');
        $formatter = Yii::$app->getFormatter();

        foreach ($entries as $entry) {
            $route = $entry->getAdminRoute();
            $name = $entry->getAdminName();

            $list->addContent(Li::make()->content(
                Div::make()
                    ->class('dashboard-list-title')
                    ->content($route ? A::make()->href($route)->text($name) : Span::make()->text($name)),
                Div::make()
                    ->class('dashboard-list-meta small text-muted')
                    ->content(
                        Span::make()->text($entry->getAdminType()),
                        Span::make()->text($formatter->asDatetime($entry->publish_date, 'short')),
                        Span::make()->content(RelativeTime::make()->value($entry->publish_date)),
                    ),
            ));
        }

        return $list;
    }
}
