<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\Models\TestEntry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Asset;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Yii;
use yii\web\MethodNotAllowedHttpException;

class AssetDuplicateControllerTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTheUpdatePageLeadsToThePicker(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry-asset/update', ['id' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('entry-asset/entries?id=1', $html);
    }

    public function testTheEntryPickerOffersEveryEntryButTheAssetsOwn(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry-asset/entries', ['id' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('entry-asset/duplicate?id=1&amp;entry=2', $html);
        self::assertStringNotContainsString('entry-asset/duplicate?id=1&amp;entry=1&', $html);
        self::assertStringNotContainsString('entry-asset/duplicate?id=1&amp;entry=1"', $html);
    }

    public function testDuplicateCopiesTheAssetToAnotherEntryAsADraft(): void
    {
        $this->login();

        $asset = EntryAsset::findOne(1);
        self::assertNotNull($asset);

        $asset->alt_text = 'Copied';
        $asset->update();

        $count = (int)Entry::findOne(2)?->asset_count;

        $this->post('admin/cms/entry-asset/duplicate', ['id' => 1, 'entry' => 2]);

        $duplicate = EntryAsset::findOne(['model_id' => 2, 'file_id' => $asset->file_id]);

        self::assertNotNull($duplicate);
        self::assertSame(Asset::STATUS_DRAFT, $duplicate->status);
        self::assertSame('Copied', $duplicate->alt_text);
        self::assertSame($count + 1, Entry::findOne(2)?->asset_count);
        self::assertNotNull(EntryAsset::findOne(1));
    }

    /**
     * A model holds a file once, so a copy onto the asset's own entry fails validation.
     */
    public function testDuplicateToTheSameEntryIsRefused(): void
    {
        $this->login();
        $count = (int)EntryAsset::find()->andWhere(['model_id' => 1])->count();

        $this->post('admin/cms/entry-asset/duplicate', ['id' => 1, 'entry' => 1]);

        self::assertSame($count, (int)EntryAsset::find()->andWhere(['model_id' => 1])->count());
        self::assertNotEmpty($this->getWebSession()->getFlash('danger'));
    }

    public function testDuplicateRefusesAGetRequest(): void
    {
        $this->login();

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/cms/entry-asset/duplicate', ['id' => 1, 'entry' => 2]);
    }

    public function testTheSectionPickerListsTheSectionsOfTheEntry(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/section-asset/sections', ['id' => 4]);

        self::assertIsString($html);
        self::assertStringContainsString('section-asset/duplicate?id=4&amp;section=', $html);
        self::assertStringNotContainsString('section-asset/duplicate?id=4&amp;section=1"', $html);
        self::assertStringNotContainsString('data-sort-url', $html);
    }

    public function testDuplicateCopiesTheAssetToAnotherSection(): void
    {
        $this->login();

        $target = Section::find()
            ->andWhere(['entry_id' => 1])
            ->andWhere(['!=', 'id', 1])
            ->all();

        $target = array_values(array_filter($target, static fn (Section $section): bool => $section->allowsAssets()))[0]
            ?? self::fail('No other section of entry 1 allows assets.');

        $this->post('admin/cms/section-asset/duplicate', ['id' => 4, 'section' => $target->id]);

        self::assertNotNull(SectionAsset::findOne(['model_id' => $target->id, 'file_id' => 3]));
    }

    public function testDuplicateCopiesTheAssetToAnotherBlock(): void
    {
        $module = TestEntry::getModule();
        $module->enableBlocks = true;
        $module->enableBlockAssets = true;

        $this->login(Block::AUTH_BLOCK);

        $source = $this->createBlock('Source');
        $target = $this->createBlock('Target');

        $asset = BlockAsset::create();
        $asset->populateModelRelation($source);
        $asset->file_id = 1;
        self::assertTrue($asset->insert(), print_r($asset->getErrors(), true));

        $html = Yii::$app->runAction('admin/cms/block-asset/blocks', ['id' => $asset->id]);

        self::assertIsString($html);
        self::assertStringContainsString("block-asset/duplicate?id=$asset->id&amp;block=$target->id", $html);

        $this->post('admin/cms/block-asset/duplicate', ['id' => $asset->id, 'block' => $target->id]);

        self::assertNotNull(BlockAsset::findOne(['model_id' => $target->id, 'file_id' => 1]));
        self::assertSame(1, Block::findOne($target->id)?->asset_count);
    }

    private function createBlock(string $name): Block
    {
        $block = Block::create();
        $block->name = $name;

        self::assertTrue($block->insert(), print_r($block->getErrors(), true));

        return $block;
    }

    /**
     * @param array<string, mixed> $bodyParams
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $params = [], array $bodyParams = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->setBodyParams([...$bodyParams, $request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    private function login(string $permission = Entry::AUTH_ENTRY): void
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');
        $user = User::findOne($fixture->data['admin']['id']);

        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getPermission($permission), $user->id);

        $this->getWebUser()->setIdentity($user);
    }
}
