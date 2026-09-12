<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Override;
use Yii;

/**
 * The records `selectWith()` reads off the join are populated by the relation's own query, so the translations of
 * a translated model come with one query for the whole list rather than one per record.
 */
class PermalinkEntrySelectWithTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        Yii::$container->setDefinitions([
            Entry::class => ['class' => TestEntry::class, 'i18nAttributes' => ['name']],
            TestEntry::class => ['i18nAttributes' => ['name']],
        ]);

        Entry::instance(true);
        TestEntry::instance(true);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Entry::class);
        Yii::$container->clear(TestEntry::class);

        Entry::instance(true);
        TestEntry::instance(true);

        parent::tearDown();
    }

    public function testJoinedEntriesEagerLoadTheirTranslations(): void
    {
        $ids = [
            $this->createEntry('Contact', 'Kontakt', 'contact')->id,
            $this->createEntry('About', 'Über uns', 'about')->id,
        ];

        $permalinks = [];

        $count = $this->countQueries(function () use (&$permalinks, $ids): void {
            $permalinks = Permalink::find()
                ->selectWith('entry')
                ->andWhere([Permalink::tableName() . '.[[entry_id]]' => $ids])
                ->all();
        });

        // The permalinks with their entries, then the entries' translations.
        self::assertSame(2, $count);
        self::assertCount(2, $permalinks);

        $names = [];

        $count = $this->countQueries(function () use ($permalinks, &$names): void {
            foreach ($permalinks as $permalink) {
                $names[] = $permalink->entry->getI18nAttribute('name', 'de');
            }
        });

        self::assertSame(0, $count);
        self::assertEqualsCanonicalizing(['Kontakt', 'Über uns'], $names);
    }

    public function testASingleJoinedEntryStaysLazyForTranslations(): void
    {
        $id = $this->createEntry('Contact', 'Kontakt', 'contact')->id;

        $permalink = Permalink::find()
            ->selectWith('entry')
            ->andWhere([Permalink::tableName() . '.[[entry_id]]' => $id])
            ->one();

        self::assertTrue($permalink->isRelationPopulated('entry'));
        self::assertSame('Contact', $permalink->entry->name);
        self::assertSame('Kontakt', $permalink->entry->getI18nAttribute('name', 'de'));
    }

    protected function createEntry(string $name, string $nameDe, string $slug): TestEntry
    {
        $entry = TestEntry::create();
        $entry->name = $name;
        $entry->setAttribute('name_de', $nameDe);
        $entry->slug = $slug;

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        return $entry;
    }
}
