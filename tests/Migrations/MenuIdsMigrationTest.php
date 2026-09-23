<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UpgradeMigrationTrait;
use Override;
use Yii;
use yii\db\Query;

/**
 * The v2 checkbox columns are a project's own, so the migration has to find them rather than assume them. The DDL
 * commits the test transaction, which is why everything is undone by hand.
 */
class MenuIdsMigrationTest extends TestCase
{
    use UpgradeMigrationTrait;

    use CmsFixtureTrait;

    private const string MIGRATION = 'Hirtz\\Cms\\Migrations\\M260915200000MenuIds';

    private const int MENU_VALUE = 1;
    private const int FOOTER_VALUE = 2;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The migration lives in davidhirtz/yii2-upgrade now; this is the test that runs it.
        $this->requireUpgradeMigration('yii2-cms', self::MIGRATION);

        $this->dropMenuIdsColumn();
    }

    #[Override]
    protected function tearDown(): void
    {
        $db = Yii::$app->getDb();
        $schema = $db->getTableSchema(Entry::tableName(), true);

        foreach (['show_in_menu', 'show_in_footer'] as $column) {
            if ($schema?->getColumn($column)) {
                $db->createCommand()
                    ->dropColumn(Entry::tableName(), $column)
                    ->execute();
            }
        }

        if (!$schema?->getColumn('menu_ids')) {
            $db->createCommand()
                ->addColumn(Entry::tableName(), 'menu_ids', 'json NULL AFTER `publish_date`')
                ->execute();
        }

        $db->createCommand()
            ->update(Entry::tableName(), ['menu_ids' => null])
            ->execute();

        parent::tearDown();
    }

    /**
     * A fresh install has neither column, and the migration must not invent them.
     */
    public function testTheColumnIsAddedWithoutTheLegacyOnes(): void
    {
        $this->migrate();

        $schema = Yii::$app->getDb()->getTableSchema(Entry::tableName(), true);

        self::assertNotNull($schema?->getColumn('menu_ids'));
        self::assertNull(self::findMenuIds(1));
    }

    public function testTheCheckboxesBecomeMenus(): void
    {
        $this->addLegacyColumns();

        Yii::$app->getDb()->createCommand()
            ->update(Entry::tableName(), ['show_in_menu' => 1], ['id' => 1])
            ->execute();

        Yii::$app->getDb()->createCommand()
            ->update(Entry::tableName(), ['show_in_menu' => 1, 'show_in_footer' => 1], ['id' => 2])
            ->execute();

        Yii::$app->getDb()->createCommand()
            ->update(Entry::tableName(), ['show_in_footer' => 1], ['id' => 3])
            ->execute();

        $this->migrate();

        self::assertSame([self::MENU_VALUE], self::findMenuIds(1));
        self::assertSame([self::MENU_VALUE, self::FOOTER_VALUE], self::findMenuIds(2));
        self::assertSame([self::FOOTER_VALUE], self::findMenuIds(3));

        $schema = Yii::$app->getDb()->getTableSchema(Entry::tableName(), true);

        self::assertNull($schema?->getColumn('show_in_menu'));
        self::assertNull($schema?->getColumn('show_in_footer'));
    }

    /**
     * An entry in neither is left alone rather than given an empty list, which would read as "in no menu" where
     * `null` reads as "never asked".
     */
    public function testAnEntryInNeitherKeepsNull(): void
    {
        $this->addLegacyColumns();
        $this->migrate();

        self::assertNull(self::findMenuIds(1));
    }

    /**
     * Only `show_in_menu` carried an index, and dropping the column while the index still names it degrades the
     * index instead of removing it.
     */
    public function testOnlyOneOfTheColumnsIsEnough(): void
    {
        Yii::$app->getDb()->createCommand()
            ->addColumn(Entry::tableName(), 'show_in_menu', 'boolean NOT NULL DEFAULT FALSE')
            ->execute();

        Yii::$app->getDb()->createCommand()
            ->createIndex('show_in_menu', Entry::tableName(), ['show_in_menu', 'status', 'position'])
            ->execute();

        Yii::$app->getDb()->createCommand()
            ->update(Entry::tableName(), ['show_in_menu' => 1], ['id' => 1])
            ->execute();

        $this->migrate();

        self::assertSame([self::MENU_VALUE], self::findMenuIds(1));

        $schema = Yii::$app->getDb()->getTableSchema(Entry::tableName(), true);
        self::assertNotNull($schema);

        self::assertNull($schema->getColumn('show_in_menu'));
        self::assertArrayNotHasKey('show_in_menu', $schema->columns);
    }

    private function addLegacyColumns(): void
    {
        $db = Yii::$app->getDb();

        foreach (['show_in_menu', 'show_in_footer'] as $column) {
            $db->createCommand()
                ->addColumn(Entry::tableName(), $column, 'boolean NOT NULL DEFAULT FALSE')
                ->execute();
        }
    }

    private function dropMenuIdsColumn(): void
    {
        $db = Yii::$app->getDb();

        if ($db->getTableSchema(Entry::tableName(), true)?->getColumn('menu_ids')) {
            $db->createCommand()
                ->dropColumn(Entry::tableName(), 'menu_ids')
                ->execute();
        }
    }

    private function migrate(): void
    {
        $migration = $this->createUpgradeMigration('yii2-cms', self::MIGRATION, ['compact' => true]);
        $migration->safeUp();
    }

    /**
     * @return list<int>|null
     */
    private static function findMenuIds(int $id): ?array
    {
        $value = (new Query())
            ->select(['menu_ids'])
            ->from(Entry::tableName())
            ->where(['id' => $id])
            ->scalar(Yii::$app->getDb());

        return $value === null || $value === false ? null : json_decode((string)$value, true);
    }
}
