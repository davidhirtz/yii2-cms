<?php

namespace davidhirtz\yii2\cms\tests\unit\migrations\traits;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\migrations\traits\FooterColumnTrait;
use davidhirtz\yii2\cms\models\Entry;
use Yii;
use yii\db\Migration;

class FooterColumnTraitTest extends Unit
{
    public function testFooterMigration(): void
    {
        $migration = new TestFooterColumnMigration();
        $migration->addColumns();

        $schema = Yii::$app->getDb()->getSchema()->getTableSchema(Entry::tableName());
        self::assertArrayHasKey('show_in_footer', $schema->columns);

        $migration->dropColumns();
    }
}

class TestFooterColumnMigration extends Migration
{
    use FooterColumnTrait;

    public function addColumns(): void
    {
        $this->addShowInFooterColumn();
    }

    public function dropColumns(): void
    {
        $this->dropShowInFooterColumn();
    }
}
