<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Test\Models\TestEntry;
use Override;
use Yii;
use yii\db\Expression;
use yii\test\ActiveFixture;

/**
 * Fixtures insert rows straight into the table, so no model event fires and no permalink would be written for a
 * fixture-loaded entry. The data file carries only the URI and the leaf; language, model and timestamps are filled
 * in here so the rows follow whatever language the test runs in.
 */
class PermalinkFixture extends ActiveFixture
{
    public $modelClass = Permalink::class;

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
                    'model' => TestEntry::instance()->getPermalinkModelClass(),
                    'created_at' => $now,
                ];
            }
        }

        return $data;
    }
}
