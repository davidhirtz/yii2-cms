<?php

use davidhirtz\yii2\media\models\collections\FolderCollection;
use davidhirtz\yii2\media\models\File;
use yii\db\Expression;

$folder = FolderCollection::getDefault();
$now = new Expression('UTC_TIMESTAMP()');
$index = 0;

return [
    'file-1' => [
        'id' => ++$index,
        'status' => File::STATUS_ENABLED,
        'folder_id' => 1,
        'name' => 'Test',
        'basename' => 'test',
        'extension' => 'jpg',
        'width' => 20,
        'height' => 20,
        'cms_asset_count' => 4,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ]
];
