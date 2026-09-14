<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Cms\Models\Sets\SectionTemplate;
use Hirtz\Cms\Modules\Admin\Controllers\SectionController;
use Hirtz\Cms\Modules\Admin\Data\SectionActiveDataProvider;
use Hirtz\Cms\Modules\Admin\Widgets\Navs\SectionActionDropdown;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

/**
 * The dropdown replaces the index page's create button, so the provider decides whether it offers the actions of
 * one section or the ones that add a section to its entry.
 */
class SectionActionDropdownTest extends TestCase
{
    use UserFixtureTrait;

    private Entry $entry;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Entry::AUTH_ENTRY);

        Yii::$app->getUser()->setIdentity($user);

        $this->entry = $this->createEntry();
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        Section::instance(true);

        parent::tearDown();
    }

    public function testTheProviderOffersTheCreateButtonAndTheDeclaredSets(): void
    {
        $this->setSectionSets();

        $html = $this->render();

        self::assertStringContainsString('New Section', $html);
        self::assertStringContainsString('Add Section Set', $html);
        self::assertStringContainsString('<option value="1">Landing page</option>', $html);
        self::assertStringContainsString('create-set', $html);
    }

    public function testTheSetButtonIsHiddenWithoutDeclaredSets(): void
    {
        $html = $this->render();

        self::assertStringContainsString('New Section', $html);
        self::assertStringNotContainsString('Add Section Set', $html);
    }

    public function testAModelOffersTheSectionActions(): void
    {
        $this->setSectionSets();

        $section = $this->createSection();

        // The copy button's route is relative, so it needs the controller the update page would have.
        Yii::$app->controller = Yii::createObject(SectionController::class, [
            'section',
            Yii::$app->getModule('admin')->getModule('cms'),
        ]);

        $html = SectionActionDropdown::make()
            ->model($section)
            ->render();

        self::assertStringContainsString('Move / Copy', $html);
        self::assertStringNotContainsString('Add Section Set', $html);
        self::assertStringNotContainsString('New Section', $html);
    }

    private function render(): string
    {
        $provider = Yii::$container->get(SectionActiveDataProvider::class, [], [
            'entry' => $this->entry,
        ]);

        return SectionActionDropdown::make()
            ->provider($provider)
            ->render();
    }

    private function setSectionSets(): void
    {
        Yii::$container->set(Section::class, [
            'sectionSets' => fn (): array => [
                SectionSet::make(1)
                    ->name('Landing page')
                    ->sections(SectionTemplate::make(Section::TYPE_DEFAULT)),
            ],
        ]);

        DefinitionRegistry::resetClass(Section::class);
    }

    private function createEntry(): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = 'Page';
        $entry->slug = 'page';

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }

    private function createSection(): Section
    {
        $section = Section::create();
        $section->loadDefaultValues();
        $section->status = Section::STATUS_ENABLED;
        $section->type = Section::TYPE_DEFAULT;
        $section->name = 'Hero';
        $section->populateEntryRelation($this->entry);

        self::assertTrue($section->insert(), print_r($section->getErrors(), true));

        return $section;
    }
}
