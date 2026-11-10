<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Links\EntryLinks;
use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\TypeFilterDropdown;
use Override;
use Stringable;
use Traversable;
use Yii;

/**
 * @extends EntryGridView<Entry>
 * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryLinkController::actionIndex()
 */
class EntryLinkGridView extends EntryGridView
{
    protected bool $showSelection = false;
    protected bool $showTenantDropdown = false;

    /**
     * @var list<int>
     */
    protected array $types = [];

    protected ?string $language = null;

    protected string $layout = '{header}{summary}{items}{pager}';

    public function showTenantDropdown(bool $showTenantDropdown = true): static
    {
        $this->showTenantDropdown = $showTenantDropdown;
        return $this;
    }

    /**
     * @param list<int> $types
     */
    public function types(array $types): static
    {
        $this->types = $types;
        return $this;
    }

    public function language(?string $language): static
    {
        $this->language = $language;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->attributes['id'] ??= 'entry-link-grid-view';
        parent::configure();
    }

    #[Override]
    protected function getTenantDropdown(): ?Stringable
    {
        return $this->showTenantDropdown ? parent::getTenantDropdown() : null;
    }

    #[Override]
    protected function getTypeDropdown(): ?Stringable
    {
        return TypeFilterDropdown::make()
            ->items($this->getTypeDropdownItems())
            ->model(Entry::instance())
            ->visible($this->showTypeDropdown);
    }

    /**
     * @return array<int, Type>
     */
    protected function getTypeDropdownItems(): array
    {
        $items = Entry::instance()::getTypeDefinitions();
        return $this->types ? array_intersect_key($items, array_flip($this->types)) : $items;
    }

    #[Override]
    protected function isPicker(): bool
    {
        return true;
    }

    /**
     * Muted under the name, in the language of the content being edited; a type in place of a URL the entry does
     * not have yet.
     */
    #[Override]
    protected function getUrl(Entry $entry): ?Stringable
    {
        $language = $this->language ?? Yii::$app->getI18n()->getContentLanguage();

        $url = Yii::$app->getI18n()->callback($language, fn (): ?string => EntryLinks::getDisplayUrl(
            $entry,
            $this->provider->tenantId,
        ));

        return Div::make()
            ->class('small text-muted')
            ->text($url ?? $entry->getTypeName());
    }

    /**
     * @return Traversable<int, Stringable>
     */
    #[Override]
    protected function getButtonColumnContent(Entry $entry): Traversable
    {
        yield $this->getAdminLinkButton($entry);

        $label = Yii::t('cms', 'ENTRY_LINK_SELECT_TOOLTIP');

        yield Button::make()
            ->primary()
            ->type('button')
            ->icon('check')
            ->tooltip($label)
            ->attribute('aria-label', $label)
            ->attribute('data-picker-value', EntryLinks::SCHEME . ':' . $entry->id)
            ->attribute('data-picker-title', $entry->getI18nAttribute('name'));
    }
}
