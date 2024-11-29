<?php

use davidhirtz\yii2\cms\Bootstrap;
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
    'modules' => [
        'cms' => [
            'enableI18nTables' => true,
        ],
    ],
    'params' => [
        'cookieValidationKey' => 'test',
    ],
];
