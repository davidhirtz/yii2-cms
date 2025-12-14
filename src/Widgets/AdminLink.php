<?php

declare(strict_types=1);

namespace Hirtz\Cms\widgets;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Yii;
use yii\helpers\Html;

class AdminLink extends Widget
{
    public Asset|Entry|Section|null $model = null;

    public array $linkOptions = [
        'class' => 'admin overlay',
        'target' => '_blank',
    ];

    #[Override]
    public function render(bool $refresh = false): string
    {
        return $this->canUpdateModel() && ($route = $this->model->getAdminRoute())
            ? Html::a('', $route, $this->linkOptions)
            : '';
    }

    protected function canUpdateModel(): bool
    {
        if ($this->model instanceof Entry) {
            return Yii::$app->getUser()->can(Entry::AUTH_ENTRY_UPDATE, ['entry' => $this->model]);
        }

        if ($this->model instanceof Section) {
            return Yii::$app->getUser()->can(Section::AUTH_SECTION_UPDATE, ['section' => $this->model]);
        }

        if ($this->model instanceof Asset) {
            $permissionName = $this->model->isEntryAsset() ? Entry::AUTH_ENTRY_ASSET_UPDATE : Section::AUTH_SECTION_ASSET_UPDATE;
            return Yii::$app->getUser()->can($permissionName, ['asset' => $this->model]);
        }

        return false;
    }

    public static function tag(Asset|Entry|Section $model): string
    {
        return static::widget(['model' => $model]);
    }
}
