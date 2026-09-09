<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\TestCase;
use Override;

/**
 * Categories are the one place where this refactor changes behaviour: their slugs used to be single, globally unique
 * segments assembled into a path at URL-generation time, and are now materialised as a nested path.
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

    public function testNestedCategoryUsesTheFullPath(): void
    {
        $parent = $this->createCategory('news');
        $child = $this->createCategory('sport', $parent);

        self::assertSame('news/sport', $child->getFormattedSlug());
        self::assertSame('news/sport', $this->findPermalinkUri($child));
    }

    public function testRenamingACategoryRewritesDescendantPermalinks(): void
    {
        $parent = $this->createCategory('news');
        $child = $this->createCategory('sport', $parent);
        $grandchild = $this->createCategory('football', $child);

        $parent->refresh();
        $parent->slug = 'stories';
        self::assertTrue($parent->update() !== false);

        self::assertSame('stories', $this->findPermalinkUri($parent));
        self::assertSame('stories/sport', $this->findPermalinkUri($child));
        self::assertSame('stories/sport/football', $this->findPermalinkUri($grandchild));
    }

    public function testMovingACategoryRewritesDescendantPermalinks(): void
    {
        $first = $this->createCategory('first');
        $second = $this->createCategory('second');
        $child = $this->createCategory('child', $first);
        $grandchild = $this->createCategory('grandchild', $child);

        $child->refresh();
        $child->parent_id = $second->id;
        self::assertTrue($child->update() !== false);

        self::assertSame('second/child', $this->findPermalinkUri($child));
        self::assertSame('second/child/grandchild', $this->findPermalinkUri($grandchild));
    }

    /**
     * The nested set is left intact by the permalink rewrite, which is why it does not re-save descendants.
     */
    public function testRenamingDoesNotDisturbTheNestedTree(): void
    {
        $parent = $this->createCategory('news');
        $child = $this->createCategory('sport', $parent);

        $parent->refresh();
        $lft = $parent->lft;
        $rgt = $parent->rgt;

        $parent->slug = 'stories';
        self::assertTrue($parent->update() !== false);
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

        self::assertTrue($category->delete() !== false);

        self::assertSame(0, (int)Permalink::find()
            ->whereModel(Category::class, $id)
            ->count());
    }

    /**
     * A rename that rewrites a subtree has to be atomic, so a collision part-way through cannot leave half the tree
     * pointing at the new path. `NestedTreeTrait::isTransactional()` only covers a changed `parent_id`.
     */
    public function testRenameIsTransactionalOnlyWhenItRewritesASubtree(): void
    {
        $parent = $this->createCategory('news');
        $this->createCategory('sport', $parent);
        $leaf = $this->createCategory('weather');

        $parent->refresh();
        $parent->slug = 'stories';
        self::assertTrue($parent->isTransactional($parent::OP_UPDATE));

        $leaf->refresh();
        $leaf->slug = 'forecast';
        self::assertFalse($leaf->isTransactional($leaf::OP_UPDATE));
    }

    public function testRenameIsNotTransactionalWhenCategoryUrlsAreDisabled(): void
    {
        $parent = $this->createCategory('news');
        $this->createCategory('sport', $parent);

        Category::getModule()->enableCategoryUrls = false;

        $parent->refresh();
        $parent->slug = 'stories';

        self::assertFalse($parent->isTransactional($parent::OP_UPDATE));
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
