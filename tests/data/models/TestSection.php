<?php

namespace davidhirtz\yii2\cms\tests\data\models;

use davidhirtz\yii2\cms\models\Section;

class TestSection extends Section
{
    public const TYPE_HEADLINE = 1;
    public const TYPE_TEXT_COLUMN = 2;
    public const TYPE_GALLERY = 3;
    public const TYPE_BLOG = 4;

    public static function getTypes(): array
    {
        return [
            self::TYPE_HEADLINE => [
                'name' => 'Headline',
                'hiddenFields' => ['content', '#entries'],
            ],
            self::TYPE_TEXT_COLUMN => [
                'name' => 'Column',
                'hiddenFields' => ['name', '#assets', '#entries'],
            ],
            self::TYPE_GALLERY => [
                'name' => 'Gallery',
                'hiddenFields' => ['name', 'content', '#entries'],
            ],
            self::TYPE_BLOG => [
                'name' => 'Blog',
                'entriesOrderBy' => ['position' => SORT_ASC],
                'hiddenFields' => ['name', 'content', '#assets'],
            ],
        ];
    }
}
