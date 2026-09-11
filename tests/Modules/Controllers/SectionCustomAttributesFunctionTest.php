<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestSection;
use Hirtz\Media\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class SectionCustomAttributesFunctionTest extends TestCase
{
    use FunctionalTestTrait;
    use CmsFixtureTrait, UserFixtureTrait {
        CmsFixtureTrait::fixtures insteadof UserFixtureTrait;
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The controller resolves the model through the container, which is where the test types come from.
        Yii::$container->setDefinitions([
            Section::class => ['class' => TestSection::class],
            TestSection::class => [],
        ]);

        Section::instance(true);
        TestSection::instance(true);

        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        Yii::$app->getUser()->login($user);
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

    public function testPostingGroupValuesStoresThem(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->type = TestSection::TYPE_LINK_LIST;
        $section->links = [['label' => 'Before', 'url' => 'https://before.example.com']];

        self::assertSame(1, $section->update());

        $this->open("/admin/cms/section/update?id=$section->id");
        self::assertResponseIsSuccessful();

        self::assertInputValueSame('Section[links][0][label]', 'Before');

        $this->submit('form', [
            'Section[links][0][label]' => 'After',
            'Section[links][0][url]' => 'https://after.example.com',
        ]);

        self::assertResponseIsSuccessful();

        self::assertSame(
            [['label' => 'After', 'url' => 'https://after.example.com']],
            TestSection::findOne($section->id)->links
        );
    }

    public function testAnInvalidGroupValueIsReportedAndNothingIsStored(): void
    {
        $section = $this->getSectionFromFixture('section-headline');
        $section->type = TestSection::TYPE_LINK_LIST;
        $section->links = [['label' => 'Label', 'url' => 'https://example.com']];

        self::assertSame(1, $section->update());

        $this->open("/admin/cms/section/update?id=$section->id");
        self::assertResponseIsSuccessful();

        $this->submit('form', [
            'Section[links][0][url]' => 'not a url',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.form-error');

        self::assertSame(
            [['label' => 'Label', 'url' => 'https://example.com']],
            TestSection::findOne($section->id)->links
        );
    }

    /**
     * The type select posts to the same action as the save, so only the marker header keeps the reload from writing.
     */
    public function testFormReloadRendersTheNewTypeWithoutSaving(): void
    {
        $section = $this->getSectionFromFixture('section-headline');

        $this->open("/admin/cms/section/update?id=$section->id");
        self::assertResponseIsSuccessful();

        self::assertSelectorExists('input[name="Section[subtitle]"]');
        self::assertSelectorNotExists('[data-group="links"]');

        $this->submit(
            'form',
            ['Section[type]' => (string)TestSection::TYPE_LINK_LIST],
            ['HTTP_X_FORM_RELOAD' => '1'],
        );

        self::assertResponseIsSuccessful();

        self::assertSelectorExists('[data-group="links"]');
        self::assertSelectorNotExists('input[name="Section[subtitle]"]');
        self::assertSelectorNotExists('.form-error');

        self::assertSame(TestSection::TYPE_HEADLINE, TestSection::findOne($section->id)->type);
    }

    public function testTheTypeSelectCarriesTheFingerprints(): void
    {
        $section = $this->getSectionFromFixture('section-headline');

        $this->open("/admin/cms/section/update?id=$section->id");

        self::assertSelectorExists('select[name="Section[type]"][data-fingerprint][hx-post]');
        self::assertSelectorExists('select[name="Section[type]"] option[data-fingerprint]');
    }
}
