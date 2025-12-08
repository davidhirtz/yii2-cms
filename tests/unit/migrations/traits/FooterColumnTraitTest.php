<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\unit\migrations\traits;

use Codeception\Test\Unit;
use Hirtz\Cms\migrations\traits\FooterColumnTrait;
use Hirtz\Cms\models\Entry;
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
