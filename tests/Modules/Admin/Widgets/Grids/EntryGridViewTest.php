<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Grids;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Web\UrlManager;
use Yii;

/**
 * The name column composes the entry link with the frontend URL and the category buttons, both of which a project
 * turns off — and a grid rendering neither used to hand back the link itself rather than a string.
 */
class EntryGridViewTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheIndexRendersWithoutTheUrlAndTheCategories(): void
    {
        Yii::$container->set(EntryGridView::class, GridViewWithoutUrl::class);

        $this->login();
        $this->createEntry('Needle', 'needle');

        $html = Yii::$app->runAction('admin/cms/entry/index');

        self::assertIsString($html);
        self::assertStringContainsString('Needle', $html);
    }

    public function testTheIndexRendersTheUrlAndTheCategories(): void
    {
        Entry::getModule()->enableCategories = true;

        $this->login();
        $this->createEntry('Needle', 'needle');

        $html = Yii::$app->runAction('admin/cms/entry/index');

        self::assertIsString($html);
        self::assertStringContainsString('Needle', $html);
    }

    /**
     * The request's tenant has no entries, another one does — and the dropdown is the only way to it.
     */
    public function testAnEmptyTenantKeepsTheTenantDropdown(): void
    {
        $empty = $this->createTenant('Empty Tenant', 'https://www.empty-domain.localhost');
        $other = $this->createTenant('Other Tenant', 'https://www.other-domain.localhost');

        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);
        $manager->setTenant($empty);

        $this->login();
        $this->createEntry('Needle', 'needle', $other);

        $html = Yii::$app->runAction('admin/cms/entry/index');

        self::assertIsString($html);
        self::assertStringNotContainsString('Needle', $html);
        self::assertStringContainsString('tenant=' . $other->id, $html);
    }

    private function createTenant(string $name, string $url): Tenant
    {
        $tenant = Tenant::create();
        $tenant->loadDefaultValues();
        $tenant->name = $name;
        $tenant->language = Yii::$app->sourceLanguage;
        $tenant->url = $url;

        self::assertTrue($tenant->save(), implode(' ', $tenant->getErrorSummary(true)));

        return $tenant;
    }

    private function createEntry(string $name, string $slug, ?Tenant $tenant = null): Entry
    {
        $entry = Entry::create();
        $entry->loadDefaultValues();
        $entry->status = Entry::STATUS_ENABLED;
        $entry->type = Entry::TYPE_DEFAULT;
        $entry->name = $name;
        $entry->slug = $slug;

        if ($tenant) {
            $entry->populateTenantRelation($tenant);
        }

        self::assertTrue($entry->insert(), print_r($entry->getErrors(), true));

        return $entry;
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignPermission($user->id, Entry::AUTH_ENTRY);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}

/**
 * @extends EntryGridView<Entry>
 */
class GridViewWithoutUrl extends EntryGridView
{
    protected bool $showUrl = false;
    protected ?bool $showCategories = false;
}
