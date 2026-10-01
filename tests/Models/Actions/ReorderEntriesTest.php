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

        self::assertGreaterThan(0, (new ReorderEntries(null, array_reverse($this->getRootEntryIds())))->run());
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
