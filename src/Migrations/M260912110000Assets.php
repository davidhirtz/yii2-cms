<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\File;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Translation;
use RuntimeException;
use Yii;
use yii\db\Migration;
use yii\db\Query;

/**
 * Copies `cms_asset` into the polymorphic `asset` table. The source table is kept as the validation
 * reference; a later migration drops it once every project is upgraded.
 *
 * @noinspection PhpUnused
 */
class M260912110000Assets extends Migration
{
    use MigrationTrait;

    private const string LEGACY_TABLE = '{{%cms_asset}}';

    /**
     * @var list<string> the columns that become custom attributes, in the order they are written
     */
    private array $textColumns = ['name', 'content', 'alt_text', 'link', 'embed_url'];

    /**
     * @var array<string, list<string>> the (attribute => languages) pairs the translation copy saw
     */
    private array $copiedTranslations = [];

    public function safeUp(): void
    {
        if (!$this->getDb()->getTableSchema(self::LEGACY_TABLE, true)) {
            echo "    > cms_asset is gone, nothing to copy\n";
            return;
        }

        $this->copyAssets();
        $this->copyTranslatedColumns();
        $this->copyTranslations();
        $this->rewriteTrail();
        $this->foldFileCounts();
        $this->assert();
    }

    public function safeDown(): bool
    {
        echo "    > the asset copy cannot be reverted, restore a dump instead\n";
        return false;
    }

