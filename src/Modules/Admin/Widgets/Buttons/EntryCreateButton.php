<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Yii;

class EntryCreateButton extends CreateButton
{
    public function __construct(array $config = [])
    {
        $this->label ??= Lang::t('cms', 'ENTRY_CREATE_CREATE_ENTRY');
        $this->roles ??= [Entry::AUTH_ENTRY_CREATE];

        $this->url ??= [
            '/admin/cms/entry/create',
            ...Yii::$app->getRequest()->getQueryParams(),
            'type' => $this->view->params['entryType'] ?? null,
        ];

        parent::__construct($config);
    }
}
