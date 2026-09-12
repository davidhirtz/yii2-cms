<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Buttons;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class SectionEntryCreateButton extends CreateButton
{
    /**
     * @use ModelTrait<Section>
     */
    use ModelTrait;

    public function __construct(array $config = [])
    {
        $this->label ??= Lang::t('cms', 'SECTION_ENTRY_CREATE_BUTTON');
        $this->roles ??= [Entry::AUTH_ENTRY_UPDATE];

        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $entryTypes = $this->model->getEntriesTypes();

        $this->url = [
            '/admin/cms/section-entry/create',
            ...Yii::$app->getRequest()->getQueryParams(),
            'section' => $this->model->id,
            'type' => $entryTypes ? current($entryTypes) : null,
        ];

        parent::configure();
    }
}
