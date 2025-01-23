<?php

namespace davidhirtz\yii2\cms\tests\unit\migrations\traits;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\migrations\traits\MenuColumnTrait;
use davidhirtz\yii2\cms\models\Entry;
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
