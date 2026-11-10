<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Asset;
use Hirtz\Skeleton\Test\Traits\UpgradeMigrationTrait;
use Hirtz\Skeleton\Web\UrlManager;
use Override;
use Yii;
use yii\db\Query;

class EntryLinksMigrationTest extends TestCase
{
    use UpgradeMigrationTrait;

    use CmsFixtureTrait;

    private const string MIGRATION = 'Hirtz\\Cms\\Migrations\\M261001100000EntryLinks';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->requireUpgradeMigration('yii2-cms', self::MIGRATION);
    }

    public function testAnExactRelativeLinkBecomesAnEntryLink(): void
    {
        $id = $this->getSectionFromFixture('section-column')->id;

        $this->setCustomAttributes(Section::tableName(), $id, ['content' => implode('', [
            '<p>Ågot 東京 🚀 Mu' . "\u{308}" . 'ller</p>',
            '<p><a href="/test-1">Page</a> <a href="/test-1/post-1#team">Post</a> <a href="/test-1/">Slash</a></p>',
            '<p><a href="/test-1?x=1">Query</a> <a href="https://www.domain.localhost/test-1">Absolute</a></p>',
            '<p><a href="test-1">No slash</a> <a href="/nothing-here">Nothing</a> <a href="//test-1">Host</a></p>',
        ])]);

        $this->migrate();

        $content = (string)($this->getCustomAttributes(Section::tableName(), $id)['content'] ?? '');

        self::assertStringContainsString('<p>Ågot 東京 🚀 Mu' . "\u{308}" . 'ller</p>', $content);
        self::assertStringContainsString('<a href="entry:1">Page</a>', $content);
        self::assertStringContainsString('<a href="entry:4#team">Post</a>', $content);
        self::assertStringContainsString('<a href="entry:1">Slash</a>', $content);
        self::assertStringContainsString('<a href="/test-1?x=1">Query</a>', $content);
        self::assertStringContainsString('<a href="https://www.domain.localhost/test-1">Absolute</a>', $content);
        self::assertStringContainsString('<a href="test-1">No slash</a>', $content);
        self::assertStringContainsString('<a href="/nothing-here">Nothing</a>', $content);
        self::assertStringContainsString('<a href="//test-1">Host</a>', $content);
    }

    public function testASecondRunChangesNothing(): void
    {
        $id = $this->getSectionFromFixture('section-column')->id;
        $this->setCustomAttributes(Section::tableName(), $id, ['content' => '<a href="/test-2">Draft</a>']);

        $this->migrate();
        $first = $this->getCustomAttributes(Section::tableName(), $id);

        $this->migrate();

        self::assertSame($first, $this->getCustomAttributes(Section::tableName(), $id));
        self::assertSame('<a href="entry:2">Draft</a>', $first['content'] ?? null);
    }

    /**
     * A project's own URL attribute takes no entry link, so only the asset's `link` is a link.
     */
    public function testAnAssetLinkIsConvertedAndAProjectsUrlIsNot(): void
    {
        $this->setCustomAttributes(Asset::tableName(), 1, [
            'link' => '/test-1/post-2',
            'website' => '/test-1',
            'content' => '<a href="/test-3">Caption</a>',
        ]);

        $this->migrate();

        self::assertSame([
            'link' => 'entry:5',
            'website' => '/test-1',
            'content' => '<a href="entry:3">Caption</a>',
        ], $this->getCustomAttributes(Asset::tableName(), 1));
    }

    /**
     * Tenant 2 lives under `/de` of the shared host, so its paths carry that prefix and tenant 1's links do not
     * reach its entries.
     */
    public function testALinkMatchesOnlyItsRecordsTenantAndItsPath(): void
    {
        $this->moveToTenant(6, 2);
        $this->setCustomAttributes(Asset::tableName(), 3, ['link' => '/de/test-2/post-3']);

        $id = $this->getSectionFromFixture('section-column')->id;
        $this->setCustomAttributes(Section::tableName(), $id, ['content' => '<a href="/test-2/post-3">Elsewhere</a>']);

        $this->migrate();

        self::assertSame(['link' => 'entry:6'], $this->getCustomAttributes(Asset::tableName(), 3));
        self::assertSame(['content' => '<a href="/test-2/post-3">Elsewhere</a>'], $this->getCustomAttributes(Section::tableName(), $id));
    }

    /**
     * The link renders in the language of the text it sits in, so only a link to that language's URL is the same link.
     */
    public function testALanguagePrefixMustBeTheTextsLanguage(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $manager = Yii::$app->getUrlManager();
        self::assertInstanceOf(UrlManager::class, $manager);

        $manager->i18nUrl = true;
        $manager->languages = ['en-US' => 'en', 'de' => 'de'];

        $id = $this->getSectionFromFixture('section-column')->id;

        $this->setCustomAttributes(Section::tableName(), $id, [
            'content' => '<a href="/test-1">English</a> <a href="/de/test-1">German</a>',
            'content_de' => '<a href="/de/test-1">Deutsch</a> <a href="/test-1">Englisch</a>',
        ]);

        $this->migrate();

        self::assertSame([
            'content' => '<a href="entry:1">English</a> <a href="/de/test-1">German</a>',
            'content_de' => '<a href="entry:1">Deutsch</a> <a href="/test-1">Englisch</a>',
        ], $this->getCustomAttributes(Section::tableName(), $id));
    }

    private function moveToTenant(int $entryId, int $tenantId): void
    {
        $db = Yii::$app->getDb();

        $db->createCommand()->update(Entry::tableName(), ['tenant_id' => $tenantId], ['id' => $entryId])->execute();
        $db->createCommand()->update(Permalink::tableName(), ['tenant_id' => $tenantId], ['entry_id' => $entryId])->execute();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function setCustomAttributes(string $table, int $id, array $attributes): void
    {
        Yii::$app->getDb()->createCommand()
            ->update($table, ['custom_attributes' => $attributes], ['id' => $id])
            ->execute();
    }

    /**
     * @return array<string, mixed>
     */
    private function getCustomAttributes(string $table, int $id): array
    {
        $json = (new Query())->select('custom_attributes')->from($table)->where(['id' => $id])->scalar();
        $decoded = json_decode((string)$json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function migrate(): void
    {
        ob_start();

        try {
            $this->createUpgradeMigration('yii2-cms', self::MIGRATION, ['compact' => true])->safeUp();
        } finally {
            ob_end_clean();
        }
    }
}
