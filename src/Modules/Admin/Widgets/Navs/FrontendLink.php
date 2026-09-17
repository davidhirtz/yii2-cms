<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\Buttons\Traits\FrontendUrlTrait;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;

class FrontendLink extends Widget
{
    use FrontendUrlTrait;
    use TagAttributesTrait;
    use UrlTrait;

    /**
     * The nearest record up the admin chain that has a frontend URL at all: an asset and a hotspot have none of
     * their own, so their pages link to whatever they hang on. `null` where nothing in the chain does — a block
     * and everything under it.
     */
    public static function findInChain(?AdminModelInterface $model): ?static
    {
        while ($model) {
            if ($model instanceof Category || $model instanceof Entry || $model instanceof Section) {
                return static::make()->model($model);
            }

            $model = $model->getAdminParent();
        }

        return null;
    }

    #[Override]
    protected function configure(): void
    {
        $this->configureDefaultUrl();

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return A::make()
            ->attributes($this->attributes)
            ->addClass($this->isDisabled() ? 'text-invalid' : null)
            ->text(is_array($this->url) ? Url::to($this->url) : $this->url)
            ->href($this->url)
            ->target('_blank')
            ->render();
    }
}
