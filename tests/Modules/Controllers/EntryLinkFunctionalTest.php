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
        self::assertStringContainsString('linkDialogUrl', $this->getResponseContent());
        self::assertInputValueSame('Section[link]', 'entry:1');
        self::assertSelectorExists('[data-link-field] .link-field-input[hidden] input[name="Section[link]"][hx-get]');
        self::assertSelectorTextContains('.link-field-selected:not([hidden]) [data-link-field-text]', 'Test Page – Enabled');
        self::assertSelectorTextContains('.link-field-selected [data-link-field-description]', '/test-1');

        $this->submit('form', ['Section[status]' => (string)TestSection::STATUS_DRAFT]);
        self::assertResponseIsSuccessful();

        $section = LinkSection::findOne($section->id);

        self::assertSame(TestSection::STATUS_DRAFT, $section->status);
        self::assertSame('<p><a href="entry:1#team">Ågot 東京 🚀</a></p>', $section->content);
        self::assertSame('entry:1', $section->link);
    }

    public function testTheSuggestionsMatchANameOrAPath(): void
    {
        $this->open('/admin/cms/entry-link/index?tenant=1&q=' . urlencode('Test Page'));
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('#autocomplete-options [data-autocomplete-value="entry:1"][data-autocomplete-text="Test Page – Enabled"]');
        self::assertSelectorExists('#autocomplete-options [data-autocomplete-value="entry:1"][data-autocomplete-description="/test-1"]');

        $this->open('/admin/cms/entry-link/index?tenant=1&q=' . urlencode('/test-1'));
        self::assertSelectorExists('#autocomplete-options [data-autocomplete-value="entry:1"]');
    }

    public function testAUrlGetsNoSuggestions(): void
    {
        foreach (['https://www.example.com/', 'mailto:å@example.com', '#team', '?q=東京', 'Nothing like this 🚀'] as $q) {
            $this->open('/admin/cms/entry-link/index?tenant=1&q=' . urlencode($q));
            self::assertResponseIsSuccessful();
            self::assertSame('', trim((string)self::$client->getResponse()->getContent()), $q);
        }
    }

    public function testTheSuggestionsOfferOnlyTheTypesTheAttributeAllows(): void
    {
        $this->open('/admin/cms/entry-link/index?tenant=1&q=Test&types=' . TestEntry::TYPE_POST);
        self::assertResponseIsSuccessful();

        self::assertSelectorNotExists('[data-autocomplete-value="entry:1"]');
    }

    public function testTheDialogIsFilledWithTheLinkItEdits(): void
    {
        $this->open('/admin/cms/entry-link/dialog?' . http_build_query([
            'tenant' => 1,
            'href' => 'entry:1#team',
            'text' => 'Ågot 東京 🚀',
            'title' => 'Mu' . "\u{308}" . 'ller',
            'target' => '_blank',
            'class' => 'btn',
            'classes' => json_encode(['' => 'None', 'btn' => 'Button']),
        ]));

        self::assertResponseIsSuccessful();

        self::assertSelectorExists('form#link-dialog[data-link-dialog]');
        self::assertInputValueSame('href', 'entry:1#team');
        self::assertInputValueSame('text', 'Ågot 東京 🚀');
        self::assertInputValueSame('title', 'Mu' . "\u{308}" . 'ller');
        self::assertSelectorExists('#link-dialog input[name="target"][value="_blank"][checked]');
        self::assertSelectorExists('#link-dialog select[name="class"] option[value="btn"][selected]');
        self::assertSelectorTextContains('#link-dialog .link-field-selected [data-link-field-text]', 'Test Page – Enabled');
    }

    public function testTheDialogOffersNoStyleWithoutClasses(): void
    {
        $this->open('/admin/cms/entry-link/dialog?tenant=1&href=' . urlencode('https://www.example.com/'));
        self::assertResponseIsSuccessful();

        self::assertSelectorNotExists('#link-dialog select[name="class"]');
        self::assertSelectorExists('#link-dialog .link-field-selected[hidden]');
        self::assertInputValueSame('href', 'https://www.example.com/');
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
