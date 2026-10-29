<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules\Admin\Widgets\Navs;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Fixtures\UserFixture;
use Yii;

/**
 * The media bundle owns `AssetHeader` but not the routes an asset page lives on, so its chain is exercised here.
 */
class AssetHeaderTest extends TestCase
{
    use CmsFixtureTrait;

    public function testTheEntryAssetPageIsTitledWithTheEntry(): void
    {
        $this->login();
        $asset = $this->getAssetFromFixture('entry-asset');

        $html = Yii::$app->runAction('admin/cms/entry-asset/update', ['id' => $asset->id]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<h1><a href="/admin/cms/entry/update?id=' . $asset->model_id . '">'
            . $asset->model->getAdminName() . '</a></h1>',
            $html,
        );
        self::assertSame(
            [$this->subtitleItem('/admin/cms/entry-asset/update?id=' . $asset->id, 1, 2)],
            $this->getSubtitleItems($html),
        );
    }

    public function testTheSectionAssetSubtitleHoldsTheSectionAndTheAsset(): void
    {
        $this->login();
        $asset = $this->getAssetFromFixture('section-image-1');
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section-asset/update', ['id' => $asset->id]);

        self::assertIsString($html);
        self::assertStringContainsString(
            '<h1><a href="/admin/cms/entry/update?id=' . $section->entry_id . '">'
            . $section->entry->getAdminName() . '</a></h1>',
            $html,
        );
        self::assertSame(
            [
                [
                    '/admin/cms/section/update?id=' . $section->id,
                    Yii::t('skeleton', 'MODEL_POSITION_FORMAT', [
                        'model' => Yii::t('cms', 'SECTION_SINGULAR'),
                        'position' => 1,
                        'total' => 5,
                    ]),
                ],
                $this->subtitleItem('/admin/cms/section-asset/update?id=' . $asset->id, 1, 4),
            ],
            $this->getSubtitleItems($html),
        );
    }

    /**
     * An asset has no frontend URL of its own, so the subheading links to the record it hangs on.
     */
    public function testTheSectionAssetSubheadingIsTheSectionsFrontendUrl(): void
    {
        $this->login();
        $asset = $this->getAssetFromFixture('section-image-1');
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section-asset/update', ['id' => $asset->id]);

        self::assertIsString($html);
        self::assertStringContainsString('#' . $section->getHtmlId() . '" target="_blank"', $html);
    }

    public function testTheSectionAssetPageStillRendersTheSectionSubmenu(): void
    {
        $this->login();
        $asset = $this->getAssetFromFixture('section-image-1');
        $section = $this->getSectionFromFixture('section-headline');

        $html = Yii::$app->runAction('admin/cms/section-asset/update', ['id' => $asset->id]);

        self::assertIsString($html);
        self::assertStringContainsString('/admin/cms/section/update?id=' . $section->id, $html);
        self::assertStringContainsString('/admin/cms/section-asset/index?section=' . $section->id, $html);
    }

    /**
     * The noun is the base "Asset", since whatever the asset hangs on is already named.
     *
     * @return array{string, string}
     */
    private function subtitleItem(string $route, int $position, int $total): array
    {
        return [$route, Yii::t('skeleton', 'MODEL_POSITION_FORMAT', [
            'model' => Yii::t('media', 'ASSET_SINGULAR'),
            'position' => $position,
            'total' => $total,
        ])];
    }
    /**
     * Matched rather than spelled out, since an item also carries a generated `view-transition-name`.
     *
     * @return list<array{string, string}>
     */
    private function getSubtitleItems(string $html): array
    {
        preg_match_all('~<span[^>]*class="header-subtitle-item"[^>]*><a[^>]*>[^<]*</a></span>~', $html, $matches);

        return array_map(static function (string $tag): array {
            preg_match('~href="([^"]*)"~', $tag, $href);
            preg_match('~([^<>]*)</a>~', $tag, $text);

            return [$href[1] ?? '', $text[1] ?? ''];
        }, $matches[0]);
    }

    private function login(): User
    {
        $user = $this->getUserFromFixture('admin');

        $auth = Yii::$app->getAuthManager();
        $auth->assign($auth->getPermission(Entry::AUTH_ENTRY), $user->id);

        $this->getWebUser()->setIdentity($user);

        return $user;
    }

    /**
     * `CmsFixtureTrait` declares no accessor for the user fixture, since one would collide with `UserFixtureTrait`.
     */
    private function getUserFromFixture(string $key): User
    {
        /** @var UserFixture $fixture */
        $fixture = $this->getFixture('user');

        return User::findOne($fixture->data[$key]['id']);
    }
}
