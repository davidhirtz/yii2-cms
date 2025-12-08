<?php

declare(strict_types=1);

use Hirtz\Cms\Bootstrap;
use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\tests\data\models\TestAsset;
use Hirtz\Cms\tests\data\models\TestEntry;
use Hirtz\Cms\tests\data\models\TestSection;
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
