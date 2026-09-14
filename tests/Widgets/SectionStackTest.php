<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Widgets;

use Closure;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\SectionGroup;
use Hirtz\Cms\Widgets\SectionStack;
use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\base\Event;

class SectionStackTest extends TestCase
{
    private int $sectionId = 0;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::setAlias('@cmsTestViews', dirname(__DIR__) . '/data/views');
        $this->sectionId = 0;
    }

    public function testConsecutiveSectionsWithTheSameViewFileAreOneGroup(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_TEXT),
            $this->createSection(TestSection::TYPE_TEXT),
            $this->createSection(TestSection::TYPE_ALT),
            $this->createSection(TestSection::TYPE_TEXT),
        ]);

        self::assertSame(
            '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[1:1][2:2]</g>'
            . '<a view="_alt" key="@cmsTestViews/site/_alt" wrapper="">[3:3]</a>'
            . '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[4:4]</g>',
            $html
        );
    }

    public function testTheStacksViewFileIsTheFallbackForATypeWithoutOne(): void
    {
        $stack = $this->createStack([$this->createSection(TestSection::TYPE_TEXT)])
            ->viewFile('@cmsTestViews/site/_alt');

        self::assertStringStartsWith('<a view="_alt"', (string)$stack);
    }

    public function testAnInvisibleTypeIsDroppedAndPositionsAreRenumbered(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_INVISIBLE),
            $this->createSection(TestSection::TYPE_TEXT),
            $this->createSection(TestSection::TYPE_VISIBLE_UNLESS_HIDDEN, 'hidden'),
            $this->createSection(TestSection::TYPE_VISIBLE_UNLESS_HIDDEN, 'shown'),
            $this->createSection(TestSection::TYPE_TEXT),
        ]);

        self::assertSame(
            '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[2:1][4:2][5:3]</g>',
            $html
        );
    }

    public function testTheStacksFilterAppliesToATypeWithoutAVisibleOption(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_TEXT),
            $this->createSection(TestSection::TYPE_TEXT),
            // the type's own `visible` wins over the filter, which would have dropped it
            $this->createSection(TestSection::TYPE_VISIBLE_UNLESS_HIDDEN),
        ])->filter(fn (Section $section): bool => $section->id !== 2);

        self::assertSame(
            '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[1:1][3:2]</g>',
            $html
        );
    }

    public function testAnInvisibleStackRendersNothingWithoutConsultingTheFilter(): void
    {
        $stack = $this->createStack([$this->createSection(TestSection::TYPE_TEXT)])
            ->visible(false)
            ->filter(function (): bool {
                self::fail('The section filter must not be consulted for an invisible stack.');
            });

        self::assertSame('', (string)$stack);
        self::assertSame([], $stack->getGroups());
    }

    public function testTheGroupOptionSplitsAndMergesSections(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_GROUP_A),
            $this->createSection(TestSection::TYPE_GROUP_B),
            $this->createSection(TestSection::TYPE_GROUP_A),
            $this->createSection(TestSection::TYPE_GROUP_A_ALT),
        ]);

        self::assertSame(
            '<g view="_sections" key="a" wrapper="">[1:1]</g>'
            . '<g view="_sections" key="b" wrapper="">[2:2]</g>'
            . '<g view="_sections" key="a" wrapper="">[3:3][4:4]</g>',
            $html
        );
    }

    public function testTheStacksGroupKeyIsTheDefaultUnderATypesOwnGroupOption(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_TEXT, 'one'),
            $this->createSection(TestSection::TYPE_ALT, 'one'),
            $this->createSection(TestSection::TYPE_TEXT, 'two'),
            $this->createSection(TestSection::TYPE_GROUP_A, 'two'),
        ])->groupKey(fn (Section $section): string => (string)$section->name);

        self::assertSame(
            '<g view="_sections" key="one" wrapper="">[1:1][2:2]</g>'
            . '<g view="_sections" key="two" wrapper="">[3:3]</g>'
            . '<g view="_sections" key="a" wrapper="">[4:4]</g>',
            $html
        );
    }

    public function testAWrapperSpansConsecutiveGroups(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_DARK),
            $this->createSection(TestSection::TYPE_DARK_ALT),
            $this->createSection(TestSection::TYPE_DARK_TABS),
            $this->createSection(TestSection::TYPE_LIGHT),
        ]);

        self::assertSame(
            '<div class="dark">'
            . '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="dark">[1:1]</g>'
            . '<a view="_alt" key="@cmsTestViews/site/_alt" wrapper="dark">[2:2]</a>'
            . '<t>[3]</t>'
            . '</div>'
            . '<div class="light">'
            . '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="light">[4:4]</g>'
            . '</div>',
            $html
        );
    }

    public function testAnUnwrappedGroupClosesTheWrapperBetweenTwoEqualKeys(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_DARK),
            $this->createSection(TestSection::TYPE_ALT),
            $this->createSection(TestSection::TYPE_DARK),
        ]);

        self::assertSame(
            '<div class="dark">'
            . '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="dark">[1:1]</g>'
            . '</div>'
            . '<a view="_alt" key="@cmsTestViews/site/_alt" wrapper="">[2:2]</a>'
            . '<div class="dark">'
            . '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="dark">[3:3]</g>'
            . '</div>',
            $html
        );
    }

    public function testTheWrapperClosureReplacesTheElement(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_DARK),
            $this->createSection(TestSection::TYPE_DARK_ALT),
        ])->wrapper(fn (string $key, string $content, array $groups): Stringable => Span::make()
            ->attribute('data-groups', (string)count($groups))
            ->addClass($key)
            ->content($content));

        self::assertSame(
            '<span class="dark" data-groups="2">'
            . '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="dark">[1:1]</g>'
            . '<a view="_alt" key="@cmsTestViews/site/_alt" wrapper="dark">[2:2]</a>'
            . '</span>',
            $html
        );
    }

    public function testTheStacksWrapperKeyIsTheDefaultUnderATypesOwnWrapperOption(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_ALT),
            $this->createSection(TestSection::TYPE_LIGHT),
        ])->wrapperKey(fn (Section $section): string => 'fallback');

        self::assertSame(
            '<div class="fallback">'
            . '<a view="_alt" key="@cmsTestViews/site/_alt" wrapper="fallback">[1:1]</a>'
            . '</div>'
            . '<div class="light">'
            . '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="light">[2:2]</g>'
            . '</div>',
            $html
        );
    }

    public function testCollectAllPullsALaterSectionIntoTheHeadsGroup(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_COLLECT_ALL),
            $this->createSection(TestSection::TYPE_TEXT),
            $this->createSection(TestSection::TYPE_COLLECT_ALL),
        ]);

        self::assertSame(
            '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[1:1][3:3]</g>'
            . '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[2:2]</g>',
            $html
        );
    }

    public function testCollectAdjacentStopsAtTheFirstSectionOfAnotherType(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_COLLECT_ADJACENT),
            $this->createSection(TestSection::TYPE_COLLECT_ADJACENT),
            $this->createSection(TestSection::TYPE_TEXT),
            $this->createSection(TestSection::TYPE_COLLECT_ADJACENT),
        ]);

        self::assertSame(
            '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[1:1][2:2]</g>'
            . '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[3:3][4:4]</g>',
            $html
        );
    }

    public function testAnEarlierHeadWinsAContestedSection(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_HEAD),
            $this->createSection(TestSection::TYPE_ITEM),
            $this->createSection(TestSection::TYPE_HEAD),
            $this->createSection(TestSection::TYPE_ITEM),
        ]);

        self::assertSame(
            '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[1:1][2:2][4:4]</g>'
            . '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[3:3]</g>',
            $html
        );
    }

    public function testAHeadWithAnEmptyResultIsGroupedLikeAnySection(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_TEXT),
            $this->createSection(TestSection::TYPE_COLLECT_NOTHING),
            $this->createSection(TestSection::TYPE_TEXT),
        ]);

        self::assertSame(
            '<g view="_sections" key="@cmsTestViews/site/_sections" wrapper="">[1:1][2:2][3:3]</g>',
            $html
        );
    }

    public function testRenderAdjacentRendersEachRunOnceAndAnswersNothingForATakenSection(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_POPS_A),
            $this->createSection(TestSection::TYPE_POPS_A),
            $this->createSection(TestSection::TYPE_POPS_B),
            $this->createSection(TestSection::TYPE_POPS_A),
        ]);

        self::assertSame('<p><t>[1][2]</t><t>[3]</t><t>[4]</t></p>', $html);
    }

    public function testRenderWhereTakesItsSectionsOutOfTheGroup(): void
    {
        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_WHERE),
            $this->createSection(TestSection::TYPE_WHERE),
            $this->createSection(TestSection::TYPE_WHERE),
        ])->viewParams(['pop' => [1, 3]]);

        self::assertSame('<w><t>[1][3]</t>[1][2][3]</w>', $html);
    }

    public function testTheGroupIsTheViewContextAndTheViewParamsReachTheView(): void
    {
        $stack = $this->createStack([$this->createSection(TestSection::TYPE_CONTEXT)])
            ->viewParams(['label' => 'from the stack']);

        self::assertSame('<c same="1" label="from the stack">[1]</c>', (string)$stack);
        self::assertInstanceOf(SectionGroup::class, $stack->getGroups()[0]);
        self::assertSame($stack, $stack->getGroups()[0]->getStack());
    }

    public function testNeighboursFollowRenderOrder(): void
    {
        $sections = [
            $this->createSection(TestSection::TYPE_COLLECT_ALL),
            $this->createSection(TestSection::TYPE_ALT),
            $this->createSection(TestSection::TYPE_COLLECT_ALL),
        ];

        $stack = $this->createStack($sections);
        $stack->render();

        [$head, $between, $collected] = $sections;

        self::assertSame([$head, $collected, $between], $stack->getSections());

        self::assertNull($stack->getPrevious($head));
        self::assertSame($collected, $stack->getNext($head));
        self::assertSame($head, $stack->getPrevious($collected));
        self::assertSame($between, $stack->getNext($collected));
        self::assertSame($collected, $stack->getPrevious($between));
        self::assertNull($stack->getNext($between));

        [$first, $second] = $stack->getGroups();

        self::assertNull($first->getPrevious());
        self::assertSame($second, $first->getNext());
        self::assertSame($first, $second->getPrevious());
        self::assertNull($second->getNext());
    }

    public function testTheConfigureEventFiresOncePerGroupAndCanChangeItsViewFile(): void
    {
        $senders = [];

        Event::on(SectionGroup::class, Widget::EVENT_CONFIGURE, function (Event $event) use (&$senders): void {
            /** @var SectionGroup<Section> $group */
            $group = $event->sender;
            $senders[] = $group;

            $group->viewFile('@cmsTestViews/site/_alt');
        });

        $html = (string)$this->createStack([
            $this->createSection(TestSection::TYPE_TEXT),
            $this->createSection(TestSection::TYPE_GROUP_B),
        ]);

        self::assertCount(2, $senders);
        self::assertSame(
            '<a view="_alt" key="@cmsTestViews/site/_sections" wrapper="">[1:1]</a>'
            . '<a view="_alt" key="b" wrapper="">[2:2]</a>',
            $html
        );
    }

    public function testAnEntryWithoutSectionsRendersNothing(): void
    {
        $entry = Entry::create();
        $entry->name = 'No sections';

        self::assertTrue($entry->insert());

        $stack = SectionStack::make()
            ->viewFile('@cmsTestViews/site/_sections')
            ->entry($entry);

        self::assertSame('', (string)$stack);
        self::assertSame([], $stack->getSections());
    }

    public function testARelativeViewNameResolvesAgainstTheRenderingView(): void
    {
        $stack = SectionStack::make()
            ->sections([$this->createSection(TestSection::TYPE_TEXT)]);

        $html = Yii::$app->getView()->render('@cmsTestViews/site/_relative', ['stack' => $stack]);

        self::assertSame('<g view="_sections" key="_sections" wrapper="">[1:1]</g>', $html);
    }

    /**
     * @param Section[] $sections
     * @return SectionStack<Section>
     */
    private function createStack(array $sections): SectionStack
    {
        return SectionStack::make()
            ->viewFile('@cmsTestViews/site/_sections')
            ->sections($sections);
    }

    private function createSection(int $type, ?string $name = null): TestSection
    {
        $section = TestSection::create();
        $section->id = ++$this->sectionId;
        $section->type = $type;
        $section->name = $name ?? '';

        return $section;
    }
}

