<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Media\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

/**
 * A form showing a resolved URL would store it on the next save, so the admin reads and writes `entry:` as stored.
 */
class EntryLinkFunctionalTest extends TestCase
{
    use FunctionalTestTrait;
    use CmsFixtureTrait, UserFixtureTrait {
        CmsFixtureTrait::fixtures insteadof UserFixtureTrait;
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$container->setDefinitions([
            Section::class => ['class' => LinkSection::class],
            TestSection::class => ['class' => LinkSection::class],
        ]);

        Section::instance(true);
        TestSection::instance(true);

        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        $this->getWebUser()->login($user);
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

    public function testAnEntryLinkRoundTripsThroughTheForm(): void
    {
        $section = LinkSection::findOne($this->getSectionFromFixture('section-column')->id) ?? self::fail('No section.');
        $section->content = '<p><a href="entry:1#team">Ågot 東京 🚀</a></p>';
        $section->link = 'entry:1';

        self::assertSame(1, $section->update());
        self::assertSame('<p><a href="entry:1#team">Ågot 東京 🚀</a></p>', LinkSection::findOne($section->id)?->content);

        $this->open("/admin/cms/section/update?id=$section->id");
        self::assertResponseIsSuccessful();

        self::assertStringContainsString('&lt;a href="entry:1#team"&gt;', $this->getResponseContent());
        self::assertStringContainsString('linkPickerUrl', $this->getResponseContent());
        self::assertInputValueSame('Section[link]', 'entry:1');
        self::assertSelectorExists('button[data-picker][data-picker-input]');
        self::assertSelectorTextContains('.form-hint', 'Test Page – Enabled');

        $this->submit('form', ['Section[status]' => (string)TestSection::STATUS_DRAFT]);
        self::assertResponseIsSuccessful();

        $section = LinkSection::findOne($section->id);

        self::assertSame(TestSection::STATUS_DRAFT, $section->status);
        self::assertSame('<p><a href="entry:1#team">Ågot 東京 🚀</a></p>', $section->content);
        self::assertSame('entry:1', $section->link);
    }

    public function testThePickerListsEntriesWithTheirUrl(): void
    {
        $this->open('/admin/cms/entry-link/index?tenant=1');
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('#picker #entry-link-grid-view');
        self::assertSelectorExists('#picker [data-picker-value="entry:1"][data-picker-title="Test Page – Enabled"]');
        self::assertSelectorTextContains('#picker', '/test-1');
    }

    public function testThePickerOffersOnlyTheTypesTheAttributeAllows(): void
    {
        $this->open('/admin/cms/entry-link/index?tenant=1&types=' . TestEntry::TYPE_POST);
        self::assertResponseIsSuccessful();

        self::assertSelectorNotExists('[data-picker-value="entry:1"]');
    }

    private function getResponseContent(): string
    {
        return (string)self::$crawler->html();
    }
}

/**
 * @property string|null $link
 */
class LinkSection extends TestSection
{
    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            ...parent::getCustomAttributes(),
            \Hirtz\Skeleton\Models\CustomAttributes\LinkCustomAttribute::make('link'),
        ];
    }
}
