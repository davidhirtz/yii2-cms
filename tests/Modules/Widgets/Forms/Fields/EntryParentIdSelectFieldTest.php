<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Forms\Fields;

use Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\EntryParentIdSelectField;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Models\Collections\TenantCollection;

class EntryParentIdSelectFieldTest extends TestCase
{
    public function testTheNameOfAParentIsEncodedOnce(): void
    {
        TestEntry::getModule()->enableNestedEntries = true;

        $parent = TestEntry::create();
        $parent->loadDefaultValues();
        $parent->status = TestEntry::STATUS_ENABLED;
        $parent->type = TestEntry::TYPE_PAGE;
        $parent->name = 'A & B';
        $parent->slug = 'a-and-b';
        $parent->populateTenantRelation(TenantCollection::getDefault());

        self::assertTrue($parent->insert(), print_r($parent->getErrors(), true));

        $entry = TestEntry::create();
        $entry->tenant_id = $parent->tenant_id;

        $html = EntryParentIdSelectField::make()
            ->model($entry)
            ->render();

        self::assertStringContainsString('>A &amp; B</option>', $html);
    }
}
