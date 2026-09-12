<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Buttons;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class SectionCreateButton extends CreateButton
{
    /**
     * @use ModelTrait<Entry>
     */
    use ModelTrait;

    public function __construct(array $config = [])
    {
        $this->label ??= Yii::t('cms', 'SECTION_NEW_SECTION');
        $this->roles ??= [Section::AUTH_SECTION_CREATE];

        parent::__construct($config);
    }


    /**
     * @see SectionController::actionCreate()
     */
    #[Override]
    protected function configure(): void
    {
        $this->url = [
            '/admin/cms/section/create',
            'entry' => $this->model->id,
            ...Yii::$app->getRequest()->getQueryParams(),
        ];

        parent::configure();
    }
}
