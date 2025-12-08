<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\unit\Migrations\Traits;

use Codeception\Test\Unit;
use Hirtz\Cms\Migrations\Traits\FooterColumnTrait;
use Hirtz\Cms\Models\Entry;
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
