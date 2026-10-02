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

    public function testTheFormCarriesWhenItWasRendered(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $html = EntryActiveForm::make()->model($this->createEntry())->render();

        self::assertMatchesRegularExpression('/<input type="hidden" name="Entry\[loadedAt\]" value="(\d+)">/', $html);
        preg_match('/name="Entry\[loadedAt\]" value="(\d+)"/', $html, $matches);
        self::assertEqualsWithDelta(time(), (int)($matches[1] ?? 0), 5);

        $new = Entry::create();
        $new->loadDefaultValues();

        self::assertStringNotContainsString('loadedAt', EntryActiveForm::make()->model($new)->render());
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
