<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Queries;

use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Types\EntryType;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Modules\Admin\Widgets\ScheduledAncestorAlert;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Filters\PageCache;
use Override;
use Yii;
use yii\web\Controller;

/**
 * An enabled entry dated in the future is off the site until its time — and on the draft domain, and in the
 * admin, all along.
 */
class EntryPublishDateTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        ActiveQuery::resetStatus();
        Yii::$container->clear(Entry::class);
        parent::tearDown();
    }

    public function testAFutureEntryIsOffTheSiteUntilItsTime(): void
    {
        $past = $this->createEntry('Past', 'past', '-1 hour');
        $future = $this->createEntry('Future', 'future', '+1 hour');

        $ids = Entry::find()->whereStatus(Entry::STATUS_ENABLED)->select('id')->column();

        self::assertContains($past->id, array_map(intval(...), $ids));
        self::assertNotContains($future->id, array_map(intval(...), $ids));

        self::assertNull(Entry::find()->enabled()->andWhere(['id' => $future->id])->one());
    }

    public function testTheDraftDomainAndTheAdminSeeItAllAlong(): void
    {
        $future = $this->createEntry('Future', 'future', '+1 hour');

        self::assertNotNull(Entry::find()->andWhere(['id' => $future->id])->one());
        self::assertNotNull(Entry::find()->whereStatus(Entry::STATUS_DRAFT)->andWhere(['id' => $future->id])->one());
    }

    public function testAnEntrySavedThisMinuteIsNotHidden(): void
    {
        $entry = $this->createEntry('Now', 'now', 'now');

        self::assertNotNull(Entry::find()->enabled()->andWhere(['id' => $entry->id])->one());
    }

    public function testATypeHidingTheDateIsNotGatedByIt(): void
    {
        self::assertFalse(PublishDateTestEntry::findType(PublishDateTestEntry::TYPE_UNDATED)?->schedules());

        $entry = $this->createEntry('Undated', 'undated', '+1 hour', PublishDateTestEntry::TYPE_UNDATED);

        self::assertNotNull(PublishDateTestEntry::find()->enabled()->andWhere(['id' => $entry->id])->one());
        self::assertNull(PublishDateTestEntry::find()->enabled()->andWhere(['id' => $this->createEntry('Dated', 'dated', '+1 hour')->id])->one());
    }

    public function testATypeThatDoesNotScheduleListsAFutureEntry(): void
    {
        $event = $this->createEntry('Event', 'event', '+1 day', PublishDateTestEntry::TYPE_EVENT);

        $ids = PublishDateTestEntry::find()->whereStatus(Entry::STATUS_ENABLED)->select('id')->column();
        self::assertContains($event->id, array_map(intval(...), $ids));
        self::assertNotNull(PublishDateTestEntry::find()->enabled()->andWhere(['id' => $event->id])->one());

        self::assertFalse($event->isScheduled());
        self::assertNotSame('clock', $event->getStatusIcon());
    }

    public function testAnExplicitOptionOverridesTheHiddenField(): void
    {
        self::assertTrue(PublishDateTestEntry::findType(Entry::TYPE_DEFAULT)?->schedules());
        self::assertTrue(EntryType::make(4)->hiddenFields('publish_date')->schedule()->schedules());
        self::assertFalse(EntryType::make(4)->schedule(false)->schedules());

        $expected = [PublishDateTestEntry::TYPE_UNDATED, PublishDateTestEntry::TYPE_EVENT];
        self::assertSame($expected, EntryQuery::getUnscheduledTypes(PublishDateTestEntry::class));
        self::assertSame($expected, EntryQuery::getTypesWithoutPublishDate(PublishDateTestEntry::class));
    }

    public function testOnlyAScheduledTypeHasTheSchedulingHint(): void
    {
        $hint = Yii::t('cms', 'ENTRY_PUBLISH_DATE_HINT');

        $entry = PublishDateTestEntry::create();
        self::assertStringContainsString($hint, EntryActiveForm::make()->model($entry)->render());

        $event = PublishDateTestEntry::instantiateByType(PublishDateTestEntry::TYPE_EVENT);
        $html = EntryActiveForm::make()->model($event)->render();

        self::assertStringContainsString('name="Entry[publish_date]"', $html);
        self::assertStringNotContainsString($hint, $html);
    }

    public function testTheNextPublishTimeIgnoresATypeThatDoesNotSchedule(): void
    {
        Yii::$container->set(Entry::class, [
            'types' => fn (): array => [
                EntryType::make(Entry::TYPE_DEFAULT)
                    ->name('Default'),
                EntryType::make(PublishDateTestEntry::TYPE_EVENT)
                    ->name('Event')
                    ->schedule(false),
            ],
        ]);

        self::assertFalse(Entry::findType(PublishDateTestEntry::TYPE_EVENT)?->schedules());

        $this->createEntry('Event', 'event', '+10 minutes', PublishDateTestEntry::TYPE_EVENT);
        self::assertNull(Entry::getModule()->getNextPublishTime());

        $future = $this->createEntry('Future', 'future', '+20 minutes');
        self::assertSame($future->publish_date?->getTimestamp(), Entry::getModule()->getNextPublishTime());
    }

    public function testTheAdminMarksItScheduled(): void
    {
        $future = $this->createEntry('Future', 'future', '+1 hour');
        $past = $this->createEntry('Past', 'past', '-1 hour');

        self::assertTrue($future->isScheduled());
        self::assertSame('clock', $future->getStatusIcon());
        self::assertSame(Yii::t('cms', 'ENTRY_STATUS_SCHEDULED', [
            'date' => Yii::$app->getFormatter()->asDatetime($future->publish_date, 'short'),
        ]), $future->getStatusName());

        self::assertFalse($past->isScheduled());
        self::assertNotSame('clock', $past->getStatusIcon());

        $future->status = Entry::STATUS_DRAFT;
        self::assertFalse($future->isScheduled());
    }

    public function testACachedPageExpiresWhenTheNextEntryGoesLive(): void
    {
        $this->createEntry('Future', 'future', '+10 minutes');

        $filter = new PageCache(['duration' => 3600, 'owner' => new Controller('test', Yii::$app)]);

        self::assertGreaterThan(500, $filter->duration);
        self::assertLessThanOrEqual(600, $filter->duration);
    }

    public function testNothingScheduledLeavesTheDurationAlone(): void
    {
        $filter = new PageCache(['duration' => 3600, 'owner' => new Controller('test', Yii::$app)]);

        self::assertSame(3600, $filter->duration);
    }

    public function testALiveChildOfAScheduledParentIsWarnedAbout(): void
    {
        Entry::getModule()->enableNestedEntries = true;

        $parent = $this->createEntry('Parent', 'parent', '+1 day');
        $child = $this->createEntry('Child', 'child', '-1 day', parent: $parent);

        self::assertSame($parent->id, $child->findScheduledAncestor()?->id);

        $html = ScheduledAncestorAlert::make()->entry($child)->render();
        self::assertStringContainsString('data-alert="warning"', $html);
        self::assertStringContainsString('“Parent”', $html);

        $published = $this->createEntry('Published', 'published', '-1 day');
        $sibling = $this->createEntry('Sibling', 'sibling', '-1 day', parent: $published);

        self::assertNull($sibling->findScheduledAncestor());
        self::assertSame('', ScheduledAncestorAlert::make()->entry($sibling)->render());
    }

    private function createEntry(
        string $name,
        string $slug,
        string $date,
        int $type = Entry::TYPE_DEFAULT,
        ?Entry $parent = null,
    ): Entry {
        $entry = PublishDateTestEntry::create();
        $entry->loadDefaultValues();
        $entry->populateParentRelation($parent);
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = $type;
        $entry->name = $name;
        $entry->slug = $slug;
        $entry->publish_date = new DateTime($date);

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }
}

class PublishDateTestEntry extends Entry
{
    public const int TYPE_UNDATED = 2;
    public const int TYPE_EVENT = 3;

    #[Override]
    public function getTypes(): array
    {
        return [
            EntryType::make(self::TYPE_DEFAULT)
                ->name('Default'),
            EntryType::make(self::TYPE_UNDATED)
                ->name('Undated')
                ->hiddenFields('publish_date'),
            EntryType::make(self::TYPE_EVENT)
                ->name('Event')
                ->schedule(false),
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Entry';
    }
}
