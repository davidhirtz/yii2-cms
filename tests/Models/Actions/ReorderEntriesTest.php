<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Hirtz\Cms\Models\Actions\ReorderEntries;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Filters\PageCache;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Tenant\Models\Tenant;
use Override;
use Yii;
use yii\caching\ArrayCache;
use yii\caching\TagDependency;

class ReorderEntriesTest extends TestCase
{
    use CmsFixtureTrait;

    public function testRootOrderEntries(): void
    {
        $reverseOrder = array_reverse($this->getRootEntryIds());
        $postIds = $this->getPostIds();

        $action = new ReorderEntries(null, null, $reverseOrder);
        $action->run();

        self::assertEquals($this->getRootEntryIds(), $reverseOrder);
        self::assertEquals($this->getPostIds(), $postIds);
    }

    public function testATenantLeavesTheOtherTenantsEntriesAlone(): void
    {
        TestEntry::updateAll(['tenant_id' => 2], ['id' => 3]);

        (new ReorderEntries(null, 1, [3, 2, 1]))->run();

        self::assertSame(3, TestEntry::findOne(3)?->position);
        self::assertSame(1, TestEntry::findOne(2)?->position);
        self::assertSame(2, TestEntry::findOne(1)?->position);
    }

    public function testReorderingOneTypeKeepsTheHiddenTypesSlots(): void
    {
        // Root entries 1, 2 and 3 at 1, 2 and 3; the grid filtered by type shows 1 and 3.
        TestEntry::updateAll(['type' => TestEntry::TYPE_POST], ['id' => 2]);

        (new ReorderEntries(null, null, [3, 1]))->run();

        self::assertSame([1 => 3, 2 => 2, 3 => 1], array_map(intval(...), TestEntry::find()
            ->select('position')
            ->where(['id' => [1, 2, 3]])
            ->indexBy('id')
            ->column()));
    }

    public function testAnEntryMovedToAnotherTenantLandsAtTheEnd(): void
    {
        // Entries 2 and 3 become the German tenant's root entries, at 1 and 2; entry 1 stays at 1 in the default one.
        Entry::updateAll(['tenant_id' => 2, 'position' => 1], ['id' => 2]);
        Entry::updateAll(['tenant_id' => 2, 'position' => 2], ['id' => 3]);
        Entry::updateAll(['tenant_id' => 2], ['parent_id' => 2]);

        $entry = Entry::findOne(1);
        self::assertNotNull($entry);
        $entry->populateTenantRelation(Tenant::findOne(2));
        self::assertTrue($entry->save(), print_r($entry->getErrors(), true));

        self::assertSame(3, Entry::findOne(1)?->position);
    }

    public function testChildrenOrderEntries(): void
    {
        $reverseOrder = array_reverse($this->getPostIds());

        $action = new ReorderEntries($this->getPageEntry(), null, $reverseOrder);
        $action->run();

        $entry = $this->getPageEntry();

        self::assertEquals($this->getPostIds(), $reverseOrder);
        self::assertEquals(1, $entry->position);
        self::assertEquals(2, $entry->entry_count);

        $trail = Trail::find()
            ->orderBy(['id' => SORT_DESC])
            ->one();

        self::assertEquals($entry::class, $trail->model_class);
        self::assertEquals(1, $trail->model_id);
        self::assertEquals(Yii::t('cms', 'COMMON_ENTRY_ORDER_CHANGED'), $trail->getMessage());
    }

    /**
     * Invalidated before the commit, a request in between would cache the old order under the new tag version.
     */
    public function testThePageCacheIsInvalidatedOnceCommitted(): void
    {
        $cache = new class () extends ArrayCache {
            public ?int $invalidatedAt = null;

            #[Override]
            protected function setValue($key, $value, $duration): bool
            {
                if ($key === $this->buildKey([TagDependency::class, PageCache::TAG_DEPENDENCY_KEY])) {
                    $this->invalidatedAt = Yii::$app->getDb()->getTransaction()?->getLevel();
                }

                return parent::setValue($key, $value, $duration);
            }
        };

        Yii::$app->set('cache', $cache);
        $level = Yii::$app->getDb()->getTransaction()?->getLevel();

        self::assertGreaterThan(0, (new ReorderEntries(null, null, array_reverse($this->getRootEntryIds())))->run());
        self::assertSame($level, $cache->invalidatedAt);
    }

    /**
     * @return list<int>
     */
    private function getRootEntryIds(): array
    {
        return array_values(TestEntry::find()
            ->select(['id'])
            ->where(['type' => TestEntry::TYPE_PAGE])
            ->orderBy(['position' => SORT_ASC])
            ->column());
    }

    /**
     * @return list<int>
     */
    private function getPostIds(): array
    {
        return array_values($this->getPageEntry()
            ->findChildren()
            ->select(['id'])
            ->orderBy(['position' => SORT_ASC])
            ->column());
    }

    private function getPageEntry(): Entry
    {
        return $this->getEntryFromFixture('page-enabled');
    }
}
