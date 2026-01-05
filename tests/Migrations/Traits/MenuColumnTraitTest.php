<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Migrations\Traits;

use Hirtz\Cms\Migrations\Traits\MenuColumnTrait;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\TestCase;
use Yii;
use yii\db\Migration;

class MenuColumnTraitTest extends TestCase
{
    public function testMenuMigration(): void
    {
        $migration = new TestMenuColumnMigration();

        ob_start();
        $migration->addColumns();
        $content = ob_get_clean();

        self::assertStringContainsString('> add column show_in_menu boolean NOT NULL DEFAULT FALSE AFTER `publish_date` to table {{%entry}} ... done', $content);

        $schema = Yii::$app->getDb()->getSchema()->getTableSchema(Entry::tableName(), true);
        self::assertArrayHasKey('show_in_menu', $schema->columns);

        ob_start();
        $migration->dropColumns();
        $content = ob_get_clean();

        self::assertStringContainsString('> drop column show_in_menu from table {{%entry}} ... done', $content);

        $schema = Yii::$app->getDb()->getSchema()->getTableSchema(Entry::tableName(), true);
        self::assertArrayNotHasKey('show_in_menu', $schema->columns);
    }
}

class TestMenuColumnMigration extends Migration
{
    use MenuColumnTrait;

    public function addColumns(): void
    {
        $this->addShowInMenuColumn();
    }

    public function dropColumns(): void
    {
        $this->dropShowInMenuColumn();
    }
}
