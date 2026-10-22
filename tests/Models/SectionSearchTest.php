<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Yii;
use Hirtz\Skeleton\Models\User;

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

    public function testTheIndexTitleIsTheSectionNameAndTheResultIsNamedLikeTheHeader(): void
    {
        // The owner sees every hit, so the result is not hidden by the section permission.
        $this->getWebUser()->setIdentity(User::findOne(['name' => 'owner']));

        $section = $this->getSectionFromFixture('section-headline');
        $result = $section->getSearchResult();

        self::assertSame('Test Headline', $section->getSearchTitle());
        self::assertSame($section->entry->getAdminName(), $result?->title);
        self::assertSame([$section->getAdminSubtitle()], $result->subtitles);
        self::assertStringStartsWith('Test Headline', (string)$result->description);
    }

    /**
     * The type name is translated in the saving editor's language, so it would title every language's row in it.
     */
    public function testAnUnnamedSectionIsIndexedWithoutATitle(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->name = '';

        self::assertSame('', $section->getSearchTitle());
        self::assertSame('', Yii::$app->getI18n()->callback('de', fn (): string => $section->getSearchTitle()));
    }

    public function testTheSearchableQueryLoadsTheEntry(): void
    {
        $section = Section::findSearchable()->one();

        self::assertNotNull($section);
        self::assertTrue($section->isRelationPopulated('entry'));
    }
}