    protected function copyAssets(): void
    {
        $db = $this->getDb();
        $legacy = $this->getQuotedTableName(self::LEGACY_TABLE);
        $assets = $this->getQuotedTableName(Asset::tableName());

        $entry = $db->quoteValue(Entry::class);
        $section = $db->quoteValue(Section::class);

        $json = [];

        foreach ($this->textColumns as $column) {
            $json[] = $db->quoteValue($column) . ", NULLIF([[$column]], '')";
        }

        $json = implode(', ', $json);

        $this->execute("
            INSERT INTO $assets ([[id]], [[status]], [[type]], [[model_class]], [[model_id]], [[file_id]],
                [[position]], [[custom_attributes]], [[updated_by_user_id]], [[updated_at]], [[created_at]])
            SELECT [[id]], [[status]], [[type]],
                IF([[section_id]] IS NULL, $entry, $section), COALESCE([[section_id]], [[entry_id]]),
                [[file_id]], [[position]],
                JSON_MERGE_PATCH(COALESCE([[custom_attributes]], '{}'), JSON_OBJECT($json)),
                [[updated_by_user_id]], [[updated_at]], [[created_at]]
            FROM $legacy
        ");

        $count = (new Query())->from(self::LEGACY_TABLE)->count();
        echo "    > copied $count assets\n";
    }

    /**
     * A project that never ran the asset half of `M260910110000Translations` still has its `_xx` columns; the JSON
     * key is the column name either way.
     */
    protected function copyTranslatedColumns(): void
    {
        $columns = $this->getDb()->getTableSchema(self::LEGACY_TABLE, true)->getColumnNames();
        $assets = $this->getQuotedTableName(Asset::tableName());
        $legacy = $this->getQuotedTableName(self::LEGACY_TABLE);
        $copied = [];

        foreach ($columns as $column) {
            foreach ($this->textColumns as $attribute) {
                if ($column !== $attribute && str_starts_with($column, $attribute . '_')) {
                    $copied[] = $column;
                }
            }
        }

        foreach ($copied as $column) {
            $this->execute("
                UPDATE $assets [[a]] JOIN $legacy [[c]] ON [[c]].[[id]] = [[a]].[[id]]
                SET [[a]].[[custom_attributes]] = JSON_SET(COALESCE([[a]].[[custom_attributes]], '{}'),
                    {$this->getDb()->quoteValue('$."' . $column . '"')}, [[c]].[[$column]])
                WHERE [[c]].[[$column]] IS NOT NULL AND [[c]].[[$column]] != ''
            ");
        }

        $count = count($copied);
        echo "    > copied $count translated columns\n";
    }

    /**
     * The JSON key carries the language suffix, which only {@see \Hirtz\Skeleton\I18n\I18N} can spell.
     */
    protected function copyTranslations(): void
    {
        $i18n = Yii::$app->getI18n();
        $count = 0;

        $query = (new Query())
            ->select(['model_id', 'language', 'attribute', 'value'])
            ->from(Translation::tableName())
            ->where(['model_class' => 'Hirtz\Cms\Models\Asset']);

        foreach ($query->each() as $row) {
            if (!in_array($row['attribute'], $this->textColumns, true)) {
                continue;
            }

            $name = $i18n->getAttributeName($row['attribute'], $row['language']);

            $this->execute('
                UPDATE ' . $this->getQuotedTableName(Asset::tableName()) . '
                SET [[custom_attributes]] = JSON_SET(COALESCE([[custom_attributes]], \'{}\'), :path, :value)
                WHERE [[id]] = :id
            ', [
                ':path' => '$."' . $name . '"',
                ':value' => $row['value'],
                ':id' => $row['model_id'],
            ]);

            $this->copiedTranslations[$row['attribute']][] = $row['language'];
            $count++;
        }

        foreach ($this->copiedTranslations as $attribute => $languages) {
            $this->copiedTranslations[$attribute] = array_values(array_unique($languages));
        }

        echo "    > copied $count translations\n";
    }

    protected function rewriteTrail(): void
    {
        [$entryIds, $sectionIds] = $this->getAssetIdsByClass();

        $classes = [
            EntryAsset::class => $entryIds,
            SectionAsset::class => $sectionIds,
        ];

        foreach ($classes as $class => $ids) {
            if (!$ids) {
                continue;
            }

            $modelClass = Yii::createObject($class)->getTrailBehavior()->modelClass;

            foreach (array_chunk($ids, 2000) as $chunk) {
                $rows = Trail::updateAll(['model_class' => $modelClass], [
                    'model_class' => 'Hirtz\Cms\Models\Asset',
                    'model_id' => array_map(strval(...), $chunk),
                ]);

                echo "    > rewrote $rows trail rows to $modelClass\n";

                $this->rewriteChildTrailData($modelClass, $chunk);
            }
        }
    }

    /**
     * @param list<int> $ids
     */
    protected function rewriteChildTrailData(string $modelClass, array $ids): void
    {
        $db = $this->getDb();
        $trail = $this->getQuotedTableName(Trail::tableName());
        $legacy = $db->quoteValue('Hirtz\Cms\Models\Asset');

        $rows = $db->createCommand("
            UPDATE $trail
            SET [[data]] = JSON_SET([[data]], '$.model_class', " . $db->quoteValue($modelClass) . ")
            WHERE JSON_UNQUOTE(JSON_EXTRACT([[data]], '$.model_class')) = $legacy
              AND CAST(JSON_UNQUOTE(JSON_EXTRACT([[data]], '$.model_id')) AS UNSIGNED) IN ("
                . implode(', ', array_map(intval(...), $ids)) . ")
        ")->execute();

        echo "    > rewrote $rows child trail rows to $modelClass\n";
    }

    /**
     * @return array{list<int>, list<int>} the live ids, then the ids only the trail still knows.
     */
    protected function getAssetIdsByClass(): array
    {
        $entryIds = [];
        $sectionIds = [];

        $rows = (new Query())
            ->select(['id', 'section_id'])
            ->from(self::LEGACY_TABLE)
            ->all();

        foreach ($rows as $row) {
            if ($row['section_id'] === null) {
                $entryIds[] = (int)$row['id'];
            } else {
                $sectionIds[] = (int)$row['id'];
            }
        }

        $known = [...$entryIds, ...$sectionIds];
        $unknown = 0;

        $deleted = (new Query())
            ->select(['model_id', 'data'])
            ->from(Trail::tableName())
            ->where(['model_class' => 'Hirtz\Cms\Models\Asset', 'type' => Trail::TYPE_DELETE])
            ->all();

        foreach ($deleted as $row) {
            $id = (int)$row['model_id'];

            if (in_array($id, $known, true)) {
                continue;
            }

            $data = $row['data'] !== null ? json_decode((string)$row['data'], true) : [];
            $known[] = $id;

            if (($data['section_id'] ?? null) !== null) {
                $sectionIds[] = $id;
            } else {
                $entryIds[] = $id;
            }
        }

        $remaining = (new Query())
            ->select(['model_id'])
            ->distinct()
            ->from(Trail::tableName())
            ->where(['model_class' => 'Hirtz\Cms\Models\Asset'])
            ->andWhere(['not in', 'model_id', array_map(strval(...), $known)])
            ->column();

        foreach ($remaining as $id) {
            $entryIds[] = (int)$id;
            $unknown++;
        }

        if ($unknown) {
            echo "    > $unknown trail rows name an asset that no longer exists, mapped to EntryAsset\n";
        }

        return [$entryIds, $sectionIds];
    }

    protected function foldFileCounts(): void
    {
        $files = $this->getQuotedTableName(File::tableName());

        $this->execute("UPDATE $files SET [[asset_count]] = [[asset_count]] + [[cms_asset_count]]");

        $this->dropIndexesContainingColumn(File::tableName(), 'cms_asset_count');
        $this->dropColumn(File::tableName(), 'cms_asset_count');
    }

    protected function assert(): void
    {
        $db = $this->getDb();
        $legacy = $this->getQuotedTableName(self::LEGACY_TABLE);
        $assets = $this->getQuotedTableName(Asset::tableName());
        $translations = $this->getQuotedTableName(Translation::tableName());
        $trail = $this->getQuotedTableName(Trail::tableName());
        $files = $this->getQuotedTableName(File::tableName());

        $entry = $db->quoteValue(Entry::class);
        $section = $db->quoteValue(Section::class);
        $cms = $db->quoteValue('Hirtz\Cms\Models\Asset');

        $checks = [
            'row count' => "
                SELECT (SELECT COUNT(*) FROM $legacy)
                     - (SELECT COUNT(*) FROM $assets WHERE [[model_class]] IN ($entry, $section))",
            'scalar columns' => "
                SELECT COUNT(*) FROM $legacy [[c]] LEFT JOIN $assets [[a]] ON [[a]].[[id]] = [[c]].[[id]]
                WHERE [[a]].[[id]] IS NULL OR [[a]].[[status]] <> [[c]].[[status]]
                   OR [[a]].[[type]] <> [[c]].[[type]] OR [[a]].[[file_id]] <> [[c]].[[file_id]]
                   OR [[a]].[[position]] <> [[c]].[[position]]
                   OR [[a]].[[model_id]] <> COALESCE([[c]].[[section_id]], [[c]].[[entry_id]])
                   OR [[a]].[[model_class]] <> IF([[c]].[[section_id]] IS NULL, $entry, $section)
                   OR NOT ([[a]].[[updated_by_user_id]] <=> [[c]].[[updated_by_user_id]])
                   OR NOT ([[a]].[[updated_at]] <=> [[c]].[[updated_at]])
                   OR [[a]].[[created_at]] <> [[c]].[[created_at]]",
            'trail class' => "
                SELECT COUNT(*) FROM $trail
                WHERE [[model_class]] = $cms
                   OR JSON_UNQUOTE(JSON_EXTRACT([[data]], '$.model_class')) = $cms",
            'file counts' => "
                SELECT COUNT(*) FROM $files [[f]]
                WHERE [[f]].[[asset_count]] <> (SELECT COUNT(*) FROM $assets [[a]] WHERE [[a]].[[file_id]] = [[f]].[[id]])",
        ];

        foreach ($this->textColumns as $column) {
            $name = $db->quoteValue('$."' . $column . '"');

            $checks["column $column"] = "
                SELECT COUNT(*) FROM $legacy [[c]] JOIN $assets [[a]] ON [[a]].[[id]] = [[c]].[[id]]
                WHERE NOT (JSON_UNQUOTE(JSON_EXTRACT([[a]].[[custom_attributes]], $name))
                    <=> NULLIF([[c]].[[$column]], ''))";
        }

        $i18n = Yii::$app->getI18n();

        foreach ($this->copiedTranslations as $attribute => $languages) {
            foreach ($languages as $language) {
                $name = $db->quoteValue('$."' . $i18n->getAttributeName($attribute, $language) . '"');

                $checks["translation $attribute/$language"] = "
                    SELECT COUNT(*) FROM $translations [[t]] JOIN $assets [[a]] ON [[a]].[[id]] = [[t]].[[model_id]]
                    WHERE [[t]].[[model_class]] = $cms
                      AND [[t]].[[attribute]] = {$db->quoteValue($attribute)}
                      AND [[t]].[[language]] = {$db->quoteValue($language)}
                      AND NOT (JSON_UNQUOTE(JSON_EXTRACT([[a]].[[custom_attributes]], $name))
                          <=> NULLIF([[t]].[[value]], ''))";
            }
        }

        foreach ($checks as $name => $sql) {
            $count = (int)$db->createCommand($sql)->queryScalar();

            if ($count !== 0) {
                throw new RuntimeException("Asset migration check \"$name\" failed with $count rows.");
            }
        }

        echo '    > ' . count($checks) . " assertions passed\n";
    }
}
