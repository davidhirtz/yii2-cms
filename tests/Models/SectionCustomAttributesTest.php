<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Actions\DuplicateSection;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Translation;
use Override;
use Yii;

class SectionCustomAttributesTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        Yii::$container->setDefinitions([
            Section::class => ['class' => TestSection::class, 'i18nAttributes' => ['name', 'slug']],
            TestSection::class => ['i18nAttributes' => ['name', 'slug']],
        ]);

        Section::instance(true);
        TestSection::instance(true);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        Yii::$container->clear(TestSection::class);

        Section::instance(true);
        TestSection::instance(true);

        parent::tearDown();
    }

    public function testTypeDecidesWhichDefinitionsApply(): void
    {
        $section = $this->getSectionFromFixture('section-headline');

        self::assertSame(['subtitle'], array_keys($section->getCustomAttributeDefinitions()));
        self::assertInstanceOf(TextCustomAttribute::class, $section->getCustomAttribute('subtitle'));

        $section->type = TestSection::TYPE_LINK_LIST;

        self::assertSame(['links'], array_keys($section->getCustomAttributeDefinitions()));
        self::assertInstanceOf(GroupCustomAttribute::class, $section->getCustomAttribute('links'));

        $section->type = TestSection::TYPE_GALLERY;

        self::assertSame([], $section->getCustomAttributeDefinitions());
    }

    public function testTranslatedValueRoundTripsThroughTheJsonColumn(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->subtitle = 'Subtitle';
        $section->subtitle_de = 'Subtitle DE';

        self::assertSame(1, $section->update(), implode(' ', $section->getErrorSummary(true)));

        $loaded = TestSection::findOne($section->id);

        self::assertSame('Subtitle', $loaded->subtitle);
        self::assertSame('Subtitle DE', $loaded->subtitle_de);

        self::assertNull(Translation::find()->whereAttribute('subtitle')->one());
    }

    public function testGroupValuesRoundTrip(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->type = TestSection::TYPE_LINK_LIST;
        $section->links = [
            ['label' => 'One', 'label_de' => 'Eins', 'url' => 'https://one.example.com'],
            ['label' => 'Two', 'url' => 'https://two.example.com'],
        ];

        self::assertSame(1, $section->update(), implode(' ', $section->getErrorSummary(true)));

        $loaded = TestSection::findOne($section->id);

        self::assertSame([
            ['label' => 'One', 'label_de' => 'Eins', 'url' => 'https://one.example.com'],
            ['label' => 'Two', 'url' => 'https://two.example.com'],
        ], $loaded->links);
    }

    public function testDuplicateCopiesTheCustomAttributes(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->subtitle = 'Subtitle';
        $section->subtitle_de = 'Subtitle DE';

        self::assertSame(1, $section->update());

        $duplicate = DuplicateSection::create([TestSection::findOne($section->id)]);

        self::assertInstanceOf(TestSection::class, $duplicate);
        self::assertSame('Subtitle', $duplicate->subtitle);
        self::assertSame('Subtitle DE', $duplicate->subtitle_de);
    }

    public function testTrailLogsTheAttributeRatherThanTheColumn(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->subtitle = 'Subtitle';

        self::assertSame(1, $section->update());

        $trail = Trail::find()
            ->where([
                'model_class' => TestSection::class,
                'model_id' => $section->id,
            ])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        self::assertSame([null, 'Subtitle'], $trail->data['subtitle']);
        self::assertArrayNotHasKey('custom_attributes', $trail->data);

        // The JSON column is not a trail attribute, so the parents are still the entry.
        self::assertSame([$section->entry], $section->getTrailParents());
    }

    public function testAnInvalidGroupValueBlocksTheSave(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->type = TestSection::TYPE_LINK_LIST;
        $section->links = [['label' => 'One', 'url' => 'not a url']];

        self::assertFalse($section->update() !== false && !$section->hasErrors());
        self::assertArrayHasKey('links.0.url', $section->getErrors());
    }
}
