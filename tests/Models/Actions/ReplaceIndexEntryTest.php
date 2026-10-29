<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models\Actions;

use Hirtz\Cms\Models\Actions\ReplaceIndexEntry;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Redirect;
use Override;
use Yii;

class ReplaceIndexEntryTest extends TestCase
{
    use CmsFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        TestEntry::getModule()->enableNestedEntries = true;
    }

    public function testTheEntryTakesTheIndexSlugAndLosesItsParent(): void
    {
        $parent = $this->createEntry('Parent', 'parent');
        $entry = $this->createEntry('New home', 'new-home', $parent);

        ReplaceIndexEntry::run(['entry' => $entry]);

        $entry = Entry::findOne($entry->id);

        self::assertSame('home', $entry->slug);
        self::assertSame(Entry::STATUS_ENABLED, $entry->status);
        self::assertSame(Entry::TYPE_DEFAULT, $entry->type);
        self::assertNull($entry->parent_id);
        self::assertTrue($entry->isIndex());
    }

    public function testThePreviousIndexIsRenamedAndDisabled(): void
    {
        $previous = $this->createEntry('Old home', 'home');
        $entry = $this->createEntry('New home', 'new-home');

        ReplaceIndexEntry::run(['entry' => $entry, 'previous' => $previous]);

        $previous = Entry::findOne($previous->id);

        self::assertSame("home-$previous->id", $previous->slug);
        self::assertSame(Entry::STATUS_DISABLED, $previous->status);

        self::assertSame('home', Entry::findOne($entry->id)->slug);
    }

    /**
     * The slug lives on the permalink, not on `entry`, so this is what the frontend resolves.
     */
    public function testOnlyOneEntryIsLeftOnTheIndexSlug(): void
    {
        $previous = $this->createEntry('Old home', 'home');
        $entry = $this->createEntry('New home', 'new-home');

        ReplaceIndexEntry::run(['entry' => $entry, 'previous' => $previous]);

        $index = Entry::find()
            ->whereIndex()
            ->all();

        self::assertCount(1, $index);
        self::assertNotFalse($first = reset($index));
        self::assertSame($entry->id, $first->id);
    }

    public function testThePreviousIndexCanKeepItsStatus(): void
    {
        $previous = $this->createEntry('Old home', 'home');
        $entry = $this->createEntry('New home', 'new-home');

        $action = new ReplaceIndexEntry($entry, $previous);
        $action->disablePreviousIndex = false;
        $action->replaceIndexEntry();

        $previous = Entry::findOne($previous->id);

        self::assertSame(Entry::STATUS_ENABLED, $previous->status);
        self::assertSame("home-$previous->id", $previous->slug);
    }

    public function testThePreviousIndexIsKeptWhenTheEntryCannotBeSaved(): void
    {
        $previous = $this->createEntry('Old home', 'home');
        $entry = $this->createEntry('New home', 'new-home');
        $entry->name = '';

        self::assertFalse((new ReplaceIndexEntry($entry, $previous))->replaceIndexEntry());

        $previous = Entry::findOne($previous->id);

        self::assertSame('home', $previous?->slug);
        self::assertSame(Entry::STATUS_ENABLED, $previous->status);
    }

    public function testTheOldUrlRedirectsToTheHomepage(): void
    {
        $entry = $this->createEntry('Über uns', 'about');

        self::assertTrue((new ReplaceIndexEntry(Entry::findOne($entry->id) ?? self::fail('No entry')))->replaceIndexEntry());

        $redirect = Redirect::find()->where(['like', 'request_uri', 'about'])->one();
        self::assertNotNull($redirect);
        $route = Entry::findOne($entry->id)->getRoute() ?: self::fail('The homepage has no route.');
        self::assertSame(Url::sanitize(Yii::$app->getUrlManager()->createUrl($route)), $redirect->url);
    }

    public function testTheActionReportsWhetherItSaved(): void
    {
        $entry = $this->createEntry('New home', 'new-home');

        $action = new ReplaceIndexEntry($entry);
        self::assertTrue($action->replaceIndexEntry());

        // the entry is already the index, so the second run changes nothing
        self::assertFalse($action->replaceIndexEntry());
    }

    private function createEntry(string $name, string $slug, ?Entry $parent = null): TestEntry
    {
        $entry = TestEntry::create();
        $entry->loadDefaultValues();
        $entry->status = TestEntry::STATUS_ENABLED;
        $entry->type = TestEntry::TYPE_PAGE;
        $entry->name = $name;
        $entry->slug = $slug;
        $entry->populateParentRelation($parent);

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }
}
