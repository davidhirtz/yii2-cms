<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests;

use Hirtz\Cms\Module;
use Hirtz\Cms\Test\TestCase;
use Yii;

class ModuleTest extends TestCase
{
    public function testBlockEntriesFollowSectionEntries(): void
    {
        self::assertFalse((new Module('cms', Yii::$app, ['enableSectionEntries' => false]))->enableBlockEntries);
        self::assertTrue((new Module('cms', Yii::$app, ['enableSectionEntries' => true]))->enableBlockEntries);
    }

    public function testBlockEntriesCanBeEnabledWithoutSectionEntries(): void
    {
        $module = new Module('cms', Yii::$app, [
            'enableSectionEntries' => false,
            'enableBlockEntries' => true,
        ]);

        self::assertTrue($module->enableBlockEntries);
    }
}
