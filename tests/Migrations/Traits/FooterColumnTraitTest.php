<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Migrations\Traits;

use Hirtz\Cms\Migrations\Traits\FooterColumnTrait;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\TestCase;
use Yii;
use yii\db\Migration;

class FooterColumnTraitTest extends TestCase
{
    public function testFooterMigration(): void
    {
        $migration = new TestFooterColumnMigration();

        ob_start();
        $migration->addColumns();
        $content = ob_get_clean();

        self::assertStringContainsString('> add column show_in_footer boolean NOT NULL DEFAULT FALSE AFTER `publish_date` to table {{%entry}} ... done', $content);

        $schema = Yii::$app->getDb()->getSchema()->getTableSchema(Entry::tableName(), true);
        self::assertArrayHasKey('show_in_footer', $schema->columns);

        ob_start();
        $migration->dropColumns();
        $content = ob_get_clean();

        self::assertStringContainsString('> drop column show_in_footer from table {{%entry}} ... done', $content);

        $schema = Yii::$app->getDb()->getSchema()->getTableSchema(Entry::tableName(), true);
        self::assertArrayNotHasKey('show_in_footer', $schema->columns);
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
