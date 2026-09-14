<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Section;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M260915180000SectionCustomAttributesColumn extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->moveCustomAttributesColumn(Section::tableName(), 'position');
    }

    public function safeDown(): void
    {
        $this->moveCustomAttributesColumnToEnd(Section::tableName());
    }
}
