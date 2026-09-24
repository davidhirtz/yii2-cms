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
 * The media bundle owns `AssetHeader` but not the routes an asset page lives on, so the chain it walks is
 * exercised here, where an entry and a section have assets.
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
                    Yii::t('skeleton', 'COMMON_MODEL_POSITION_TOTAL', [
                        'model' => Yii::t('cms', 'COMMON_SECTION'),
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
     * The noun is the base "Asset", not the subclass's "Section asset": whatever the asset hangs on is already
     * named, either as the title or as the item right before it.
     *
     * @return array{string, string}
     */
    private function subtitleItem(string $route, int $position, int $total): array
    {
        return [$route, Yii::t('skeleton', 'COMMON_MODEL_POSITION_TOTAL', [
            'model' => Yii::t('media', 'ASSET_ASSET'),
            'position' => $position,
            'total' => $total,
        ])];
    }
    /**
     * @return list<array{string, string}> the href and the text of each subtitle item, in order. Matched rather
     *     than spelled out: an item also carries the generated `view-transition-name` its group is matched by.
     */
    private function getSubtitleItems(string $html): array
    {
        preg_match_all('~<a[^>]*class="header-subtitle-item"[^>]*>[^<]*</a>~', $html, $matches);

        return array_map(static function (string $tag): array {
            preg_match('~href="([^"]*)"~', $tag, $href);
            preg_match('~>([^<]*)<~', $tag, $text);

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
