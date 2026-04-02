<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Buttons;

use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;
use Yii;

class EntryCreateButton extends Widget
{
    use TagAttributesTrait;

    protected function renderContent(): string|Stringable
    {
        $route = [
            '/admin/entry/create',
            ...Yii::$app->getRequest()->getQueryParams(),
            'type' => Yii::$app->getView()->params['entryType'] ?? null,
        ];

        return CreateButton::make()
            ->attributes($this->attributes)
            ->href($route)
            ->roles([Entry::AUTH_ENTRY_CREATE])
            ->text(Yii::t('cms', 'New Entry'));
    }
}
