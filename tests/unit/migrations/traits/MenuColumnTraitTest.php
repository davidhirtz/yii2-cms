<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\unit\Migrations\Traits;

use Codeception\Test\Unit;
use Hirtz\Cms\Migrations\Traits\MenuColumnTrait;
use Hirtz\Cms\Models\Entry;
use Yii;
use yii\db\Migration;

class MenuColumnTraitTest extends Unit
{
    public function testMenuMigration(): void
    {
        $migration = new TestMenuColumnMigration();
        $migration->addColumns();

        $schema = Yii::$app->getDb()->getSchema()->getTableSchema(Entry::tableName());
        self::assertArrayHasKey('show_in_menu', $schema->columns);

        $migration->dropColumns();
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
