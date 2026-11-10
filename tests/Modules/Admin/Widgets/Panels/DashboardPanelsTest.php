<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Panels;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Types\EntryType;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Panels\ScheduledEntriesPanel;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Override;
use Yii;

class DashboardPanelsTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheScheduledPanelShowsOnlyOnceSomethingIsScheduled(): void
    {
        $this->login();
        $this->createEntry('Already out', publishDate: '-1 hour');

        self::assertSame('', ScheduledEntriesPanel::make()->render());

        $future = $this->createEntry('Später 東京 😀', publishDate: '+2 days');
        $draft = $this->createEntry('Draft later', status: Entry::STATUS_DRAFT, publishDate: '+1 day');

        $html = ScheduledEntriesPanel::make()->render();

        self::assertStringContainsString('Später 東京 😀', $html);
        self::assertStringContainsString($this->getHref($future), $html);
        self::assertStringNotContainsString($this->getHref($draft), $html);
        self::assertStringNotContainsString('Already out', $html);
    }

    public function testTheScheduledPanelNeedsTheEntryPermission(): void
    {
        $this->createEntry('Scheduled', publishDate: '+1 day');
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        self::assertSame('', ScheduledEntriesPanel::make()->render());
    }

    public function testAnEntryLacksADescriptionOnlyWithAPageAndTheField(): void
    {
        $missing = $this->createEntry('Missing', sectionCount: 1);
        $blank = $this->createEntry('Blank', description: " \t ", sectionCount: 1);
        $described = $this->createEntry('Described', description: 'Ålesund ąę 東京 😀 Mu' . "\u{308}" . 'ller', sectionCount: 1);
        $pageless = $this->createEntry('Pageless');
        $draft = $this->createEntry('Draft', status: Entry::STATUS_DRAFT, sectionCount: 1);

        $ids = $this->getMissingDescriptionIds();

        self::assertContains($missing->id, $ids);
        self::assertContains($blank->id, $ids);
        self::assertNotContains($described->id, $ids);
        self::assertNotContains($pageless->id, $ids);
        self::assertNotContains($draft->id, $ids);
    }

    public function testATypeHidingTheDescriptionIsNotCounted(): void
    {
        $hidden = MissingDescriptionTestEntry::create();
        $hidden->loadDefaultValues();
        $hidden->status = Entry::STATUS_ENABLED;
        $hidden->type = MissingDescriptionTestEntry::TYPE_WITHOUT_DESCRIPTION;
        $hidden->name = 'Without field';
        $hidden->slug = 'without-field';

        self::assertTrue($hidden->insert(), print_r($hidden->getErrors(), true));
        $hidden->updateAttributes(['section_count' => 1]);

        $ids = MissingDescriptionTestEntry::find()->whereMissingDescription()->select('id')->column();
        self::assertNotContains($hidden->id, array_map(intval(...), $ids));
    }

    public function testTheGridListsTheMissingDescriptionsOfEveryLevel(): void
    {
        $this->login();

        $parent = $this->createEntry('Parent', description: 'Described', sectionCount: 1);
        $this->createEntry('Child without', parent: $parent, sectionCount: 1);

        $html = Yii::$app->runAction('admin/cms/entry/index', ['missing' => EntryActiveDataProvider::MISSING_DESCRIPTION]);

        self::assertIsString($html);
        self::assertStringContainsString('Child without', $html);
        self::assertStringNotContainsString('>Parent<', $html);
        self::assertStringContainsString(Yii::t('cms', 'ENTRY_MISSING_DESCRIPTION_LABEL'), $html);
    }

    public function testTheDashboardCountsTheMissingDescriptions(): void
    {
        $this->login();
        $this->createEntry('Missing', sectionCount: 1);

        $count = Yii::$container->get(EntryActiveDataProvider::class, config: [
            'missing' => EntryActiveDataProvider::MISSING_DESCRIPTION,
        ])->getTotalCount();

        $html = Dashboard::make()->render();

        self::assertGreaterThan(0, $count);
        self::assertStringContainsString(Yii::t('cms', 'ENTRY_MISSING_DESCRIPTION_TEXT', ['count' => $count]), $html);
        self::assertStringContainsString(Url::to(['/admin/cms/entry/index', 'missing' => 'description']), $html);
    }

    public function testADescriptionMissingInOneLanguageIsCountedForThatLanguage(): void
    {
        $this->withGermanDescriptions(function (): void {
            $this->login();

            $entry = $this->createEntry('Translated later', description: 'Sharp Å 東京', sectionCount: 1);

            self::assertNotContains($entry->id, $this->getMissingDescriptionIds());
            self::assertContains($entry->id, $this->getMissingDescriptionIds('de'));

            $html = Dashboard::make()->render();

            self::assertStringContainsString(Yii::t('cms', 'ENTRY_MISSING_DESCRIPTION_LANGUAGE_TEXT', [
                'count' => count($this->getMissingDescriptionIds('de')),
                'language' => Yii::$app->getI18n()->getLabel('de'),
            ]), $html);
            self::assertStringContainsString(Html::encode(Url::to([
                '/admin/cms/entry/index',
                'missing' => 'description',
                'language' => 'de',
            ])), $html);

            $entry->setAttribute('description_de', 'Spitz Mu' . "\u{308}" . 'ller 😀');
            self::assertTrue($entry->save(), print_r($entry->getErrors(), true));

            self::assertNotContains($entry->id, $this->getMissingDescriptionIds('de'));

            $params = ['missing' => 'description', 'language' => 'de'];
            $this->getWebRequest()->setQueryParams($params);

            $html = Yii::$app->runAction('admin/cms/entry/index', $params);
            self::assertIsString($html);
            self::assertStringNotContainsString('Translated later', $html);
            self::assertStringContainsString(Yii::t('cms', 'ENTRY_MISSING_DESCRIPTION_LANGUAGE_LABEL', [
                'language' => Yii::$app->getI18n()->getLabel('de'),
            ]), $html);
        });
    }

    /**
     * The query layer reads `i18nAttributes` off the shared instance(), so it has to be configured.
     */
    private function withGermanDescriptions(callable $test): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        Yii::$container->setDefinitions([
            Entry::class => ['class' => TestEntry::class, 'i18nAttributes' => ['name', 'slug', 'description']],
            TestEntry::class => ['i18nAttributes' => ['name', 'slug', 'description']],
        ]);

        Entry::instance(true);
        TestEntry::instance(true);

        try {
            $test();
        } finally {
            Yii::$container->clear(Entry::class);
            Yii::$container->clear(TestEntry::class);
            Entry::instance(true);
            TestEntry::instance(true);
        }
    }

    /**
     * @return list<int>
     */
    private function getMissingDescriptionIds(?string $language = null): array
    {
        return array_values(array_map(intval(...), Entry::find()->whereMissingDescription($language)->select(Entry::tableName() . '.[[id]]')->column()));
    }

    private function createEntry(
        string $name,
        int $status = Entry::STATUS_ENABLED,
        ?string $description = null,
        ?string $publishDate = null,
        ?Entry $parent = null,
        int $sectionCount = 0,
    ): Entry {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->populateParentRelation($parent);
        $entry->status = $status;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = $name;
        $entry->slug = 'dashboard-' . md5($name);
        $entry->description = $description;
        $entry->publish_date = $publishDate !== null ? new DateTime($publishDate) : null;

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        if ($sectionCount) {
            $entry->updateAttributes(['section_count' => $sectionCount]);
        }

        return $entry;
    }

    private function getHref(Entry $entry): string
    {
        $route = $entry->getAdminRoute();
        self::assertNotFalse($route);

        return 'href="' . Url::to($route) . '"';
    }

    private function login(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Entry::AUTH_ENTRY);

        $this->getWebUser()->setIdentity($user);
    }
}

class MissingDescriptionTestEntry extends Entry
{
    public const int TYPE_WITHOUT_DESCRIPTION = 2;

    #[Override]
    public function getTypes(): array
    {
        return [
            EntryType::make(self::TYPE_DEFAULT)
                ->name('Default'),
            EntryType::make(self::TYPE_WITHOUT_DESCRIPTION)
                ->name('Without description')
                ->hiddenFields('description'),
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Entry';
    }
}
