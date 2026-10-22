<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UpgradeMigrationTrait;
use Override;
use RuntimeException;
use Yii;
use yii\db\Command;
use yii\db\Query;

/**
 * The permalink table is replaced by the one the migration creates, and restored with its rows afterwards. The
 * DDL commits the test transaction, which is why everything is undone by hand.
 */
class PermalinkMigrationTest extends TestCase
{
    use CmsFixtureTrait;
    use UpgradeMigrationTrait;

    private const string MIGRATION = 'Hirtz\\Cms\\Migrations\\M260909100000Permalink';
    private const string BACKUP = 'permalink_migration_test';

    private string $createTable;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireUpgradeMigration('yii2-cms', self::MIGRATION);

        $db = Yii::$app->getDb();
        $table = $db->getSchema()->getRawTableName(Permalink::tableName());

        $row = $db->createCommand("SHOW CREATE TABLE `$table`")->queryOne();
        self::assertNotFalse($row);

        $this->createTable = (string)$row['Create Table'];

        $db->createCommand('CREATE TABLE ' . self::BACKUP . ' AS SELECT * FROM ' . Permalink::tableName())->execute();
        $db->createCommand()->dropTable(Permalink::tableName())->execute();

        $this->addEntryColumns(['slug' => 'varchar(100) NULL', 'parent_slug' => 'varchar(255) NULL']);
    }

    #[Override]
    protected function tearDown(): void
    {
        $db = Yii::$app->getDb();

        if (isset($this->createTable)) {
            if ($db->getTableSchema(Permalink::tableName(), true) !== null) {
                $db->createCommand()->dropTable(Permalink::tableName())->execute();
            }

            $db->createCommand($this->createTable)->execute();
            $db->getSchema()->refreshTableSchema(Permalink::tableName());
            $db->createCommand('INSERT INTO ' . Permalink::tableName() . ' SELECT * FROM ' . self::BACKUP)->execute();
            $db->createCommand()->dropTable(self::BACKUP)->execute();
        }

        foreach ($db->getTableSchema(Entry::tableName(), true)?->getColumnNames() ?? [] as $column) {
            if (str_contains($column, 'slug')) {
                $db->createCommand()->dropColumn(Entry::tableName(), $column)->execute();
            }
        }

        InterruptedPermalinkCommand::$enabled = false;

        parent::tearDown();
    }

    public function testTheSlugsBecomePermalinks(): void
    {
        $this->updateEntry(1, ['slug' => 'alpha']);
        $this->updateEntry(2, ['slug' => 'beta', 'parent_slug' => 'alpha']);

        $this->migrate();

        self::assertSame(['alpha', 'alpha/beta'], self::findUris(Permalink::LANGUAGE_ALL));
        self::assertNull(Yii::$app->getDb()->getTableSchema(Entry::tableName(), true)?->getColumn('slug'));
    }

    /**
     * `NULL` and `''` compute the same URI, and v2's unique index on `(slug, parent_slug)` let a `NULL` repeat.
     */
    public function testEntriesSharingAUriAreRefusedBeforeAnyIsWritten(): void
    {
        $this->updateEntry(1, ['slug' => 'alpha']);
        $this->updateEntry(2, ['slug' => 'Alpha', 'parent_slug' => '']);
        $this->updateEntry(3, ['slug' => 'gamma']);

        try {
            $this->migrate();
            self::fail('Entries sharing a URI were not refused.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('entries 1,2', $exception->getMessage());
        }

        self::assertSame([], self::findUris(Permalink::LANGUAGE_ALL));
        self::assertNotNull(Yii::$app->getDb()->getTableSchema(Entry::tableName(), true)?->getColumn('slug'));

        $this->updateEntry(2, ['slug' => 'alpha-2']);
        $this->migrate();

        self::assertSame(['alpha', 'alpha-2', 'gamma'], self::findUris(Permalink::LANGUAGE_ALL));
    }

    public function testARunInterruptedBeforeTheColumnsAreDroppedCanBeRepeated(): void
    {
        $this->updateEntry(1, ['slug' => 'alpha']);

        $db = clone Yii::$app->getDb();
        $db->commandClass = InterruptedPermalinkCommand::class;
        InterruptedPermalinkCommand::$enabled = true;

        try {
            $this->migrate(['db' => $db]);
            self::fail('The migration was not interrupted.');
        } catch (RuntimeException) {
        }

        $db->close();

        self::assertSame(['alpha'], self::findUris(Permalink::LANGUAGE_ALL));

        $this->migrate();

        self::assertSame(['alpha'], self::findUris(Permalink::LANGUAGE_ALL));
    }

    public function testATranslatedSlugGetsAPermalinkPerLanguage(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$container->set(Entry::class, ['i18nAttributes' => ['slug']]);

        $this->addEntryColumns(['slug_de' => 'varchar(100) NULL', 'parent_slug_de' => 'varchar(255) NULL']);
        $this->updateEntry(1, ['slug' => 'contact', 'slug_de' => 'kontakt']);
        $this->updateEntry(2, ['slug' => 'team', 'parent_slug' => 'contact', 'slug_de' => 'team', 'parent_slug_de' => 'kontakt']);

        $this->migrate();

        self::assertSame(['contact', 'contact/team'], self::findUris('en-US'));
        self::assertSame(['kontakt', 'kontakt/team'], self::findUris('de'));
        self::assertNull(Yii::$app->getDb()->getTableSchema(Entry::tableName(), true)?->getColumn('slug_de'));
    }

    /**
     * The languages come from the configuration at migrate time, which may no longer translate the slug.
     */
    public function testATranslatedSlugColumnNoLanguageReadsIsRefused(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $this->addEntryColumns(['slug_de' => 'varchar(100) NULL', 'parent_slug_de' => 'varchar(255) NULL']);
        $this->updateEntry(1, ['slug' => 'contact', 'slug_de' => 'kontakt']);

        try {
            $this->migrate();
            self::fail('A translated slug column was left behind.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('slug_de, parent_slug_de', $exception->getMessage());
        }

        self::assertSame([], self::findUris(Permalink::LANGUAGE_ALL));
        self::assertNotNull(Yii::$app->getDb()->getTableSchema(Entry::tableName(), true)?->getColumn('slug_de'));
    }

    /**
     * @param array<string, string> $columns
     */
    private function addEntryColumns(array $columns): void
    {
        foreach ($columns as $column => $type) {
            Yii::$app->getDb()->createCommand()->addColumn(Entry::tableName(), $column, $type)->execute();
        }
    }

    /**
     * @param array<string, string> $values
     */
    private function updateEntry(int $id, array $values): void
    {
        Yii::$app->getDb()->createCommand()->update(Entry::tableName(), $values, ['id' => $id])->execute();
    }

    /**
     * @param array<string, mixed> $config
     */
    private function migrate(array $config = []): void
    {
        $this->createUpgradeMigration('yii2-cms', self::MIGRATION, ['compact' => true, ...$config])->safeUp();
    }

    /**
     * @return list<string>
     */
    private static function findUris(string $language): array
    {
        if (Yii::$app->getDb()->getTableSchema(Permalink::tableName(), true) === null) {
            return [];
        }

        return array_values(array_map(strval(...), (new Query())
            ->select(['uri'])
            ->from(Permalink::tableName())
            ->where(['language' => $language])
            ->orderBy(['uri' => SORT_ASC])
            ->column(Yii::$app->getDb())));
    }
}

final class InterruptedPermalinkCommand extends Command
{
    public static bool $enabled = false;

    #[Override]
    public function execute()
    {
        if (self::$enabled && str_contains($this->getRawSql(), 'DROP ')) {
            throw new RuntimeException('Interrupted.');
        }

        return parent::execute();
    }
}
