<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Override;
use yii\db\Expression;
use Hirtz\Skeleton\Test\Fixtures\ActiveFixture;
use yii\test\Fixture;

/**
 * Fixtures insert rows straight into the table, so no model event writes the permalinks; language and timestamps follow
 * the test's language.
 */
class PermalinkFixture extends ActiveFixture
{
    public $modelClass = Permalink::class;

    /**
     * @var list<class-string<Fixture>>
     */
    public $depends = [
        EntryFixture::class,
    ];

    #[Override]
    protected function getData(): array
    {
        $now = new Expression('UTC_TIMESTAMP()');
        $permalinks = require(__DIR__ . '/Data/permalink.php');
        $data = [];

        foreach (TestEntry::instance()->getPermalinkLanguages() as $language) {
            foreach ($permalinks as $key => $attributes) {
                $data["$key-$language"] = [
                    ...$attributes,
                    'language' => $language,
                    'created_at' => $now,
                ];
            }
        }

        return $data;
    }
}
