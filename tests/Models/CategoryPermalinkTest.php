<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\TestCase;
use Override;

/**
 * A category slug is a single, globally-unique segment: its permalink URI is just the slug, with no parent path, so
 * renaming or moving a category never changes another category's URL.
 */
class CategoryPermalinkTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $module = Category::getModule();
        $module->enableCategories = true;
        $module->enableNestedCategories = true;
        $module->enableCategoryUrls = true;
    }

    public function testNoPermalinkIsWrittenWhenCategoryUrlsAreDisabled(): void
    {
        Category::getModule()->enableCategoryUrls = false;

        $category = $this->createCategory('news');

        self::assertFalse($category->hasPermalink());
        self::assertNull($this->findPermalinkUri($category));
    }

    public function testPermalinkIsWrittenForARootCategory(): void
    {
        $category = $this->createCategory('news');

        self::assertSame('news', $this->findPermalinkUri($category));
    }

    public function testNestedCategoryUsesItsOwnSlug(): void
    {
        $parent = $this->createCategory('news');
        $child = $this->createCategory('sport', $parent);

        self::assertSame('sport', $child->getFormattedSlug());
        self::assertSame('sport', $this->findPermalinkUri($child));
    }

    public function testRenamingACategoryLeavesDescendantsUntouched(): void
    {
        $parent = $this->createCategory('news');
        $child = $this->createCategory('sport', $parent);

        $parent->refresh();
        $parent->slug = 'stories';
        self::assertNotFalse($parent->update());

        self::assertSame('stories', $this->findPermalinkUri($parent));
        self::assertSame('sport', $this->findPermalinkUri($child));
    }

    public function testMovingACategoryLeavesItsSlugUntouched(): void
    {
        $first = $this->createCategory('first');
        $second = $this->createCategory('second');
        $child = $this->createCategory('child', $first);

        $child->refresh();
        $child->parent_id = $second->id;
        self::assertNotFalse($child->update());

        self::assertSame('child', $this->findPermalinkUri($child));
    }

    /**
     * The nested set is left intact by a rename, which touches only the category's own permalink.
     */
    public function testRenamingDoesNotDisturbTheNestedTree(): void
    {
        $parent = $this->createCategory('news');
        $child = $this->createCategory('sport', $parent);

        $parent->refresh();
        $lft = $parent->lft;
        $rgt = $parent->rgt;

        $parent->slug = 'stories';
        self::assertNotFalse($parent->update());
        $parent->refresh();
        $child->refresh();

        self::assertSame($lft, $parent->lft);
        self::assertSame($rgt, $parent->rgt);
        self::assertTrue($child->lft > $parent->lft && $child->rgt < $parent->rgt);
    }

    public function testPermalinkIsDeletedWithTheCategory(): void
    {
        $category = $this->createCategory('news');
        $id = $category->id;

        self::assertNotFalse($category->delete());

        self::assertSame(0, (int)Permalink::find()
            ->whereModel(Category::class, $id)
            ->count());
    }

    protected function findPermalinkUri(Category $category): ?string
    {
        $permalink = Permalink::find()
            ->whereModel(Category::class, $category->id)
            ->whereLanguage()
            ->one();

        return $permalink?->uri;
    }

    protected function createCategory(string $slug, ?Category $parent = null): Category
    {
        $category = Category::create();
        $category->name = ucfirst($slug);
        $category->slug = $slug;
        $category->parent_id = $parent?->id;

        self::assertTrue($category->save(), implode(' ', $category->getErrorSummary(true)));

        return $category;
    }
}
