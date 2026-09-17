<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Forms;

use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;

class EntryActiveFormTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTenantFieldIsRenderedLastForASingleTenant(): void
    {
        Tenant::deleteAll(['!=', 'id', TenantCollection::getDefault()->id]);
        TenantCollection::invalidateCache();

        $content = $this->render();

        self::assertGreaterThan(
            strpos($content, 'name="Entry[name]"'),
            strpos($content, 'name="Entry[tenant_id]"'),
            'The tenant field is not rendered after the name field.'
        );
    }

    public function testTenantFieldIsRenderedFirstForSeveralTenants(): void
    {
        $content = $this->render();

        self::assertLessThan(
            strpos($content, 'name="Entry[name]"'),
            strpos($content, 'name="Entry[tenant_id]"'),
            'The tenant field is not rendered before the name field.'
        );
    }

    public function testNoIdIsRenderedTwice(): void
    {
        preg_match_all('/ id="([^"]+)"/', $this->render(), $matches);
        $ids = $matches[1];

        self::assertNotSame([], $ids);
        self::assertSame(array_values(array_unique($ids)), $ids);
    }

    public function testSlugBaseUrlIsTheParentPathForAParentWithoutARoute(): void
    {
        $parent = $this->getEntryFromFixture('page-disabled');
        self::assertFalse($parent->hasRoute());

        $entry = TestEntry::create();
        $entry->parent_id = $parent->id;

        self::assertStringContainsString(
            $parent->getFormattedSlug() . '/',
            $this->render($entry),
            'The slug base URL does not carry the path of a parent that has no route of its own.'
        );
    }

    protected function render(?TestEntry $entry = null): string
    {
        return EntryActiveForm::make()
            ->model($entry ?? TestEntry::create())
            ->render();
    }
}
