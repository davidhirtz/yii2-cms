<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;

class SectionSearchTest extends TestCase
{
    use CmsFixtureTrait;

    public function testDocumentsCarryTheTenantOfTheEntry(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $documents = $section->getSearchDocuments();

        self::assertNotEmpty($documents);

        foreach ($documents as $document) {
            self::assertSame($section->entry->tenant_id, $document->tenantId);
        }
    }
}