/**
 * The scratch model carries every type option the stack reads. Composer's PSR-4 map finds one class per file, so
 * it is only autoloadable while this test file is loaded.
 */
class TestSection extends Section
{
    public const int TYPE_TEXT = 1;
    public const int TYPE_ALT = 2;
    public const int TYPE_INVISIBLE = 3;
    public const int TYPE_VISIBLE_UNLESS_HIDDEN = 4;
    public const int TYPE_GROUP_A = 5;
    public const int TYPE_GROUP_B = 6;
    public const int TYPE_GROUP_A_ALT = 7;
    public const int TYPE_DARK = 8;
    public const int TYPE_DARK_ALT = 9;
    public const int TYPE_DARK_TABS = 10;
    public const int TYPE_LIGHT = 11;
    public const int TYPE_COLLECT_ALL = 12;
    public const int TYPE_COLLECT_ADJACENT = 13;
    public const int TYPE_COLLECT_NOTHING = 14;
    public const int TYPE_HEAD = 15;
    public const int TYPE_ITEM = 16;
    public const int TYPE_POPS_A = 17;
    public const int TYPE_POPS_B = 18;
    public const int TYPE_WHERE = 19;
    public const int TYPE_CONTEXT = 20;

    #[Override]
    public function getTypes(): array
    {
        return [
            SectionType::make(self::TYPE_TEXT)
                ->name('Text'),
            SectionType::make(self::TYPE_ALT)
                ->name('Alt')
                ->viewFile('@cmsTestViews/site/_alt'),
            SectionType::make(self::TYPE_INVISIBLE)
                ->name('Invisible')
                ->visible(false),
            SectionType::make(self::TYPE_VISIBLE_UNLESS_HIDDEN)
                ->name('Visible unless hidden')
                ->visible(fn (Section $section): bool => $section->name !== 'hidden'),
            SectionType::make(self::TYPE_GROUP_A)
                ->name('Group A')
                ->group('a'),
            SectionType::make(self::TYPE_GROUP_B)
                ->name('Group B')
                ->group('b'),
            SectionType::make(self::TYPE_GROUP_A_ALT)
                ->name('Group A, other view')
                ->group('a')
                ->viewFile('@cmsTestViews/site/_alt'),
            SectionType::make(self::TYPE_DARK)
                ->name('Dark')
                ->wrapper('dark'),
            SectionType::make(self::TYPE_DARK_ALT)
                ->name('Dark, other view')
                ->wrapper(fn (Section $section): string => 'dark')
                ->viewFile('@cmsTestViews/site/_alt'),
            SectionType::make(self::TYPE_DARK_TABS)
                ->name('Dark, third view')
                ->wrapper('dark')
                ->viewFile('@cmsTestViews/site/_tabs'),
            SectionType::make(self::TYPE_LIGHT)
                ->name('Light')
                ->wrapper('light'),
            SectionType::make(self::TYPE_COLLECT_ALL)
                ->name('Collects all of its type')
                ->collect(SectionStack::collectAll()),
            SectionType::make(self::TYPE_COLLECT_ADJACENT)
                ->name('Collects the adjacent ones of its type')
                ->collect(SectionStack::collectAdjacent()),
            SectionType::make(self::TYPE_COLLECT_NOTHING)
                ->name('Collects nothing')
                ->collect(fn (Section $head, array $rest): array => []),
            SectionType::make(self::TYPE_HEAD)
                ->name('Head')
                ->collect(self::collectItems()),
            SectionType::make(self::TYPE_ITEM)
                ->name('Item'),
            SectionType::make(self::TYPE_POPS_A)
                ->name('Popped, first type')
                ->viewFile('@cmsTestViews/site/_pops'),
            SectionType::make(self::TYPE_POPS_B)
                ->name('Popped, second type')
                ->viewFile('@cmsTestViews/site/_pops'),
            SectionType::make(self::TYPE_WHERE)
                ->name('Popped by callback')
                ->viewFile('@cmsTestViews/site/_where'),
            SectionType::make(self::TYPE_CONTEXT)
                ->name('Context')
                ->viewFile('@cmsTestViews/site/_context'),
        ];
    }

    private static function collectItems(): Closure
    {
        return static fn (Section $head, array $rest): array => array_values(
            array_filter($rest, fn (Section $section): bool => $section->type === self::TYPE_ITEM)
        );
    }
}
