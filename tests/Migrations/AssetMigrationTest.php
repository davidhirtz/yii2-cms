<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Migrations;

use Hirtz\Cms\Migrations\M260912110000Assets;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Test\Fixtures\Traits\CmsFixtureTrait;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\File;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Translation;
use Override;
use Yii;
use yii\db\Expression;
use yii\db\JsonExpression;

/**
 * `cms_asset` is kept, so the copy can be replayed against it. The migration asserts its own result and throws, so
 * a silent failure here would already have rolled back — these tests check what it produced.
 *
 * Adding the legacy file count column commits the test transaction, so everything is cleaned up by hand.
 */
class AssetMigrationTest extends TestCase
{
    use CmsFixtureTrait {
        fixtures as cmsFixtures;
    }

    private const string LEGACY_CLASS = 'Hirtz\Cms\Models\Asset';
    private const string LEGACY_FILE_COUNT_COLUMN = 'cms_asset_count';

    private const int ENTRY_ASSET_ID = 101;
    private const int SECTION_ASSET_ID = 102;
    private const int SECOND_ENTRY_ASSET_ID = 103;

    /**
     * Never in `cms_asset`: the asset was deleted before the migration and only its trail rows remain.
     */
    private const int DELETED_ASSET_ID = 199;

    /**
     * The migration asserts that `asset` holds exactly what it copied, so the fixture must not fill it first.
     */
    #[Override]
    public function fixtures(): array
    {
        $fixtures = $this->cmsFixtures();
        unset($fixtures['asset']);

        return $fixtures;
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getDb()->createCommand()
            ->addColumn(File::tableName(), self::LEGACY_FILE_COUNT_COLUMN, 'smallint NOT NULL DEFAULT 0')
            ->execute();
    }

    #[Override]
    protected function tearDown(): void
    {
        $db = Yii::$app->getDb();

        if ($db->getTableSchema(File::tableName(), true)->getColumn(self::LEGACY_FILE_COUNT_COLUMN)) {
            $db->createCommand()
                ->dropColumn(File::tableName(), self::LEGACY_FILE_COUNT_COLUMN)
                ->execute();
        }

        $db->createCommand()->delete('{{%cms_asset}}')->execute();
        $db->createCommand()->delete(Asset::tableName())->execute();

        Trail::deleteAll(['model_class' => [self::LEGACY_CLASS, EntryAsset::class, SectionAsset::class, Entry::class]]);
        Translation::deleteAll(['model_class' => self::LEGACY_CLASS]);

        parent::tearDown();
    }

    public function testTheAssetsLandOnTheirSubclasses(): void
    {
        $this->migrate();

        $asset = Asset::findOne(self::ENTRY_ASSET_ID);

        self::assertInstanceOf(EntryAsset::class, $asset);
        self::assertSame(Entry::class, $asset->model_class);
        self::assertSame(1, $asset->model_id);
        self::assertSame(1, $asset->position);

        $asset = Asset::findOne(self::SECTION_ASSET_ID);

        self::assertInstanceOf(SectionAsset::class, $asset);
        self::assertSame(Section::class, $asset->model_class);
        self::assertSame(1, $asset->model_id);
    }

    public function testTheTextColumnsBecomeCustomAttributes(): void
    {
        $this->migrate();

        $values = Asset::findOne(self::ENTRY_ASSET_ID)->getAttribute('custom_attributes');

        self::assertSame([
            'name' => 'Entry asset',
            'content' => '<p>Caption</p>',
            'alt_text' => 'Alt EN',
            'link' => 'https://example.com',
        ], $values);
    }

    /**
     * An empty column leaves no key at all, rather than an explicit `null` that would read as "set to null".
     */
    public function testAnEmptyColumnLeavesNoKey(): void
    {
        $this->migrate();

        $values = Asset::findOne(self::SECOND_ENTRY_ASSET_ID)->getAttribute('custom_attributes');

        self::assertSame([], $values);
    }

    public function testTheTranslationsLandInTheJson(): void
    {
        $this->migrate();

        $values = Asset::findOne(self::SECTION_ASSET_ID)->getAttribute('custom_attributes');

        self::assertSame(['name_de' => 'Name DE'], $values);
    }

    public function testTheTrailRowsNameTheSubclass(): void
    {
        $this->migrate();

        self::assertSame(EntryAsset::class, $this->findTrail(self::ENTRY_ASSET_ID)->model_class);
        self::assertSame(SectionAsset::class, $this->findTrail(self::SECTION_ASSET_ID)->model_class);

        self::assertSame(0, (int)Trail::find()->where(['model_class' => self::LEGACY_CLASS])->count());
    }

