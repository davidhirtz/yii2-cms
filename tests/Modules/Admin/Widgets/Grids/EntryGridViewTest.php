<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Types\EntryType;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Grids\Toolbars\StatusFilterDropdown;
use Hirtz\Skeleton\Widgets\Widget;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Web\UrlManager;
use Override;
use Yii;
use yii\base\Event;

/**
 * The name column composes the entry link with the frontend URL and the category buttons, both of which a project
 * turns off — and a grid rendering neither used to hand back the link itself rather than a string.
 */
class EntryGridViewTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheIndexRendersWithoutTheUrlAndTheCategories(): void
    {
        Yii::$container->set(EntryGridView::class, ['showUrl' => false, 'showCategories' => false]);

        $this->login();
        $this->createEntry('Needle', 'needle');

        $html = Yii::$app->runAction('admin/cms/entry/index');

        self::assertIsString($html);
        self::assertStringContainsString('Needle', $html);
    }

    public function testTheIndexRendersTheUrlAndTheCategories(): void
    {
        Entry::getModule()->enableCategories = true;

        $this->login();
        $this->createEntry('Needle', 'needle');

        $html = Yii::$app->runAction('admin/cms/entry/index');

        self::assertIsString($html);
        self::assertStringContainsString('Needle', $html);
    }

    /**
     * The category buttons and the dropdown follow the module's `enableCategories`, which wins over a grid
     * setting them: a type or a grid only ever narrows what the installation turned on.
     */
    public function testTheCategoryDropdownFollowsTheModule(): void
    {
        $category = Category::create();
        $category->loadDefaultValues();
        $category->name = 'Haystack';
        $category->slug = 'haystack';
        self::assertTrue($category->insert(), print_r($category->getErrors(), true));
        CategoryCollection::getAll(true);

        $this->login();
        $this->createEntry('Needle', 'needle');

        Entry::getModule()->enableCategories = false;
        $html = Yii::$app->runAction('admin/cms/entry/index');
        self::assertIsString($html);
        self::assertStringNotContainsString('Haystack', $html);

        Yii::$container->set(EntryGridView::class, ['showCategoryDropdown' => true]);
        $html = Yii::$app->runAction('admin/cms/entry/index');
        self::assertIsString($html);
        self::assertStringNotContainsString('Haystack', $html);
        Yii::$container->clear(EntryGridView::class);

        Entry::getModule()->enableCategories = true;
        $html = Yii::$app->runAction('admin/cms/entry/index');
        self::assertIsString($html);
        self::assertStringContainsString('Haystack', $html);
    }

    public function testTheContainerTurnsTheTypeDropdownOff(): void
    {
        Yii::$container->set(Entry::class, DatedEntry::class);
        Entry::instance(true);

        try {
            $this->login();
            $this->createEntry('Needle', 'needle');

            $html = Yii::$app->runAction('admin/cms/entry/index');
            self::assertIsString($html);
            self::assertStringContainsString('Undated', $html);

            Yii::$container->set(EntryGridView::class, ['showTypeDropdown' => false]);
            $html = Yii::$app->runAction('admin/cms/entry/index');
            self::assertIsString($html);
            self::assertStringNotContainsString('Undated', $html);
        } finally {
            Yii::$container->clear(EntryGridView::class);
            Yii::$container->clear(Entry::class);
            Entry::instance(true);
        }
    }

    /**
     * A status per item, posting the model's form name with the checked rows; entries have no bulk delete.
     */
    public function testASelectionOfEntriesChangesTheirStatus(): void
    {
        $this->login();
        $this->createEntry('First', 'first');

        $html = Yii::$app->runAction('admin/cms/entry/index');
        self::assertIsString($html);
        self::assertStringNotContainsString('entry/update-all', $html);

        $this->createEntry('Second', 'second');

        $html = Yii::$app->runAction('admin/cms/entry/index');
        self::assertIsString($html);
        self::assertStringContainsString('name="selection[]"', $html);
        self::assertStringContainsString('hx-post="/admin/cms/entry/update-all"', $html);
        self::assertStringContainsString(
            'hx-vals="' . htmlspecialchars((string)json_encode(['Entry[status]' => Entry::STATUS_DRAFT])) . '"',
            $html,
        );
        self::assertStringNotContainsString('delete-all', $html);
    }

    public function testAnEmptyIndexSaysWhatEntriesAre(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry/index');
        self::assertIsString($html);
        self::assertStringContainsString(Yii::t('cms', 'ENTRY_GRID_SUMMARY_EMPTY'), $html);
        self::assertStringContainsString('Click <a href="/admin/cms/entry/create">here</a> to create the first entry.', $html);

        $this->createEntry('Needle', 'needle');

        $this->getWebRequest()->setQueryParams(['q' => 'haystack']);
        $html = Yii::$app->runAction('admin/cms/entry/index', ['q' => 'haystack']);
        self::assertIsString($html);
        self::assertStringNotContainsString(Yii::t('cms', 'ENTRY_GRID_SUMMARY_EMPTY'), $html);
        self::assertStringNotContainsString('to create the first entry', $html);
    }

    public function testAnEntryLackingATranslationSaysWhichOne(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        // The query layer reads `i18nAttributes` off the shared instance(), so it has to be configured.
        Yii::$container->setDefinitions([
            Entry::class => ['class' => TestEntry::class, 'i18nAttributes' => ['name', 'slug', 'description']],
            TestEntry::class => ['i18nAttributes' => ['name', 'slug', 'description']],
        ]);

        Entry::instance(true);
        TestEntry::instance(true);

        try {
            $this->login();
            $entry = $this->createEntry('Needle', 'needle');

            // A required attribute is copied into an empty translation on save; an optional one is what goes missing.
            $entry->setAttribute('description', 'Sharp');
            self::assertTrue($entry->save(), print_r($entry->getErrors(), true));

            $html = Yii::$app->runAction('admin/cms/entry/index');
            self::assertIsString($html);
            self::assertStringContainsString(Yii::t('skeleton', 'GRID_MISSING_TRANSLATIONS', ['languages' => 'DE']), $html);
            self::assertStringContainsString('class="text-warning fas fa-exclamation-triangle" title="' . Yii::t('skeleton', 'GRID_MISSING_TRANSLATIONS', ['languages' => 'DE']), $html);

            $entry->setAttribute('description_de', 'Spitz');
            self::assertTrue($entry->save(), print_r($entry->getErrors(), true));

            $html = Yii::$app->runAction('admin/cms/entry/index');
            self::assertIsString($html);
            self::assertStringNotContainsString(Yii::t('skeleton', 'GRID_MISSING_TRANSLATIONS', ['languages' => 'DE']), $html);
            self::assertStringNotContainsString('text-warning', $html, 'Nothing missing on the page, no column.');
        } finally {
            Yii::$container->clear(Entry::class);
            Yii::$container->clear(TestEntry::class);
            Entry::instance(true);
            TestEntry::instance(true);
        }
    }

    public function testTheIndexFiltersByStatus(): void
    {
        $this->login();
        $this->createEntry('Published', 'published');
        $this->createEntry('Unfinished', 'unfinished', status: Entry::STATUS_DRAFT);

        $html = Yii::$app->runAction('admin/cms/entry/index', ['status' => Entry::STATUS_DRAFT]);

        self::assertIsString($html);
        self::assertStringContainsString('Unfinished', $html);
        self::assertStringNotContainsString('Published', $html);
    }

    /**
     * The README's way of adding the status dropdown the default header leaves out.
     */
    public function testAListenerAddsTheStatusDropdown(): void
    {
        $handler = static function (Event $event): void {
            $grid = $event->sender;
            self::assertInstanceOf(EntryGridView::class, $grid);

            $grid->header(fn (array $header): array => [
                ...$header,
                StatusFilterDropdown::make()->model(Entry::instance()),
            ]);
        };

        $this->login();
        $this->createEntry('Needle', 'needle');

        $html = Yii::$app->runAction('admin/cms/entry/index');
        self::assertIsString($html);
        self::assertStringNotContainsString('status=' . Entry::STATUS_DRAFT, $html);

        Event::on(EntryGridView::class, Widget::EVENT_CONFIGURE, $handler);

        try {
            $html = Yii::$app->runAction('admin/cms/entry/index');
        } finally {
            Event::off(EntryGridView::class, Widget::EVENT_CONFIGURE, $handler);
        }

        self::assertIsString($html);
        self::assertStringContainsString('status=' . Entry::STATUS_DRAFT, $html);
    }

    /**
     * The request's tenant has no entries, another one does — and the dropdown is the only way to it.
     */
    public function testAnEmptyTenantKeepsTheTenantDropdown(): void
    {
        $empty = $this->createTenant('Empty Tenant', 'https://www.empty-domain.localhost');
        $other = $this->createTenant('Other Tenant', 'https://www.other-domain.localhost');

        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);
        $manager->setTenant($empty);

        $this->login();
        $this->createEntry('Needle', 'needle', $other);

        $html = Yii::$app->runAction('admin/cms/entry/index');

        self::assertIsString($html);
        self::assertStringNotContainsString('Needle', $html);
        self::assertStringContainsString('tenant=' . $other->id, $html);
    }

    private function createTenant(string $name, string $url): Tenant
    {
        $tenant = Tenant::create();
        $tenant->loadDefaultValues();
        $tenant->name = $name;
        $tenant->language = Yii::$app->sourceLanguage;
        $tenant->url = $url;

        self::assertTrue($tenant->save(), implode(' ', $tenant->getErrorSummary(true)));

        return $tenant;
    }

    private function createEntry(
        string $name,
        string $slug,
        ?Tenant $tenant = null,
        int $status = Entry::STATUS_ENABLED,
    ): Entry {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = $status;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = $name;
        $entry->slug = $slug;

        if ($tenant) {
            $entry->populateTenantRelation($tenant);
        }

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }

    /**
     * The date shows for a type whose form shows it, whatever the order: one sorted by a flag first and the date
     * second used to list when it was last edited instead.
     */
    public function testATypeShowingTheDateListsIt(): void
    {
        self::assertTrue($this->hasPublishDate(DatedEntry::TYPE_DATED));
    }

    public function testATypeHidingTheDateListsTheUpdate(): void
    {
        self::assertFalse($this->hasPublishDate(DatedEntry::TYPE_UNDATED));
    }

    public function testAGridOfEveryTypeListsTheDateWhenItsOrderUsesIt(): void
    {
        self::assertTrue($this->hasPublishDate(null, ['is_featured' => SORT_DESC, 'publish_date' => SORT_DESC]));
        self::assertFalse($this->hasPublishDate(null, ['position' => SORT_ASC]));
    }

    /**
     * @param array<string, int>|null $orderBy
     */
    private function hasPublishDate(?int $type, ?array $orderBy = null): bool
    {
        Yii::$container->set(Entry::class, DatedEntry::class);
        Entry::instance(true);

        try {
            $provider = Yii::$container->get(EntryActiveDataProvider::class, config: ['type' => $type]);

            if ($orderBy !== null) {
                $provider->query->orderBy($orderBy);
            }

            return DateColumnGridView::make()->provider($provider)->exposesPublishDate();
        } finally {
            Yii::$container->clear(Entry::class);
            Entry::instance(true);
        }
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Entry::AUTH_ENTRY);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}

/**
 * @extends EntryGridView<Entry>
 */
class DateColumnGridView extends EntryGridView
{
    public function exposesPublishDate(): bool
    {
        return $this->hasPublishDate();
    }
}

class DatedEntry extends Entry
{
    public const int TYPE_DATED = 1;
    public const int TYPE_UNDATED = 2;

    /**
     * @return list<EntryType>
     */
    #[Override]
    public function getTypes(): array
    {
        return [
            EntryType::make(self::TYPE_DATED)
                ->name('Dated')
                ->orderBy(['position' => SORT_ASC]),
            EntryType::make(self::TYPE_UNDATED)
                ->name('Undated')
                ->hiddenFields('publish_date'),
        ];
    }
}
