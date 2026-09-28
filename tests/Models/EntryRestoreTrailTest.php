<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Actions\RestoreTrail;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\TrailGridView;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

/**
 * An update's previous values go back through the record's own save: cast, validated, and trailed themselves.
 */
class EntryRestoreTrailTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheValuesBeforeAnUpdateAreRestored(): void
    {
        $entry = $this->createEntry();
        $original = $entry->publish_date;

        $entry->name = 'Renamed';
        $entry->publish_date = new DateTime('+1 day');
        self::assertTrue($entry->save());

        $trail = $this->findLatestUpdate($entry);
        $action = new RestoreTrail($trail);

        self::assertTrue($action->run(), print_r($action->getModel()?->getErrors(), true));
        self::assertEqualsCanonicalizing(['name', 'publish_date'], $action->getRestored());

        $restored = Entry::findOne($entry->id);
        self::assertSame('Original', $restored?->name);
        self::assertInstanceOf(DateTime::class, $restored->publish_date);
        self::assertSame($original?->getTimestamp(), $restored->publish_date->getTimestamp());

        self::assertNotSame($trail->id, $this->findLatestUpdate($entry)->id, 'The restore is trailed itself.');
    }

    public function testAnAttributeTheRecordNoLongerAcceptsIsSkipped(): void
    {
        $entry = $this->createEntry();
        $entry->name = 'Renamed';
        self::assertTrue($entry->save());

        $trail = $this->findLatestUpdate($entry);
        $trail->data = [...(array)$trail->data, 'dropped_column' => ['old', 'new']];

        $action = new RestoreTrail($trail);

        self::assertTrue($action->run());
        self::assertSame(['dropped_column'], $action->getSkipped());
        self::assertSame('Original', Entry::findOne($entry->id)?->name);
    }

    public function testRestoringNeedsTheRecordsPermission(): void
    {
        $entry = $this->createEntry();
        $entry->name = 'Renamed';
        self::assertTrue($entry->save());

        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Trail::AUTH_TRAIL_INDEX);
        $this->getWebUser()->setIdentity($user);

        $trail = $this->findLatestUpdate($entry);
        self::assertStringNotContainsString('trail/restore', $this->renderGrid($entry));

        try {
            $this->post('admin/trail/restore', ['id' => $trail->id]);
            self::fail('The restore went through without the entry permission.');
        } catch (ForbiddenHttpException) {
        }

        $this->assignPermission($user->id, Entry::AUTH_ENTRY);
        $this->getWebUser()->setIdentity($user);

        self::assertStringContainsString('trail/restore?id=' . $trail->id, $this->renderGrid($entry));

        $response = $this->post('admin/trail/restore', ['id' => $trail->id]);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('Original', Entry::findOne($entry->id)?->name);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testRestoringIsAPost(): void
    {
        $entry = $this->createEntry();
        $entry->name = 'Renamed';
        self::assertTrue($entry->save());

        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Trail::AUTH_TRAIL_INDEX);
        $this->getWebUser()->setIdentity($user);

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/trail/restore', ['id' => $this->findLatestUpdate($entry)->id]);
    }

    private function renderGrid(Entry $entry): string
    {
        $provider = Yii::$container->get(TrailActiveDataProvider::class, config: [
            'model' => $entry->getTrailBehavior()->modelClass,
            'modelId' => (string)$entry->id,
        ]);

        return (string)TrailGridView::make()->provider($provider);
    }

    private function findLatestUpdate(Entry $entry): Trail
    {
        $trail = Trail::find()
            ->where([
                'model_class' => $entry->getTrailBehavior()->modelClass,
                'model_id' => (string)$entry->id,
                'type' => Trail::TYPE_UPDATE,
            ])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        self::assertInstanceOf(Trail::class, $trail);

        return $trail;
    }

    private function createEntry(): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = 'Original';
        $entry->slug = 'original';
        $entry->publish_date = new DateTime('-1 day');

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $params = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }
}
