<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Queries;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Panels\Dashboard;
use Override;
use Yii;

class CategoryUnusedTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = Category::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;
    }

    public function testAnEnabledLeafWithoutEntriesIsUnused(): void
    {
        $empty = $this->getCategoryFromFixture('root-2');
        $parent = $this->getCategoryFromFixture('root-1');
        $child = $this->getCategoryFromFixture('child-1');

        self::assertSame([$empty->id], $this->getUnusedIds());

        // A parent holding no entries of its own still holds its subcategory.
        $parent->updateAttributes(['entry_count' => 0]);
        $child->updateAttributes(['entry_count' => 0]);

        self::assertEqualsCanonicalizing([$empty->id, $child->id], $this->getUnusedIds());

        $empty->updateAttributes(['status' => Category::STATUS_DISABLED]);

        self::assertSame([$child->id], $this->getUnusedIds());
    }

    public function testTheGridListsUnusedCategoriesOfEveryLevelWithoutReordering(): void
    {
        $child = $this->getCategoryFromFixture('child-1');
        $child->updateAttributes(['entry_count' => 0]);

        $this->login();

        $params = ['unused' => 1];
        $this->getWebRequest()->setQueryParams($params);

        $html = Yii::$app->runAction('admin/cms/category/index', $params);

        self::assertIsString($html);
        self::assertStringContainsString((string)$child->getI18nAttribute('name'), $html);
        self::assertStringContainsString(Yii::t('cms', 'CATEGORY_UNUSED_LABEL'), $html);
        self::assertStringContainsString((string)$this->getCategoryFromFixture('root-2')->getI18nAttribute('name'), $html);
        self::assertStringNotContainsString('reorder-handle', $html);
    }

    public function testTheDashboardCountsUnusedCategories(): void
    {
        $this->login();
        $html = Dashboard::make()->render();

        self::assertStringContainsString(Yii::t('cms', 'CATEGORY_UNUSED_TEXT', ['count' => count($this->getUnusedIds())]), $html);
        self::assertStringContainsString(Url::to(['/admin/cms/category/index', 'unused' => 1]), $html);
    }

    /**
     * @return list<int>
     */
    private function getUnusedIds(): array
    {
        $ids = Category::find()->whereUnused()->select(Category::tableName() . '.[[id]]')->column();
        return array_values(array_map(intval(...), $ids));
    }

    private function login(): void
    {
        $user = User::findOne(1) ?? self::fail('The owner fixture is missing.');
        $this->getWebUser()->setIdentity($user);
    }
}
