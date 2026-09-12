<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Yii;

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

    public function testTheIndexTitleIsTheSectionNameAndTheResultTitleNamesTheEntry(): void
    {
        // The owner sees every hit, so the result is not hidden by the section permission.
        Yii::$app->getUser()->setIdentity(User::findOne(['name' => 'owner']));

        $section = $this->getSectionFromFixture('section-headline');

        self::assertSame('Test Headline', $section->getSearchTitle());
        self::assertSame(
            $section->entry->getSearchTitle() . ' › Test Headline',
            $section->getSearchResult()?->title
        );
    }

    public function testAnUnnamedSectionIsTitledByItsType(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->name = '';

        self::assertSame($section->getTypeName(), $section->getSearchTitle());
        self::assertStringNotContainsString('#', $section->getSearchTitle());
    }

    public function testTheSearchableQueryLoadsTheEntry(): void
    {
        $section = Section::findSearchable()->one();

        self::assertNotNull($section);
        self::assertTrue($section->isRelationPopulated('entry'));
    }
}
