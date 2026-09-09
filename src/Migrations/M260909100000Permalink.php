<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Migrations\Traits\I18nTablesTrait;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * Creates the {@see Permalink} table and backfills it from the entry slug columns. This migration is additive: the
 * `slug` and `parent_slug` columns are dropped by a later migration, once the models write permalinks themselves.
 *
 * Categories are not backfilled. They have no standalone URL yet, so there is no existing URL to preserve — their
 * records are written once `Category::hasPermalink()` exists.
 *
 * @noinspection PhpUnused
 */
class M260909100000Permalink extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function (): void {
            $this->createPermalinkTable();
            $this->insertEntryPermalinks();
            $this->updateEntryParentIds();
        });
    }

    public function safeDown(): void
    {
        $this->i18nTablesCallback(function (): void {
            $this->dropTable(Permalink::tableName());
        });
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
            'parent_id' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull(),
        ], $this->getTableOptions());

        $this->createIndex('uri', Permalink::tableName(), ['language', 'uri'], true);
        $this->createIndex('model', Permalink::tableName(), ['model', 'model_id', 'language'], true);

        $this->addForeignKey(
            $this->getForeignKeyName(Permalink::tableName(), 'parent_id') . '_ibfk',
            Permalink::tableName(),
            'parent_id',
            Permalink::tableName(),
            'id',
            'SET NULL'
        );
    }

    protected function insertEntryPermalinks(): void
    {
        $entry = Entry::create();
        $db = $this->getDb();

        $permalinks = $this->getQuotedTableName(Permalink::tableName());
        $entries = $this->getQuotedTableName($entry::tableName());
        $model = $db->quoteValue($entry::class);

        foreach ($entry->getI18nAttributeNames('slug') as $language => $slug) {
            $parentSlug = $entry->getI18nAttributeName('parent_slug', $language);
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

    protected function updateEntryParentIds(): void
    {
        $entry = Entry::create();
        $db = $this->getDb();

        $permalinks = $this->getQuotedTableName(Permalink::tableName());
        $entries = $this->getQuotedTableName($entry::tableName());
        $model = $db->quoteValue($entry::class);

        $this->execute("
            UPDATE $permalinks AS [[child]]
            INNER JOIN $entries AS [[entry]]
                ON [[entry]].[[id]] = [[child]].[[model_id]]
            INNER JOIN $permalinks AS [[parent]]
                ON [[parent]].[[model]] = $model
                AND [[parent]].[[model_id]] = [[entry]].[[parent_id]]
                AND [[parent]].[[language]] = [[child]].[[language]]
            SET [[child]].[[parent_id]] = [[parent]].[[id]]
            WHERE [[child]].[[model]] = $model
        ");
    }

    protected function getQuotedTableName(string $tableName): string
    {
        $db = $this->getDb();
        return $db->quoteTableName($db->getSchema()->getRawTableName($tableName));
    }
}
