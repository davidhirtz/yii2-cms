<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Widgets;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Migrations\Traits\FooterColumnTrait;
use Hirtz\Cms\Migrations\Traits\MenuColumnTrait;
use Hirtz\Cms\Models\Traits\FooterAttributeTrait;
use Hirtz\Cms\Models\Traits\MenuAttributeTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\NavItems;
use Hirtz\Skeleton\Db\ActiveQuery;
use Override;
use Yii;
use yii\db\Migration;

class NavItemsTest extends TestCase
{
    private bool $hasColumns = false;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Entry::getModule()->enableNestedEntries = true;
    }

    /**
     * `show_in_menu` and `show_in_footer` are columns a project adds with the migration traits, so the DDL belongs
     * outside the test transaction.
     */
    #[Override]
    protected function tearDownSchema(): void
    {
        if ($this->hasColumns) {
            $migration = new TestNavColumnMigration(['compact' => true]);
            $migration->dropColumns();
        }
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Entry::class);

        Entry::instance(true);
        NavItems::reset();
        ActiveQuery::resetStatus();

        parent::tearDown();
    }

    /**
     * An `Entry` that declares neither `show_in_menu` nor `show_in_footer` has no menu at all — and used to be a
     * `TypeError` on every page that rendered one.
     */
    public function testAnEntryWithoutTheAttributesHasNoMenu(): void
    {
        $this->createEntry('First', 'first');

        self::assertSame([], NavItems::getMenuItems());
        self::assertSame([], NavItems::getFooterItems());
        self::assertSame([], NavItems::getMainMenuItems());
    }

    public function testOnlyTheEntriesThatAskForItAreMenuItems(): void
    {
        $this->useNavEntry();

        $shown = $this->createEntry('Shown', 'shown', attributes: ['show_in_menu' => true]);
        $this->createEntry('Hidden', 'hidden');

        self::assertSame([$shown->id], array_keys(NavItems::getMenuItems()));
    }

    public function testTheMainMenuIsTheRootLevelOnly(): void
    {
        $this->useNavEntry();

        $parent = $this->createEntry('Parent', 'parent', attributes: ['show_in_menu' => true]);
        $child = $this->createEntry('Child', 'child', $parent, ['show_in_menu' => true]);

        self::assertSame([$parent->id], array_keys(NavItems::getMainMenuItems()));
        self::assertSame([$parent->id, $child->id], array_keys(NavItems::getMenuItems()));
    }

    public function testTheSubmenuIsTheChildrenOfItsParent(): void
    {
        $this->useNavEntry();

        $parent = $this->createEntry('Parent', 'parent', attributes: ['show_in_menu' => true]);
        $child = $this->createEntry('Child', 'child', $parent, ['show_in_menu' => true]);

        $parent->refresh();

        self::assertSame([$child->id], array_keys(NavItems::getSubmenuItems($parent)));

        // an entry with no children is not asked about at all
        self::assertSame([], NavItems::getSubmenuItems($child));
    }

    public function testTheFooterHasItsOwnFlag(): void
    {
        $this->useNavEntry();

        $footer = $this->createEntry('Footer', 'footer', attributes: ['show_in_footer' => true]);
        $this->createEntry('Menu', 'menu', attributes: ['show_in_menu' => true]);

        self::assertSame([$footer->id], array_keys(NavItems::getFooterItems()));
    }

    public function testAnUnpublishedEntryIsNotInTheMenu(): void
    {
        $this->useNavEntry();

        $this->createEntry('Draft', 'draft', attributes: [
            'status' => Entry::STATUS_DISABLED,
            'show_in_menu' => true,
        ]);

        self::assertSame([], NavItems::getMenuItems());
    }

    /**
     * The menu is loaded once per request, whatever asks for it afterwards.
     */
    public function testTheEntriesAreLoadedOnce(): void
    {
        $this->useNavEntry();
        $this->createEntry('Shown', 'shown', attributes: ['show_in_menu' => true]);

        NavItems::getMenuItems();

        $queries = $this->countQueries(function (): void {
            NavItems::getMenuItems();
            NavItems::getMainMenuItems();
            NavItems::getFooterItems();
        });

        self::assertSame(0, $queries);
    }

    /**
     * The status is scoped to the request, so a draft host cannot widen what the next one sees.
     */
    public function testTheStatusDoesNotOutliveTheApplication(): void
    {
        EntryQuery::setStatus(Entry::STATUS_DRAFT);

        $sql = Entry::find()
            ->whereStatus()
            ->createCommand()
            ->getRawSql();

        self::assertStringContainsString('WHERE', $sql);

        ActiveQuery::resetStatus();

        self::assertStringNotContainsString(
            'WHERE',
            Entry::find()->whereStatus()->createCommand()->getRawSql()
        );
    }

    public function testTheEntriesDoNotOutliveTheRequest(): void
    {
        $this->useNavEntry();
        $this->createEntry('Shown', 'shown', attributes: ['show_in_menu' => true]);

        self::assertCount(1, NavItems::getMenuItems());

        NavItems::reset();
        $this->createEntry('Second', 'second', attributes: ['show_in_menu' => true]);

        self::assertCount(2, NavItems::getMenuItems());
    }

    private function useNavEntry(): void
    {
        $migration = new TestNavColumnMigration(['compact' => true]);
        $migration->addColumns();

        $this->hasColumns = true;

        // a request scopes its queries by status, and the menu query inherits it
        EntryQuery::setStatus(Entry::STATUS_ENABLED);

        Yii::$container->setDefinitions([
            Entry::class => ['class' => TestNavEntry::class],
        ]);

        Entry::instance(true);
    }

    private function createEntry(string $name, string $slug, ?Entry $parent = null, array $attributes = []): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = $name;
        $entry->slug = $slug;
        $entry->populateParentRelation($parent);
        $entry->setAttributes($attributes, false);

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }
}

class TestNavEntry extends Entry
{
    use FooterAttributeTrait;
    use MenuAttributeTrait;

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getMenuAttributeRules(),
            ...$this->getFooterAttributeRules(),
        ];
    }
}

class TestNavColumnMigration extends Migration
{
    use FooterColumnTrait;
    use MenuColumnTrait;

    public function addColumns(): void
    {
        $this->addShowInMenuColumn();
        $this->addShowInFooterColumn();
    }

    public function dropColumns(): void
    {
        $this->dropShowInFooterColumn();
        $this->dropShowInMenuColumn();
    }
}
