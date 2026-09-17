<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Modules\Admin\Data\BlockActiveDataProvider;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Widgets\Buttons\CreateButton;
use Hirtz\Skeleton\Widgets\Navs\Header;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\ProviderTrait;
use Override;
use Stringable;
use Yii;

class BlockHeader extends Header
{
    /**
     * @use ModelTrait<Block>
     */
    use ModelTrait;
    use ModuleTrait;

    /**
     * @use ProviderTrait<BlockActiveDataProvider|null>
     */
    use ProviderTrait;

    #[Override]
    protected function configure(): void
    {
        $this->addEntriesBreadcrumb();
        $this->addBreadcrumb(Yii::t('cms', 'COMMON_BLOCKS'), ['/admin/cms/block/index']);

        if ($this->model) {
            $this->title ??= $this->model->getOldAttribute($this->model->getI18nAttributeName('name'));
            $this->url ??= $this->model->getAdminRoute();
        }

        if ($this->provider) {
            $this->subtitle ??= $this->getPaginationSubtitle($this->provider);
            $this->title ??= Yii::t('cms', 'COMMON_BLOCKS');
            $this->url ??= ['/admin/cms/block/index'];

            $this->addContent($this->getCreateBlockButton());
        }

        parent::configure();
    }

    protected function addEntriesBreadcrumb(): void
    {
        $this->addBreadcrumb(Yii::t('cms', 'COMMON_ENTRIES'), [
            '/admin/cms/entry/index',
            'type' => static::getModule()->defaultEntryType,
        ]);
    }

    protected function getCreateBlockButton(): ?Stringable
    {
        return CreateButton::make()
            ->label(Yii::t('cms', 'BLOCK_CREATE_BUTTON'))
            ->icon('plus')
            ->roles([Block::AUTH_BLOCK])
            ->url(['/admin/cms/block/create']);
    }
}