    /**
     * The asset was deleted before the migration, so only its `TYPE_DELETE` row says which kind it was.
     */
    public function testADeletedAssetIsResolvedFromItsTrailData(): void
    {
        $this->migrate();

        self::assertSame(SectionAsset::class, $this->findTrail(self::DELETED_ASSET_ID)->model_class);
    }

    public function testAChildTrailRowStillResolvesItsAsset(): void
    {
        $this->migrate();

        $trail = Trail::find()
            ->where(['model_class' => Entry::class, 'type' => Trail::TYPE_CHILD_UPDATE])
            ->one();

        self::assertSame(EntryAsset::class, $trail->data['model_class']);
        self::assertInstanceOf(EntryAsset::class, $trail->getDataModelRecord());
    }

    public function testTheFileCountsFold(): void
    {
        $this->migrate();

        self::assertSame(2, $this->getFileFromFixture('file-1')->asset_count);
        self::assertSame(1, $this->getFileFromFixture('file-2')->asset_count);
    }

    protected function migrate(): void
    {
        $this->seed();

        ob_start();
        (new M260912110000Assets())->up();
        ob_end_clean();
    }

    protected function seed(): void
    {
        $db = Yii::$app->getDb();
        $now = new Expression('UTC_TIMESTAMP()');

        $db->createCommand()->batchInsert('{{%cms_asset}}', [
            'id',
            'status',
            'type',
            'entry_id',
            'section_id',
            'file_id',
            'position',
            'name',
            'content',
            'alt_text',
            'link',
            'embed_url',
            'created_at',
        ], [
            [self::ENTRY_ASSET_ID, Asset::STATUS_ENABLED, Asset::TYPE_DEFAULT, 1, null, 1, 1,
                'Entry asset', '<p>Caption</p>', 'Alt EN', 'https://example.com', '', $now],
            [self::SECTION_ASSET_ID, Asset::STATUS_ENABLED, Asset::TYPE_DEFAULT, 1, 1, 1, 1,
                '', '', '', '', '', $now],
            [self::SECOND_ENTRY_ASSET_ID, Asset::STATUS_ENABLED, Asset::TYPE_DEFAULT, 1, null, 2, 2,
                '', '', '', '', '', $now],
        ])->execute();

        $files = $db->quoteTableName($db->getSchema()->getRawTableName(File::tableName()));
        $legacy = $db->quoteTableName($db->getSchema()->getRawTableName('{{%cms_asset}}'));
        $column = $db->quoteColumnName(self::LEGACY_FILE_COUNT_COLUMN);

        $db->createCommand("
            UPDATE $files f SET f.$column = (SELECT COUNT(*) FROM $legacy c WHERE c.file_id = f.id)
        ")->execute();

        $db->createCommand()->insert(Translation::tableName(), [
            'model_class' => self::LEGACY_CLASS,
            'model_id' => self::SECTION_ASSET_ID,
            'language' => 'de',
            'attribute' => 'name',
            'value' => 'Name DE',
        ])->execute();

        // `batchInsert()` does not encode an array, and re-encodes a string, so the values are wrapped by hand.
        $db->createCommand()->batchInsert(Trail::tableName(), ['type', 'model_class', 'model_id', 'data', 'created_at'], [
            [Trail::TYPE_CREATE, self::LEGACY_CLASS, (string)self::ENTRY_ASSET_ID,
                new JsonExpression(['name' => 'Entry asset']), $now],
            [Trail::TYPE_CREATE, self::LEGACY_CLASS, (string)self::SECTION_ASSET_ID,
                new JsonExpression(['name' => '']), $now],
            [Trail::TYPE_CREATE, self::LEGACY_CLASS, (string)self::SECOND_ENTRY_ASSET_ID,
                new JsonExpression(['name' => '']), $now],
            [Trail::TYPE_DELETE, self::LEGACY_CLASS, (string)self::DELETED_ASSET_ID,
                new JsonExpression(['section_id' => 1]), $now],
            [Trail::TYPE_CHILD_UPDATE, Entry::class, '1',
                new JsonExpression(['model_class' => self::LEGACY_CLASS, 'model_id' => (string)self::ENTRY_ASSET_ID]), $now],
        ])->execute();
    }

    protected function findTrail(int $assetId): Trail
    {
        return Trail::find()
            ->where(['model_id' => (string)$assetId])
            ->andWhere(['in', 'model_class', [self::LEGACY_CLASS, EntryAsset::class, SectionAsset::class]])
            ->one();
    }
}
