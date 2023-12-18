<?php

use davidhirtz\yii2\cms\Bootstrap;
use yii\web\Session;

return [
    'id' => 'yii2-cms',
    'aliases' => [
        // This is a fix for the broken aliasing of `BaseMigrateController::getNamespacePath()`
        '@davidhirtz/yii2/cms' => __DIR__ . '/../../src/',
    ],
    'bootstrap' => [
        Bootstrap::class,
    ],
    'components' => [
        'assetManager' => [
            'linkAssets' => true,
        ],
        'db' => [
            'dsn' => getenv('MYSQL_DSN') ?: 'mysql:host=127.0.0.1;dbname=yii2_cms_test',
            'username' => getenv('MYSQL_USER') ?: 'root',
            'password' => getenv('MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8',
            ...is_file(__DIR__ . '/db.php') ? require (__DIR__ . '/db.php') : [],
        ],
        'mailer' => [
            'useFileTransport' => true,
        ],
        'session' => [
            'class' => Session::class,
        ],
    ],
    'params' => [
        'cookieValidationKey' => 'test',
    ],
];
