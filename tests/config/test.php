<?php

use davidhirtz\yii2\cms\Bootstrap;
use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\tests\data\models\TestAsset;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use davidhirtz\yii2\cms\tests\data\models\TestSection;
use yii\web\Session;

return [
    'aliases' => [
        // This is a fix for the broken aliasing of `BaseMigrateController::getNamespacePath()`
        '@davidhirtz/yii2/cms' => __DIR__ . '/../../src/',
    ],
    'bootstrap' => [
        Bootstrap::class,
    ],
    'components' => [
        'db' => [
            'dsn' => getenv('MYSQL_DSN') ?: 'mysql:host=127.0.0.1;dbname=yii2_cms_test',
            'username' => getenv('MYSQL_USER') ?: 'root',
            'password' => getenv('MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8',
        ],
        'i18n' => [
            'languages' => ['en-US', 'de'],
        ],
        'session' => [
            'class' => Session::class,
        ],
    ],
    'container' => [
        'definitions' => [
            Asset::class => TestAsset::class,
            Entry::class => TestEntry::class,
            Section::class => TestSection::class,
        ],
    ],
    'modules' => [
        'cms' => [
            'enableI18nTables' => true,
            'enableNestedEntries' => true,
            'enableSectionEntries' => true,
        ],
    ],
    'params' => [
        'cookieValidationKey' => 'test',
    ],
];
