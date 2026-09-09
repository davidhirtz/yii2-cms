<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Migrations\Traits\I18nTablesTrait;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use yii\db\Migration;

/**
 * Drops the entry slug columns, which {@see Permalink} has been the source of truth for since
 * {@see M260909100000Permalink} backfilled it.
 *
 * Category slugs stay: a category without a standalone URL still needs its slug as a filter key, and collapsing that
 * column belongs to the `i18nAttributes` removal instead.
 *
 * @noinspection PhpUnused
 */
class M260909170000DropEntrySlug extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function (): void {
            $entry = Entry::create();

            foreach ($entry->getI18nAttributeNames('slug') as $attributeName) {
                $this->dropIndexIfExists($attributeName, $entry::tableName());
            }

            foreach (['slug', 'parent_slug'] as $attribute) {
                foreach ($entry->getI18nAttributeNames($attribute) as $attributeName) {
                    $this->dropColumnIfExists($entry::tableName(), $attributeName);
                }
            }
        });
    }

    public function safeDown(): void
    {
        $this->i18nTablesCallback(function (): void {
            $entry = Entry::create();

            foreach ($entry->getI18nAttributeNames('slug') as $language => $attributeName) {
                $this->addColumn($entry::tableName(), $attributeName, (string)$this->string(100)->null());
                $this->addColumn(
                    $entry::tableName(),
                    $entry->getI18nAttributeName('parent_slug', $language),
                    (string)$this->string(255)->null()
                );
            }

            $this->restoreSlugColumns();
        });
    }

    /**
     * Rebuilds the dropped columns from the permalink table, splitting each URI back into a leaf and a prefix.
     */
    protected function restoreSlugColumns(): void
    {
        $entry = Entry::create();
        $db = $this->getDb();
        $schema = $db->getSchema();

        $entries = $db->quoteTableName($schema->getRawTableName($entry::tableName()));
        $permalinks = $db->quoteTableName($schema->getRawTableName(Permalink::tableName()));
        $model = $db->quoteValue($entry::class);

        foreach ($entry->getI18nAttributeNames('slug') as $language => $slug) {
            $parentSlug = $entry->getI18nAttributeName('parent_slug', $language);

            $this->execute("
                UPDATE $entries AS [[entry]]
                INNER JOIN $permalinks AS [[permalink]]
                    ON [[permalink]].[[model_id]] = [[entry]].[[id]]
                    AND [[permalink]].[[model]] = $model
                    AND [[permalink]].[[language]] = {$db->quoteValue($language)}
                SET [[entry]].[[$slug]] = [[permalink]].[[slug]],
                    [[entry]].[[$parentSlug]] = TRIM(TRAILING '/' FROM
                        SUBSTRING([[permalink]].[[uri]], 1, CHAR_LENGTH([[permalink]].[[uri]]) - CHAR_LENGTH([[permalink]].[[slug]])))
            ");
        }
    }
}
