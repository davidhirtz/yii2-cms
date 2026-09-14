<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Actions\DuplicateSection;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Yii;

/**
 * The slug is the section's HTML id and has no column of its own, so the uniqueness check is a comparison against
 * the sections of the same entry rather than a database constraint.
 */
class SectionSlugTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTheSlugIsInflected(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->slug = 'Über Uns!';

        self::assertSame(1, $section->update(), implode(' ', $section->getErrorSummary(true)));
        self::assertSame('uber-uns', $section->slug);
    }

    /**
     * The inflection is the definition's filter rule, so a translated slug is normalized in its own language too.
     */
    public function testATranslatedSlugIsInflectedAsWell(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        Yii::$container->set(TestSection::class, ['translatableAttributes' => ['slug']]);
        TestSection::instance(true);

        try {
            $section = TestSection::findOne($this->getSectionFixtureData('section-headline')['id']);
            $section->slug = 'One Anchor';
            $section->setAttribute('slug_de', 'Ein Anker');

            self::assertSame(1, $section->update(), implode(' ', $section->getErrorSummary(true)));
            self::assertSame('one-anchor', $section->slug);
            self::assertSame('ein-anker', $section->getAttribute('slug_de'));
        } finally {
            Yii::$container->clear(TestSection::class);
            TestSection::instance(true);
        }
    }

    public function testASlugIsRefusedWhenASiblingAlreadyHasIt(): void
    {
        $first = $this->getSectionFromFixture('section-headline');
        $first->slug = 'anchor';

        self::assertSame(1, $first->update(), implode(' ', $first->getErrorSummary(true)));

        $second = $this->getSectionFromFixture('section-column');
        $second->slug = 'anchor';

        self::assertFalse($second->update() === 1);
        self::assertArrayHasKey('slug', $second->getErrors());
    }

    /**
     * Only within the entry: two entries may name the same anchor.
     */
    public function testTheSameSlugIsAcceptedOnAnotherEntry(): void
    {
        $first = $this->getSectionFromFixture('section-headline');
        $first->slug = 'anchor';

        self::assertSame(1, $first->update(), implode(' ', $first->getErrorSummary(true)));

        $other = $this->getSectionFromFixture('section-entry-draft');
        $other->slug = 'anchor';

        self::assertSame(1, $other->update(), implode(' ', $other->getErrorSummary(true)));
    }

    public function testTheSlugSurvivesItsOwnUpdate(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->slug = 'anchor';

        self::assertSame(1, $section->update(), implode(' ', $section->getErrorSummary(true)));

        $section = TestSection::findOne($section->id);
        $section->name = 'Renamed';

        self::assertSame(1, $section->update(), implode(' ', $section->getErrorSummary(true)));
        self::assertSame('anchor', $section->slug);
    }

    public function testADuplicateIsGivenASlugOfItsOwn(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->slug = 'anchor';

        self::assertSame(1, $section->update(), implode(' ', $section->getErrorSummary(true)));

        $duplicate = DuplicateSection::create([TestSection::findOne($section->id)]);

        self::assertInstanceOf(TestSection::class, $duplicate);
        self::assertSame('anchor-1', $duplicate->slug);
    }

    public function testTheHtmlIdFallsBackToTheRecordId(): void
    {
        $section = $this->getSectionFromFixture('section-headline');

        self::assertSame('section-' . $section->id, $section->getHtmlId());

        $section->slug = 'anchor';

        self::assertSame('anchor', $section->getHtmlId());
    }
}
