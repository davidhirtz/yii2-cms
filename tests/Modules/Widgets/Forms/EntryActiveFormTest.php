<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Widgets\Forms;

use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Hirtz\Tenant\Models\Tenant;

class EntryActiveFormTest extends TestCase
{
    public function testTenantFieldIsRenderedLastForASingleTenant(): void
    {
        Tenant::deleteAll(['!=', 'id', TenantCollection::getDefault()->id]);
        TenantCollection::invalidateCache();

        $content = $this->render();

        self::assertGreaterThan(
            strpos($content, 'name="Entry[name]"'),
            strpos($content, 'data-id="tenant"'),
            'The tenant field is not rendered after the name field.'
        );
    }

    public function testTenantFieldIsRenderedFirstForSeveralTenants(): void
    {
        $content = $this->render();

        self::assertLessThan(
            strpos($content, 'name="Entry[name]"'),
            strpos($content, 'data-id="tenant"'),
            'The tenant field is not rendered before the name field.'
        );
    }

    protected function render(): string
    {
        return EntryActiveForm::make()
            ->model(TestEntry::create())
            ->render();
    }
}
