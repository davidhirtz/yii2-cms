<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Yii;
use yii\db\Migration;

/**
 * Creates the {@see Permalink} table, backfills it from the entry slug columns and drops those columns.
 *
 * An untranslated slug is stored once under {@see Permalink::LANGUAGE_ALL}; a translated one gets a record per
 * language. Categories are not backfilled — they had no standalone URL, so there is nothing to preserve; their
 * records are written at runtime once `Module::$enableCategoryUrls` is on. The category `slug` column stays.
 *
 * @noinspection PhpUnused
 */
class M260909100000Permalink extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->createPermalinkTable();
        $this->insertEntryPermalinks();
        $this->dropEntrySlugColumns();
    }

    public function safeDown(): void
    {
        $this->restoreEntrySlugColumns();
        $this->dropTable(Permalink::tableName());
    }

    protected function createPermalinkTable(): void
    {
        $this->createTable(Permalink::tableName(), [
            'id' => $this->primaryKey()->unsigned(),
            'language' => $this->string(16)->notNull(),
            'uri' => $this->string(255)->notNull(),
            'slug' => $this->string(100)->notNull(),
            'model' => $this->string()->notNull(),
            'model_id' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull(),
        ], $this->getTableOptions());

        $this->createIndex('uri', Permalink::tableName(), ['language', 'uri'], true);
        $this->createIndex('model', Permalink::tableName(), ['model', 'model_id', 'language'], true);
    }

    protected function insertEntryPermalinks(): void
    {
        $entry = Entry::create();
        $db = $this->getDb();

        $permalinks = $this->getQuotedTableName(Permalink::tableName());
        $entries = $this->getQuotedTableName($entry::tableName());
        $model = $db->quoteValue($entry->getPermalinkModelClass());

        foreach ($entry->getPermalinkLanguages() as $language) {
            [$slug, $parentSlug] = $this->getSlugColumns($entry, $language);

            if (!$this->hasColumn($entry::tableName(), $slug)) {
                continue;
            }

            $uri = $this->hasColumn($entry::tableName(), $parentSlug)
                ? "TRIM(BOTH '/' FROM CONCAT_WS('/', NULLIF([[$parentSlug]], ''), [[$slug]]))"
                : "[[$slug]]";

            $this->execute("
                INSERT INTO $permalinks ([[language]], [[uri]], [[slug]], [[model]], [[model_id]], [[created_at]])
                SELECT {$db->quoteValue($language)}, $uri, [[$slug]], $model, [[id]], UTC_TIMESTAMP()
                FROM $entries
                WHERE [[$slug]] IS NOT NULL AND [[$slug]] != ''
            ");
        }
    }

    protected function dropEntrySlugColumns(): void
    {
        $entry = Entry::create();

        foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
            $this->dropIndexIfExists($attributeName, $entry::tableName());
        }

        foreach (['slug', 'parent_slug'] as $attribute) {
            foreach ($entry->getI18nAttributeNames($attribute) as $attributeName) {
                $this->dropColumnIfExists($entry::tableName(), $attributeName);
            }
        }
    }

    protected function restoreEntrySlugColumns(): void
    {
        $entry = Entry::create();
        $db = $this->getDb();

        $entries = $this->getQuotedTableName($entry::tableName());
        $permalinks = $this->getQuotedTableName(Permalink::tableName());
        $model = $db->quoteValue($entry->getPermalinkModelClass());

        foreach ($entry->getPermalinkLanguages() as $language) {
            [$slug, $parentSlug] = $this->getSlugColumns($entry, $language);

            if (!$this->hasColumn($entry::tableName(), $slug)) {
                $this->addColumn($entry::tableName(), $slug, (string)$this->string(100)->null());
            }

            if (!$this->hasColumn($entry::tableName(), $parentSlug)) {
                $this->addColumn($entry::tableName(), $parentSlug, (string)$this->string(255)->null());
            }

            $this->execute("
                UPDATE $entries AS [[entry]]
                INNER JOIN $permalinks AS [[permalink]]
                    ON [[permalink]].[[model_id]] = [[entry]].[[id]]
                    AND [[permalink]].[[model]] = $model
                    AND [[permalink]].[[language]] = {$db->quoteValue($language)}
                SET [[entry]].[[$slug]] = [[permalink]].[[slug]],
                    [[entry]].[[$parentSlug]] = TRIM(TRAILING '/' FROM SUBSTRING(
                        [[permalink]].[[uri]], 1, CHAR_LENGTH([[permalink]].[[uri]]) - CHAR_LENGTH([[permalink]].[[slug]])))
            ");
        }
    }

    /**
     * Maps a permalink storage language to the entry columns it reads: {@see Permalink::LANGUAGE_ALL} resolves to the
     * source-language columns, a real language to its own.
     *
     * @return array{string, string} the `slug` and `parent_slug` column names
     */
    protected function getSlugColumns(Entry $entry, string $language): array
    {
        $language = $language === Permalink::LANGUAGE_ALL ? Yii::$app->sourceLanguage : $language;

        return [
            $entry->getI18nAttributeName('slug', $language),
            $entry->getI18nAttributeName('parent_slug', $language),
        ];
    }
}
