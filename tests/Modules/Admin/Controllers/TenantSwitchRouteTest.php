<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Controllers;

use Hirtz\Cms\Test\TestCase;
use Hirtz\Tenant\Modules\Admin\Controllers\TenantSwitchInterface;
use Yii;

final class TenantSwitchRouteTest extends TestCase
{
    public function testTheEntryListKeepsItsFiltersButTheParent(): void
    {
        $this->getWebRequest()->setQueryParams(['type' => '2', 'q' => 'Å東京', 'parent' => '5', 'tenant' => '2']);

        self::assertSame(
            ['/admin/cms/entry/index', 'type' => '2', 'q' => 'Å東京'],
            $this->getSwitchRoute('admin/cms/entry/index'),
        );

        self::assertSame(
            ['/admin/cms/entry/create', 'type' => '2', 'q' => 'Å東京'],
            $this->getSwitchRoute('admin/cms/entry/create'),
        );
    }

    public function testAnEntryLeadsToTheList(): void
    {
        $this->getWebRequest()->setQueryParams(['id' => '5']);
        self::assertSame(['/admin/cms/entry/index'], $this->getSwitchRoute('admin/cms/entry/update'));
    }

    public function testWhatBelongsToAnEntryLeadsToTheList(): void
    {
        foreach (['entry-asset/update', 'entry-category/index', 'section/update', 'section-asset/index', 'section-entry/index'] as $route) {
            self::assertSame(['/admin/cms/entry/index'], $this->getSwitchRoute("admin/cms/$route"), $route);
        }
    }

    public function testABlockIsNoTenantsAndKeepsItsPage(): void
    {
        $result = Yii::$app->createController('admin/cms/block/update');

        self::assertNotFalse($result);
        self::assertNotInstanceOf(TenantSwitchInterface::class, $result[0]);
    }

    /**
     * @return array<int|string, mixed>|null
     */
    private function getSwitchRoute(string $route): ?array
    {
        $result = Yii::$app->createController($route);
        self::assertNotFalse($result);

        [$controller, $actionId] = $result;
        self::assertInstanceOf(TenantSwitchInterface::class, $controller);

        $action = $controller->createAction($actionId) ?? self::fail("No action for $route.");
        return $controller->getTenantSwitchRoute($action);
    }
}
