<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Buttons;

use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Override;
use Yii;

class EntryCreateButton extends CreateButton
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->label ??= Yii::t('cms', 'ENTRY_CREATE_BUTTON');
        $this->roles ??= [Entry::AUTH_ENTRY];

        // The grid's own filters travel with the button, the type among them (monorepo issue #161) — it used to
        // be overwritten with the one a project pins the page to, and dropped whenever that was nothing.
        $this->url ??= [
            '/admin/cms/entry/create',
            ...Application::current()->getRequest()->getQueryParams(),
        ];

        parent::__construct($config);
    }

    /**
     * The type a project pinned the page to is the fallback, and is read here rather than in the constructor:
     * `$this->view` is only assigned by the parent's, and `??` answers `null` for an uninitialized typed
     * property instead of erroring — so in the constructor the whole expression was silently dead.
     */
    #[Override]
    protected function configure(): void
    {
        $entryType = $this->view->params['entryType'] ?? null;

        if ($entryType !== null && is_array($this->url) && !isset($this->url['type'])) {
            $this->url['type'] = $entryType;
        }

        parent::configure();
    }
}
