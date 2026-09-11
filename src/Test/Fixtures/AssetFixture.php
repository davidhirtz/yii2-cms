<?php

declare(strict_types=1);

namespace Hirtz\Cms\Test\Fixtures;

use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\File;
use Hirtz\Media\Test\Fixtures\FileFixture;
use Override;
use Yii;
use yii\test\ActiveFixture;

/**
 * The rows carry their own `model_class`, so the fixture is loaded through the base model.
 */
class AssetFixture extends ActiveFixture
{
    public $depends = [
        EntryFixture::class,
        FileFixture::class,
        SectionFixture::class,
    ];

    public $modelClass = Asset::class;

    /**
     * Fixtures insert rows straight into the table, so no model event updates the file counts.
     */
    #[Override]
    public function afterLoad(): void
    {
        Yii::$app->getDb()->createCommand('
            UPDATE ' . File::tableName() . ' AS [[file]]
            SET [[file]].[[asset_count]] = (
                SELECT COUNT(*) FROM ' . Asset::tableName() . ' AS [[asset]]
                WHERE [[asset]].[[file_id]] = [[file]].[[id]]
            )
        ')->execute();

        parent::afterLoad();
    }
}
