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
 * Moves the permalinks of models whose slug is not translated from the source language to
 * {@see Permalink::LANGUAGE_ALL}, so they resolve under every language rather than only the source one.
 *
 * A translated slug keeps one record per language and is left untouched, which is why this converts only models
 * whose `slug` is not an `i18nAttribute`.
 *
 * @noinspection PhpUnused
 */
class M260910100000PermalinkLanguageAll extends Migration
{
    use MigrationTrait;
    use I18nTablesTrait;

    public function safeUp(): void
    {
        $this->i18nTablesCallback(function (): void {
            foreach ($this->getUntranslatedModelClasses() as $class) {
                $this->update(
                    Permalink::tableName(),
                    ['language' => Permalink::LANGUAGE_ALL],
                    ['model' => $class, 'language' => Yii::$app->sourceLanguage]
                );
            }
        });
    }

    public function safeDown(): void
    {
        $this->i18nTablesCallback(function (): void {
            foreach ($this->getUntranslatedModelClasses() as $class) {
                $this->update(
                    Permalink::tableName(),
                    ['language' => Yii::$app->sourceLanguage],
                    ['model' => $class, 'language' => Permalink::LANGUAGE_ALL]
                );
            }
        });
    }

    /**
     * @return list<class-string>
     */
    protected function getUntranslatedModelClasses(): array
    {
        return array_values(array_filter(
            [Entry::class, Category::class],
            static fn (string $class): bool => !Yii::createObject($class)->isI18nAttribute('slug')
        ));
    }
}
