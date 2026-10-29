<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UpgradeMigrationTrait;
use Override;
use RuntimeException;
use Yii;
use yii\db\Query;

/**
 * The DDL commits the test transaction, so the columns are put back by hand.
 */
class JsonMigrationTest extends TestCase
{
    use UpgradeMigrationTrait;

    use CmsFixtureTrait;

    private const string MIGRATION = 'Hirtz\\Cms\\Migrations\\M260906100000Json';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->requireUpgradeMigration('yii2-cms', self::MIGRATION);

        $db = Yii::$app->getDb();
        $db->createCommand()->update(Entry::tableName(), ['path' => null, 'category_ids' => null])->execute();
        $db->createCommand()->alterColumn(Entry::tableName(), 'path', 'varchar(255) DEFAULT NULL')->execute();
        $db->createCommand()->alterColumn(Entry::tableName(), 'category_ids', 'text DEFAULT NULL')->execute();
    }

    #[Override]
    protected function tearDown(): void
    {
        $db = Yii::$app->getDb();

        if ($db->getTableSchema(Entry::tableName(), true)?->getColumn('path')?->dbType !== 'longtext') {
            $db->createCommand()->update(Entry::tableName(), ['path' => null, 'category_ids' => null])->execute();
            $db->createCommand()->alterColumn(Entry::tableName(), 'path', 'json NULL')->execute();
            $db->createCommand()->alterColumn(Entry::tableName(), 'category_ids', 'json NULL')->execute();
        }

        parent::tearDown();
    }

    public function testACommaListBecomesAJsonList(): void
    {
        $this->setLegacyValues(4, '1', '1,3');
        $this->setLegacyValues(5, '1,4', null);

        $this->migrate();

        self::assertSame([[1], [1, 3]], $this->findValues(4));
        self::assertSame([[1, 4], null], $this->findValues(5));
    }

    /**
     * `intval()` read `[1` as `0`: the fixture's JSON lists came out as `[0,12]`, and nothing noticed.
     */
    public function testAValueThatIsNotACommaListOfIdsIsRefusedBeforeAnythingIsWritten(): void
    {
        $this->setLegacyValues(4, '1', null);
        $this->setLegacyValues(5, '1', '[1,12]');

        try {
            $this->migrate();
            self::fail('The migration accepted a JSON list.');
        } catch (RuntimeException $exception) {
            self::assertSame('entry.category_ids is not a comma list of ids on entries 5.', $exception->getMessage());
        }

        self::assertSame('1', (new Query())->select('path')->from(Entry::tableName())->where(['id' => 4])->scalar());
    }

    private function setLegacyValues(int $id, ?string $path, ?string $categoryIds): void
    {
        Yii::$app->getDb()->createCommand()
            ->update(Entry::tableName(), ['path' => $path, 'category_ids' => $categoryIds], ['id' => $id])
            ->execute();
    }

    /**
     * @return array{mixed, mixed}
     */
    private function findValues(int $id): array
    {
        /** @var array{path: string|null, category_ids: string|null} $row */
        $row = (new Query())
            ->select(['path', 'category_ids'])
            ->from(Entry::tableName())
            ->where(['id' => $id])
            ->one();

        return [
            $row['path'] === null ? null : json_decode($row['path'], true),
            $row['category_ids'] === null ? null : json_decode($row['category_ids'], true),
        ];
    }

    private function migrate(): void
    {
        ob_start();

        try {
            $this->createUpgradeMigration('yii2-cms', self::MIGRATION, ['compact' => true])->safeUp();
        } finally {
            ob_end_clean();
        }
    }
}
