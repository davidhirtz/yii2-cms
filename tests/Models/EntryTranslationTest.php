<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Actions\DuplicateEntry;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Data\ActiveDataProvider;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Translation;
use Override;
use Yii;

/**
 * A translated entry splits across three tables: the source language stays in the entry row, the other languages go to
 * {@see Translation} — except the slug, which is backed by a {@see \Hirtz\Cms\Models\Permalink}.
 */
class EntryTranslationTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        // The query layer reads `i18nAttributes` off the shared model instance, so it has to be configured, not set
        // on a single record.
        Yii::$container->setDefinitions([
            Entry::class => ['class' => TestEntry::class, 'i18nAttributes' => ['name', 'slug']],
            TestEntry::class => ['i18nAttributes' => ['name', 'slug']],
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

    public function testTranslatedAttributesAreStoredInTheirOwnTable(): void
    {
        $entry = $this->createEntry(['name' => 'Contact', 'name_de' => 'Kontakt', 'slug' => 'contact', 'slug_de' => 'kontakt']);

        self::assertSame(['name' => 'Kontakt'], $this->getTranslations($entry));
        self::assertSame('kontakt', $entry->getPermalink('de')?->slug);

        $columns = Yii::$app->getDb()->getTableSchema($entry::tableName(), true)->getColumnNames();

        self::assertNotContains('name_de', $columns);
        self::assertNotContains('slug_de', $columns);
    }

    public function testTrailLogsBothTranslatedAttributes(): void
    {
        $entry = $this->createEntry(['name' => 'Contact', 'name_de' => 'Kontakt', 'slug' => 'contact', 'slug_de' => 'kontakt']);

        $entry->setAttributes(['name_de' => 'Kontaktseite', 'slug_de' => 'kontaktseite'], false);

        self::assertSame(1, $entry->update(), implode(' ', $entry->getErrorSummary(true)));

        $data = (array)Trail::find()
            ->where([
                'model' => $entry->getTrailBehavior()->modelClass,
                'model_id' => $entry->id,
            ])
            ->orderBy(['id' => SORT_DESC])
            ->one()
            ?->data;

        self::assertSame(['Kontakt', 'Kontaktseite'], $data['name_de']);
        self::assertSame(['kontakt', 'kontaktseite'], $data['slug_de']);
    }

    public function testSearchMatchesTheTranslationAndFallsBackToTheSourceLanguage(): void
    {
        $translated = $this->createEntry(['name' => 'Contact', 'name_de' => 'Kontakt', 'slug' => 'contact', 'slug_de' => 'kontakt']);
        $untranslated = $this->createEntry(['name' => 'Imprint', 'slug' => 'imprint', 'slug_de' => 'imprint-de']);

        Yii::$app->language = 'de';

        self::assertSame([$translated->id], $this->findIds('Kontakt'));
        self::assertSame([$untranslated->id], $this->findIds('Imprint'));

        // The German name wins over the English one it replaces.
        self::assertSame([], $this->findIds('Contact'));
    }

    public function testSortingUsesTheTranslationWithFallback(): void
    {
        $alpha = $this->createEntry(['name' => 'Zulu', 'name_de' => 'Alpha', 'slug' => 'zulu', 'slug_de' => 'zulu-de']);
        $bravo = $this->createEntry(['name' => 'Bravo', 'slug' => 'bravo', 'slug_de' => 'bravo-de']);
        $charlie = $this->createEntry(['name' => 'Alpha', 'name_de' => 'Charlie', 'slug' => 'alpha', 'slug_de' => 'alpha-de']);

        /** @var ActiveDataProvider<Entry> $provider */
        $provider = new ActiveDataProvider([
            'query' => Entry::find(),
            'sort' => ['defaultOrder' => ['name_de' => SORT_ASC]],
        ]);

        $ids = [];

        foreach ($provider->getModels() as $entry) {
            $ids[] = $entry->id;
        }

        self::assertSame([$alpha->id, $bravo->id, $charlie->id], $ids);
    }

    public function testEagerLoadedTranslationsCostOneQuery(): void
    {
        $this->createEntry(['name' => 'One', 'name_de' => 'Eins', 'slug' => 'one', 'slug_de' => 'eins']);
        $this->createEntry(['name' => 'Two', 'name_de' => 'Zwei', 'slug' => 'two', 'slug_de' => 'zwei']);
        $this->createEntry(['name' => 'Three', 'name_de' => 'Drei', 'slug' => 'three', 'slug_de' => 'drei']);

        $entries = [];

        $queries = $this->countQueries(function () use (&$entries): void {
            /** @var Entry[] $entries */
            $entries = Entry::find()
                ->withTranslations('de')
                ->orderBy(['id' => SORT_ASC])
                ->all();
        });

        self::assertCount(3, $entries);
        self::assertSame(2, $queries, 'Eager loading the translations took more than one extra query.');

        $queries = $this->countQueries(function () use ($entries): void {
            $names = [];

            foreach ($entries as $entry) {
                $names[] = $entry->getI18nAttribute('name', 'de');
            }

            self::assertSame(['Eins', 'Zwei', 'Drei'], $names);
        });

        self::assertSame(0, $queries, 'Reading an eager loaded translation queried the database.');
    }

    public function testDuplicateCarriesTranslations(): void
    {
        $entry = $this->createEntry(['name' => 'Contact', 'name_de' => 'Kontakt', 'slug' => 'contact', 'slug_de' => 'kontakt']);
        $duplicate = DuplicateEntry::create([Entry::findOne($entry->id)]);

        self::assertNotSame($entry->id, $duplicate->id);
        self::assertSame('Kontakt', $duplicate->getI18nAttribute('name', 'de'));
        self::assertSame(['name' => 'Kontakt'], $this->getTranslations($duplicate));
    }

    /**
     * @param array<string, string> $attributes
     */
    protected function createEntry(array $attributes): TestEntry
    {
        $entry = TestEntry::create();
        $entry->setAttributes($attributes, false);

        self::assertTrue($entry->save(), implode(' ', $entry->getErrorSummary(true)));

        return $entry;
    }

    /**
     * @return list<int>
     */
    protected function findIds(string $search): array
    {
        return array_map(intval(...), Entry::find()
            ->select(Entry::tableName() . '.[[id]]')
            ->matching($search)
            ->orderBy(['id' => SORT_ASC])
            ->column());
    }

    /**
     * @return array<string, string|null> the stored German value per attribute
     */
    protected function getTranslations(Entry $entry): array
    {
        $values = [];

        foreach (Translation::find()->whereModel(Entry::class, $entry->id)->whereLanguage('de')->all() as $translation) {
            $values[$translation->attribute] = $translation->value;
        }

        return $values;
    }
}
