<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\unit\models\actions;

use Codeception\Test\Unit;
use davidhirtz\yii2\cms\models\actions\ReorderEntries;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use davidhirtz\yii2\cms\tests\support\fixtures\traits\CmsFixturesTrait;
use davidhirtz\yii2\cms\tests\support\UnitTester;
use davidhirtz\yii2\skeleton\models\Trail;
use Yii;

class ReorderEntriesTest extends Unit
{
    use CmsFixturesTrait;

    protected UnitTester $tester;

    public function testRootOrderEntries(): void
    {
        $reverseOrder = array_reverse($this->getRootEntryIds());
        $postIds = $this->getPostIds();

        $action = new ReorderEntries(null, $reverseOrder);
        $action->run();

        self::assertEquals($this->getRootEntryIds(), $reverseOrder);
        self::assertEquals($this->getPostIds(), $postIds);
    }

    public function testChildrenOrderEntries(): void
    {
        $reverseOrder = array_reverse($this->getPostIds());

        $action = new ReorderEntries($this->getPageEntry(), $reverseOrder);
        $action->run();

        $entry = $this->getPageEntry();

        self::assertEquals($this->getPostIds(), $reverseOrder);
        self::assertEquals(1, $entry->position);
        self::assertEquals(2, $entry->entry_count);

        $trail = Trail::find()
            ->orderBy(['id' => SORT_DESC])
            ->one();

        self::assertEquals($entry::getModule()->getI18nClassName($entry::class), $trail->model);
        self::assertEquals(1, $trail->model_id);
        self::assertEquals(Yii::t('cms', 'Entry order changed'), $trail->message);
    }

    private function getRootEntryIds(): array
    {
        return TestEntry::find()
            ->select(['id'])
            ->where(['type' => TestEntry::TYPE_PAGE])
            ->orderBy(['position' => SORT_ASC])
            ->column();
    }

    private function getPostIds(): array
    {
        return $this->getPageEntry()
            ->findChildren()
            ->select(['id'])
            ->orderBy(['position' => SORT_ASC])
            ->column();
    }

    private function getPageEntry(): Entry
    {
        return $this->tester->grabEntryFixture('page-enabled');
    }
}
