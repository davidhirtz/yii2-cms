<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

/**
 * Two editors on one entry: the one saving second is told, instead of overwriting the first.
 */
class EntryStaleSaveTest extends TestCase
{
    use UserFixtureTrait;

    public function testASaveOverAnotherOneMadeSinceIsRefused(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));
        $entry = $this->createEntry();

        $first = Entry::findOne($entry->id);
        $second = Entry::findOne($entry->id);
        self::assertInstanceOf(Entry::class, $first);
        self::assertInstanceOf(Entry::class, $second);

        $first->name = 'First editor';
        self::assertTrue($first->save(), print_r($first->getErrors(), true));

        $second->load(['Entry' => ['name' => 'Second editor', 'loadedAt' => (string)(time() - 5)]]);

        self::assertFalse($second->save());
        self::assertArrayHasKey('loadedAt', $second->getErrors());
        self::assertSame('Second editor', $second->name);
        self::assertSame('First editor', Entry::findOne($entry->id)?->name);
    }

    public function testAChangeToACountInBetweenIsNoConflict(): void
    {
        $entry = $this->createEntry();
        $loadedAt = time() - 5;

        $section = Section::create();
        $section->loadDefaultValues();
        $section->type = Section::TYPE_DEFAULT;
        $section->name = 'Added elsewhere';
        $section->populateEntryRelation($entry);
        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        $stale = Entry::findOne($entry->id);
        self::assertInstanceOf(Entry::class, $stale);

        $stale->load(['Entry' => ['name' => 'Renamed', 'loadedAt' => (string)$loadedAt]]);

        self::assertTrue($stale->save(), print_r($stale->getErrors(), true));
    }

    public function testAFormWithoutTheTimeChecksNothing(): void
    {
        $entry = $this->createEntry();
        $entry->name = 'Changed';
        self::assertTrue($entry->save(), print_r($entry->getErrors(), true));

        $other = Entry::findOne($entry->id);
        self::assertInstanceOf(Entry::class, $other);
        $other->name = 'Changed again';

        self::assertTrue($other->save(), print_r($other->getErrors(), true));
    }

    public function testTheFormCarriesTheVersionItWasRenderedAt(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));
        $entry = $this->createEntry();

        $entry->name = 'Saved once';
        self::assertNotFalse($entry->update());

        $html = EntryActiveForm::make()->model($entry)->render();

        self::assertStringContainsString('<input type="hidden" name="Entry[loadedVersion]" value="' . $entry->getStaleSaveVersion() . '">', $html);
        self::assertGreaterThan(0, $entry->getStaleSaveVersion());

        $new = Entry::create();
        $new->loadDefaultValues();

        self::assertStringNotContainsString('loadedVersion', EntryActiveForm::make()->model($new)->render());
    }

    /**
     * The trail keeps seconds, so only the version tells a save within the second the record was read in apart.
     */
    public function testASaveWithinTheSameSecondIsRefusedByVersion(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));
        $entry = $this->createEntry();

        $version = $entry->getStaleSaveVersion();

        $other = Entry::findOne($entry->id) ?? self::fail('The entry is missing.');
        $other->name = 'Other editor';
        self::assertNotFalse($other->update());

        $stale = Entry::findOne(['id' => $entry->id]) ?? self::fail('The entry is missing.');
        $stale->load(['Entry' => ['name' => 'Agent', 'loadedVersion' => (string)$version]]);

        self::assertFalse($stale->save());
        self::assertArrayHasKey('loadedAt', $stale->getErrors());
        self::assertSame('Other editor', Entry::find()->where(['id' => $entry->id])->one()?->name);
    }

    public function testTheConflictIsAFlashNotALineInTheForm(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $entry = $this->createEntry();
        $entry->addError('loadedAt', 'Changed by someone else.');

        $html = EntryActiveForm::make()->model($entry)->render();

        self::assertStringNotContainsString('>Changed by someone else.', $html);
        self::assertStringContainsString('data-stale-save="Changed by someone else."', $html);
        self::assertSame(['Changed by someone else.'], $this->getWebSession()->getFlash('warning'));
    }

    private function createEntry(): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = 'Original';
        $entry->slug = 'original';

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }
}
