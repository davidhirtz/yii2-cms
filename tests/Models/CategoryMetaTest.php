<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Translation;
use Override;
use Yii;

/**
 * A category has no URL of its own, so its meta title and description live in `custom_attributes` rather than in
 * columns of their own — unlike the entry's, which are still columns.
 */
class CategoryMetaTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Category::class);
        Category::instance(true);

        parent::tearDown();
    }

    public function testTheMetaAttributesRoundTripThroughTheJsonColumn(): void
    {
        $category = $this->getCategoryFromFixture('root-1');
        $category->title = 'A meta title';
        $category->description = 'A meta description';

        self::assertSame(1, $category->update(), implode(' ', $category->getErrorSummary(true)));

        $loaded = Category::findOne($category->id);

        self::assertSame('A meta title', $loaded->title);
        self::assertSame('A meta description', $loaded->description);
        self::assertSame([
            'title' => 'A meta title',
            'description' => 'A meta description',
        ], $loaded->getAttribute('custom_attributes'));
    }

    public function testATranslatedValueStaysOutOfTheTranslationTable(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        Yii::$container->set(Category::class, ['translatableAttributes' => ['title', 'description']]);
        Category::instance(true);

        $category = Category::findOne($this->getCategoryFixtureData('root-1')['id']);
        $category->title = 'A meta title';
        $category->setAttribute('title_de', 'Ein Meta-Titel');

        self::assertSame(1, $category->update(), implode(' ', $category->getErrorSummary(true)));

        $loaded = Category::findOne($category->id);

        self::assertSame('Ein Meta-Titel', $loaded->getAttribute('title_de'));
        self::assertNull(Translation::find()->whereAttribute('title')->one());
    }

    /**
     * Still indexed, and still 255 characters — only the storage changed.
     */
    public function testTheyAreSearchableAndLengthChecked(): void
    {
        $category = $this->getCategoryFromFixture('root-1');

        self::assertContains('title', $category->getSearchAttributes());
        self::assertContains('description', $category->getSearchAttributes());

        $category->title = str_repeat('a', 256);

        self::assertFalse($category->update() === 1);
        self::assertArrayHasKey('title', $category->getErrors());
    }
}
