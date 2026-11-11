<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Media\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;

/**
 * The entry page tells who links to the entry and who lists it before it is deleted.
 */
class EntryReferenceFunctionalTest extends TestCase
{
    use FunctionalTestTrait;
    use CmsFixtureTrait, UserFixtureTrait {
        CmsFixtureTrait::fixtures insteadof UserFixtureTrait;
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        $this->getWebUser()->login($user);
    }

    public function testTheEntryShowsItsReferencesAndRelations(): void
    {
        $section = TestSection::instantiateByType(TestSection::TYPE_TEXT_COLUMN);
        $section->populateEntryRelation($this->getEntryFromFixture('page-enabled'));
        $section->content = '<p><a href="entry:4">Ågot</a> <a href="entry:4#東京">🚀</a></p>';
        self::assertTrue($section->insert(), implode(' ', $section->getErrorSummary(true)));

        $relation = SectionEntry::create();
        $relation->populateModelRelation(Section::findOne(3));
        $relation->populateEntryRelation(Entry::findOne(4));
        self::assertTrue($relation->insert(), implode(' ', $relation->getErrorSummary(true)));

        $this->open('/admin/cms/entry/update?id=4');
        self::assertResponseIsSuccessful();

        $html = self::$crawler->html();
        self::assertStringContainsString('linked 2 times in one record', $html);
        self::assertStringContainsString('listed in one section or block', $html);
        self::assertSelectorExists('a[href="/admin/cms/entry-reference/index?entry=4"]');
        self::assertSelectorExists('a[href="/admin/cms/entry-relation/index?entry=4"]');

        $this->open('/admin/cms/entry-reference/index?entry=4');
        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('#entry-reference-grid-view', 'Content (2x)');
        self::assertSelectorExists("#entry-reference-grid-view a[href=\"/admin/cms/section/update?id=$section->id\"]");
        self::assertSelectorNotExists('#entry-reference-grid-view a[href="/admin/cms/section/update?id=3"]');

        $this->open('/admin/cms/entry-relation/index?entry=4');
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('#entry-relation-parent-grid-view a[href="/admin/cms/section-entry/index?section=3"]');
        self::assertSelectorNotExists("#entry-relation-parent-grid-view a[href*=\"section=$section->id\"]");
    }

    public function testAnUnusedEntryShowsNoTab(): void
    {
        $this->open('/admin/cms/entry/update?id=5');
        self::assertResponseIsSuccessful();

        self::assertSelectorNotExists('a[href="/admin/cms/entry-reference/index?entry=5"]');
        self::assertSelectorNotExists('a[href="/admin/cms/entry-relation/index?entry=5"]');
    }
}
