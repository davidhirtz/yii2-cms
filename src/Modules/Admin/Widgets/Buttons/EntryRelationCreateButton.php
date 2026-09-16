<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Buttons;

use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Override;
use Yii;

class EntryRelationCreateButton extends CreateButton
{
    /**
     * @use ModelTrait<(ActiveRecord&EntryRelationModelInterface)|null>
     */
    use ModelTrait;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->label ??= Yii::t('cms', 'ENTRY_RELATION_CREATE_BUTTON');

        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $entryRelationClass = $this->model->getEntryRelationClass();
        $entryTypes = $this->model->getEntriesTypes();

        $this->roles ??= [$entryRelationClass::instance()->getPermissionName()];

        $this->url = [
            $entryRelationClass::getAdminControllerRoute() . '/create',
            ...Application::current()->getRequest()->getQueryParams(),
            $this->model->getParamName() => $this->model->id,
            'type' => $entryTypes ? current($entryTypes) : null,
        ];

        parent::configure();
    }
}
