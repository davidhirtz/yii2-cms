<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Models\Actions\UpdateTenantEntryCount;
use Hirtz\Cms\Models\Entry;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Tenant\Models\Tenant;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\db\Migration;

/**
 * Seeds the tenant every database must have and makes `entry.tenant_id` NOT NULL. Idempotent: an upgraded database
 * that already has the column and its tenants only gains what is missing.
 *
 * @noinspection PhpUnused
 */
class M260908100000Tenant extends Migration
{
    use MigrationTrait;

    public function safeUp(): void
    {
        $this->insertDefaultTenant();
        $this->addEntryTenantId();
        $this->addTenantEntryCount();
    }

    public function safeDown(): void
    {
        $tableName = $this->getDb()->getSchema()->getRawTableName(Entry::tableName());

        if ($this->hasColumn(Entry::tableName(), 'tenant_id')) {
            $this->dropForeignKey("{$tableName}_tenant_id_ibfk", Entry::tableName());
            $this->dropIndexIfExists('tenant_id', Entry::tableName());
            $this->dropColumn(Entry::tableName(), 'tenant_id');
        }

        $this->dropColumnIfExists(Tenant::tableName(), UpdateTenantEntryCount::ENTRY_COUNT_ATTRIBUTE);
    }

    protected function insertDefaultTenant(): void
    {
        if ($this->getDb()->createCommand('SELECT COUNT(*) FROM ' . $this->getQuotedTableName(Tenant::tableName()))->queryScalar()) {
            return;
        }

        $url = $this->getDefaultTenantUrl();

        $this->insert(Tenant::tableName(), [
            'status' => StatusAttributeInterface::STATUS_ENABLED,
            'name' => parse_url($url, PHP_URL_HOST) ?: $url,
            'url' => $url,
            'language' => null,
            'position' => 1,
            'created_at' => new Expression('UTC_TIMESTAMP()'),
        ]);
    }

    /**
     * The canonical host of the site, per environment. Never guessed: it becomes the URL manager's `hostInfo` on
     * every request.
     */
    protected function getDefaultTenantUrl(): string
    {
        $url = $this->getHostInfo() ?: (Yii::$app->params['tenantUrl'] ?? null);

        if (!is_string($url) || !$url) {
            throw new InvalidConfigException('The tenant seed needs the canonical URL of this environment. Set'
                . ' `params[\'tenantUrl\']` or the console `urlManager.hostInfo` and run the migration again.');
        }

        return rtrim($url, '/');
    }

    protected function getHostInfo(): ?string
    {
        try {
            return Yii::$app->getUrlManager()->getHostInfo() ?: null;
        } catch (InvalidConfigException) {
            return null;
        }
    }

    protected function addEntryTenantId(): void
    {
        $tableName = $this->getDb()->getSchema()->getRawTableName(Entry::tableName());

        if (!$this->hasColumn(Entry::tableName(), 'tenant_id')) {
            $this->addColumn(Entry::tableName(), 'tenant_id', (string)$this->integer()
                ->unsigned()
                ->null()
                ->after('type'));
        }

        $this->backfillEntryTenantIds();

        if ($this->getDb()->getTableSchema(Entry::tableName(), true)?->getColumn('tenant_id')?->allowNull) {
            $this->alterColumn(Entry::tableName(), 'tenant_id', (string)$this->integer()
                ->unsigned()
                ->notNull());
        }

        if (!$this->hasIndex('tenant_id', Entry::tableName())) {
            $this->createIndex('tenant_id', Entry::tableName(), ['tenant_id', 'status', 'position']);
        }

        if (!$this->hasForeignKey("{$tableName}_tenant_id_ibfk", Entry::tableName())) {
            $this->addForeignKey(
                "{$tableName}_tenant_id_ibfk",
                Entry::tableName(),
                'tenant_id',
                Tenant::tableName(),
                'id',
                'CASCADE',
            );
        }
    }

    /**
     * With several tenants an entry without one cannot be resolved by a migration — that is a repair for a human.
     */
    protected function backfillEntryTenantIds(): void
    {
        $db = $this->getDb();
        $entries = $this->getQuotedTableName(Entry::tableName());
        $tenants = $this->getQuotedTableName(Tenant::tableName());

        $orphanCount = (int)$db->createCommand("SELECT COUNT(*) FROM $entries WHERE [[tenant_id]] IS NULL")
            ->queryScalar();

        if (!$orphanCount) {
            return;
        }

        $tenantIds = $db->createCommand("SELECT [[id]] FROM $tenants ORDER BY [[id]]")->queryColumn();

        if (count($tenantIds) !== 1) {
            throw new InvalidConfigException("$orphanCount entries have no tenant and this database has"
                . ' ' . count($tenantIds) . ' tenants. Assign them by hand and run the migration again.');
        }

        $this->update(Entry::tableName(), ['tenant_id' => $tenantIds[0]], ['tenant_id' => null]);
    }

    protected function addTenantEntryCount(): void
    {
        $attributeName = UpdateTenantEntryCount::ENTRY_COUNT_ATTRIBUTE;

        if (!$this->hasColumn(Tenant::tableName(), $attributeName)) {
            $this->addColumn(Tenant::tableName(), $attributeName, (string)$this->integer()
                ->unsigned()
                ->notNull()
                ->defaultValue(0)
                ->after('language'));
        }

        $tenants = $this->getQuotedTableName(Tenant::tableName());
        $entries = $this->getQuotedTableName(Entry::tableName());

        $this->execute("
            UPDATE $tenants AS [[tenant]]
            SET [[tenant]].[[$attributeName]] = (
                SELECT COUNT(*) FROM $entries AS [[entry]] WHERE [[entry]].[[tenant_id]] = [[tenant]].[[id]]
            )
        ");
    }

    protected function hasIndex(string $name, string $table): bool
    {
        $db = $this->getDb();
        $sql = 'SHOW INDEX FROM ' . $this->getQuotedTableName($table)
            . ' WHERE ' . $db->quoteColumnName('Key_name') . ' = :name';

        return (bool)$db->createCommand($sql, [':name' => $name])->queryOne();
    }

    protected function hasForeignKey(string $name, string $table): bool
    {
        return array_key_exists($name, $this->getDb()->getTableSchema($table, true)->foreignKeys);
    }
}
