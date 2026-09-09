<?php

declare(strict_types=1);

namespace Hirtz\Cms\Migrations;

use Hirtz\Cms\Migrations\Traits\I18nTablesTrait;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Yii;
use yii\db\Migration;

/**
 * Normalises {@see Permalink::$model} to the canonical base class.
 *
 * Earlier migrations stored whatever the container resolved `Entry::class` to, which differs by load path: the same
 * row is a `Hirtz\Cms\Tenant\Models\Entry` through a relation and a `TestEntry` in a test, so the records became
 * invisible from the other path.
 *
 * @noinspection PhpUnused
 */
class M260909180000PermalinkModelClass extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function (): void {
            foreach ([Entry::class, Category::class] as $class) {
                $this->update(
                    Permalink::tableName(),
                    ['model' => $class],
                    ['model' => $this->getSubclassNames($class)]
                );
            }
        });
    }

    public function safeDown(): void
    {
        // The resolved class names cannot be recovered, and the canonical ones are what every reader now expects.
    }

    /**
     * @param class-string $class
     * @return list<class-string>
     */
    protected function getSubclassNames(string $class): array
    {
        $names = [$class, Yii::createObject($class)::class];

        foreach (get_declared_classes() as $declared) {
            if (is_a($declared, $class, true)) {
                $names[] = $declared;
            }
        }

        return array_values(array_unique($names));
    }
}
