<?php
declare(strict_types=1);

/**
 * @see \Hirtz\Cms\Modules\Admin\Controllers\SectionEntryController::actionIndex()
 *
 * @var View $this
 * @var EntryActiveDataProvider $provider
 */

use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\SectionEntryGridView;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\CmsSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Bootstrap\Panel;

$this->title(Yii::t('cms', 'Link entries'));
?>

<?= CmsSubmenu::widget([
    'model' => $provider->section,
]); ?>

<?php $this->setBreadcrumbs([
    Yii::t('cms', 'Entries') => $provider->section->getAdminRoute() + ['#' => 'entries'],
    Yii::t('cms', 'Link entries'),
]); ?>

<?= Panel::widget([
    'content' => SectionEntryGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>
