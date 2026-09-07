<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs\Traits;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Yii;

trait EntryHeaderTrait
{
    use ModuleTrait;

    protected int $maxParentBreadcrumbCount = 2;

    protected function addEntryBreadcrumbs(Entry $entry): void
    {
        $this->addBreadcrumb(Yii::t('app', 'Entries'), [
            '/admin/cms/entry/index',
            'type' => static::getModule()->defaultEntryType,
        ]);

        if ($entry->parent_id && $this->maxParentBreadcrumbCount > 0) {
            $isIndex = Yii::$app->requestedRoute === 'admin/cms/entry/index';
            $count = count($entry->getAncestors());

            if ($count > $this->maxParentBreadcrumbCount) {
                $this->addBreadcrumb('…');
            }

            foreach ($entry->ancestors as $ancestor) {
                if (--$count < $this->maxParentBreadcrumbCount) {
                    $this->addBreadcrumb($ancestor->getI18nAttribute('name'), $isIndex
                        ? ['index', 'parent' => $ancestor->id]
                        : $ancestor->getAdminRoute());
                }
            }
        }
    }
}
