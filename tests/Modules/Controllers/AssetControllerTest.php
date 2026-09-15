<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\File;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Both controllers are thin: they resolve and authorise the record, then hand it to the media bundle's
 * `AssetControllerTrait`.
 */
class AssetControllerTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTheEntryAssetsAreListed(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry-asset/index', ['entry' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('entry-asset/update', $html);
    }

    public function testTheIndexNeedsAnEntry(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/entry-asset/index');
    }

    public function testTheIndexOfAnUnknownEntryIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/entry-asset/index', ['entry' => 99999]);
    }

    public function testTheIndexIsForbiddenWithoutThePermission(): void
    {
        Yii::$app->getUser()->setIdentity($this->getUserFromFixture('admin'));

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/cms/entry-asset/index', ['entry' => 1]);
    }

    public function testCreateRendersTheFilePicker(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry-asset/create', ['entry' => 1]);

        self::assertIsString($html);
    }

    public function testCreateLinksAnExistingFile(): void
    {
        $this->login();

        $file = $this->getFileFromFixture('file-3');
        $count = (int)EntryAsset::find()->andWhere(['model_id' => 1])->count();

        $this->post('admin/cms/entry-asset/create', ['entry' => 1, 'file' => $file->id]);

        self::assertSame($count + 1, (int)EntryAsset::find()->andWhere(['model_id' => 1])->count());
        self::assertSame($count + 1, Entry::findOne(1)->asset_count);
    }

    public function testCreateWithAnAssetReplacesItsFileAndKeepsEverythingElse(): void
    {
        $this->login();

        $asset = EntryAsset::findOne(1);
        self::assertNotNull($asset);

        $asset->name = 'Test';
        $asset->update();

        $file = $this->getFileFromFixture('file-3');
        self::assertNotSame($file->id, $asset->file_id);

        $previousFileId = $asset->file_id;
        $previousAssetCount = $asset->file->asset_count;
        $assetCount = $file->asset_count;
        $count = (int)EntryAsset::find()->andWhere(['model_id' => 1])->count();

        $this->post('admin/cms/entry-asset/create', [
            'entry' => 1,
            'file' => $file->id,
            'asset' => $asset->id,
        ]);

        $asset = EntryAsset::findOne($asset->id);
        self::assertNotNull($asset);

        self::assertSame($count, (int)EntryAsset::find()->andWhere(['model_id' => 1])->count());
        self::assertSame($file->id, $asset->file_id);
        self::assertSame('Test', $asset->name);

        self::assertSame($assetCount + 1, File::findOne($file->id)?->asset_count);
        self::assertSame($previousAssetCount - 1, File::findOne($previousFileId)?->asset_count);
    }

    public function testTheFilePickerPostsToTheAssetWhenReplacingItsFile(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry-asset/create', ['entry' => 1, 'asset' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('asset=1', $html);
        self::assertStringContainsString('fa-exchange-alt', $html);
    }

    public function testReplacingASectionAssetThroughTheEntryControllerIsNotFound(): void
    {
        $this->login();

        $sectionAsset = SectionAsset::find()->one();
        self::assertNotNull($sectionAsset);

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/entry-asset/create', ['entry' => 1, 'asset' => $sectionAsset->id]);
    }

    public function testUpdateRendersTheAsset(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/entry-asset/update', ['id' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('name="Asset[', $html);
    }

    public function testUpdateOfAnUnknownAssetIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/entry-asset/update', ['id' => 99999]);
    }

    /**
     * The asset table is shared, so a controller only ever resolves the subclass it serves. Note that a query built
     * on a subclass loses that scope to `where()` — it has to be `andWhere()`.
     */
    public function testASectionAssetIsNotAnEntryAsset(): void
    {
        $this->login();

        $sectionAsset = SectionAsset::find()->one();
        self::assertNotNull($sectionAsset);

        $this->expectException(NotFoundHttpException::class);
        Yii::$app->runAction('admin/cms/entry-asset/update', ['id' => $sectionAsset->id]);
    }

    public function testDuplicateCopiesTheAsset(): void
    {
        $this->login();
        $count = (int)EntryAsset::find()->andWhere(['model_id' => 1])->count();

        $response = $this->post('admin/cms/entry-asset/duplicate', ['id' => 1]);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame($count + 1, (int)EntryAsset::find()->andWhere(['model_id' => 1])->count());
    }

    public function testDeleteRemovesTheAssetAndFixesTheCount(): void
    {
        $this->login();
        $count = (int)EntryAsset::find()->andWhere(['model_id' => 1])->count();

        $this->post('admin/cms/entry-asset/delete', ['id' => 1]);

        self::assertNull(EntryAsset::findOne(1));
        self::assertSame($count - 1, Entry::findOne(1)->asset_count);
    }

    public function testDeleteRefusesAGetRequest(): void
    {
        $this->login();

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/cms/entry-asset/delete', ['id' => 1]);
    }

    public function testOrderRewritesThePositions(): void
    {
        $this->login();

        $ids = EntryAsset::find()
            ->andWhere(['model_id' => 1])
            ->orderBy(['position' => SORT_ASC])
            ->column();

        self::assertCount(2, $ids);

        $html = $this->post('admin/cms/entry-asset/order', ['entry' => 1], [
            'asset' => array_reverse($ids),
        ]);

        self::assertIsString($html);
        self::assertLessThan(
            EntryAsset::findOne($ids[0])->position,
            EntryAsset::findOne($ids[1])->position
        );
    }

    public function testTheSectionAssetsAreListed(): void
    {
        $this->login();

        $html = Yii::$app->runAction('admin/cms/section-asset/index', ['section' => 1]);

        self::assertIsString($html);
        self::assertStringContainsString('section-asset/update', $html);
    }

    public function testASectionAssetIsUpdatedThroughItsOwnController(): void
    {
        $this->login();
        $asset = SectionAsset::find()->one();

        $html = Yii::$app->runAction('admin/cms/section-asset/update', ['id' => $asset->id]);

        self::assertIsString($html);
        self::assertStringContainsString('name="Asset[', $html);
    }

    /**
     * @param array<string, mixed> $bodyParams
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $params = [], array $bodyParams = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = Yii::$app->getRequest();
        $request->setBodyParams([...$bodyParams, $request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');

        $permission = Yii::$app->getAuthManager()->getPermission(Entry::AUTH_ENTRY);
        Yii::$app->getAuthManager()->assign($permission, $user->id);

        Yii::$app->getUser()->setIdentity($user);

        return $user;
    }

    /**
     * `CmsFixtureTrait` declares the user fixture but no accessor for it: adding one would collide with
     * `UserFixtureTrait` for the tests that use both.
     */
    private function getUserFromFixture(string $key): User
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');

        return User::findOne($fixture->data[$key]['id']);
    }
}
