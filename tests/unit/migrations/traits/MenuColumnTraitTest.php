<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\unit\migrations\traits;

use Codeception\Test\Unit;
use Hirtz\Cms\migrations\traits\MenuColumnTrait;
use Hirtz\Cms\models\Entry;
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
