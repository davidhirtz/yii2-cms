<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Mcp;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Models\Search;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Models\User;
use Override;
use Yii;

class McpResourcesTest extends TestCase
{
    use CmsFixtureTrait;

    private User $owner;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        // The query layer reads `i18nAttributes` off the shared instance(), so it has to be configured.
        Yii::$container->set(Entry::class, ['i18nAttributes' => ['name', 'slug']]);
        Entry::instance(true);

        $this->owner = User::find()->andWhere(['is_owner' => true])->one() ?? self::fail('No owner');
        $this->getWebUser()->setIdentity($this->owner);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Entry::class);
        Entry::instance(true);

        parent::tearDown();
    }

    public function testTheResourcesAreRegistered(): void
    {
        $names = array_column($this->callTool('list_resources')['resources'] ?? [], 'name');

        self::assertSame(['redirect', 'entry', 'section', 'category', 'block'], array_slice($names, 0, 5));
    }

    public function testCreatingAnEntryGoesThroughTheModelLayer(): void
    {
        $result = $this->callTool('create_record', [
            'resource' => 'entry',
            'attributes' => [
                'name' => 'Åsa’s Mu' . "\u{308}" . 'ller Story 🎉',
                'name_de' => 'Müllers Geschichte – Привет',
                'slug' => 'asa-story',
                'slug_de' => 'muellers-geschichte',
                'tenant_id' => 1,
            ],
        ]);

        $id = $result['id'] ?? self::fail('No id');

        self::assertSame(['Müllers Geschichte – Привет'], Translation::find()
            ->select('value')
            ->where(['model_class' => Entry::class, 'model_id' => $id, 'attribute' => 'name', 'language' => 'de'])
            ->column());

        self::assertTrue(Search::find()->where(['model_class' => Entry::class, 'model_id' => $id])->exists());

        $trail = Trail::find()->where(['model_class' => Entry::class, 'model_id' => (string)$id, 'type' => Trail::TYPE_CREATE])->one();
        self::assertSame($this->owner->id, $trail?->user_id);
    }

    public function testASectionIsCreatedUnderAnEntry(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $entry->updateAttributes(['updated_at' => new DateTime('2020-01-01 00:00:00')]);

        $count = (int)$entry->getSections()->count();

        $result = $this->callTool('create_record', [
            'resource' => 'section',
            'parent' => $entry->id,
            'attributes' => ['name' => 'Neue Sektion', 'name_de' => 'Ąb 東京'],
        ]);

        $section = Section::findOne($result['id'] ?? 0) ?? self::fail('Not created');
        $entry->refresh();

        self::assertSame($entry->id, $section->entry_id);
        self::assertSame($count + 1, $entry->section_count);
        self::assertSame($count + 1, $section->position);
        self::assertGreaterThan('2020-01-01 00:00:00', (string)$entry->updated_at);
    }

    public function testASectionNeedsItsEntry(): void
    {
        $result = $this->callTool('create_record', ['resource' => 'section', 'attributes' => ['name' => 'x']], true);
        self::assertSame('A section belongs to a parent: pass "parent".', $result['error'] ?? null);

        $result = $this->callTool('create_record', ['resource' => 'section', 'parent' => 999999, 'attributes' => ['name' => 'x']], true);
        self::assertSame('Parent not found: 999999', $result['error'] ?? null);
    }

    public function testReorderingSectionsUsesReorderSections(): void
    {
        $entry = $this->getEntryFromFixture('page-enabled');
        $ids = $this->getSectionIds($entry);
        $reversed = array_reverse($ids);

        $result = $this->callTool('reorder', ['resource' => 'section', 'parent' => $entry->id, 'ids' => $reversed]);

        self::assertTrue($result['reordered'] ?? null);
        self::assertSame($reversed, $this->getSectionIds($entry));
        self::assertTrue(Trail::find()->where([
            'model_class' => Entry::class,
            'model_id' => (string)$entry->id,
            'type' => Trail::TYPE_ORDER,
        ])->exists());
    }

    /**
     * @return list<int>
     */
    private function getSectionIds(Entry $entry): array
    {
        return array_values(array_map(intval(...), $entry->getSections()->select('id')->orderBy(['position' => SORT_ASC])->column()));
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    private function callTool(string $name, array $arguments = [], bool $isError = false): array
    {
        $response = Mcp::getComponent()->createServer()->handle([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ]);

        $result = $response['result'] ?? self::fail('No result: ' . json_encode($response));

        self::assertIsArray($result);
        self::assertSame($isError, $result['isError'] ?? null, json_encode($result['structuredContent'] ?? null, JSON_UNESCAPED_UNICODE) ?: '');

        /** @var array<string, mixed> */
        return $result['structuredContent'];
    }
}
