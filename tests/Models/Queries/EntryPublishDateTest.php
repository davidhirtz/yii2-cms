<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Queries;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Models\Types\EntryType;
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
        self::assertSame([PublishDateTestEntry::TYPE_UNDATED], EntryQuery::getTypesWithoutPublishDate(PublishDateTestEntry::class));

        $entry = $this->createEntry('Undated', 'undated', '+1 hour', PublishDateTestEntry::TYPE_UNDATED);

        self::assertNotNull(PublishDateTestEntry::find()->enabled()->andWhere(['id' => $entry->id])->one());
        self::assertNull(PublishDateTestEntry::find()->enabled()->andWhere(['id' => $this->createEntry('Dated', 'dated', '+1 hour')->id])->one());
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

    private function createEntry(string $name, string $slug, string $date, int $type = Entry::TYPE_DEFAULT): Entry
    {
        $entry = PublishDateTestEntry::create();
        $entry->loadDefaultValues();
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

    #[Override]
    public function getTypes(): array
    {
        return [
            EntryType::make(self::TYPE_DEFAULT)
                ->name('Default'),
            EntryType::make(self::TYPE_UNDATED)
                ->name('Undated')
                ->hiddenFields('publish_date'),
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Entry';
    }
}
