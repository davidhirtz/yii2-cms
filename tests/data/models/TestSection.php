<?php

namespace davidhirtz\yii2\cms\tests\data\models;

use davidhirtz\yii2\cms\models\Section;

class TestSection extends Section
{
    public const TYPE_HEADLINE = 1;
    public const TYPE_COLUMN = 2;
    public const TYPE_GALLERY = 3;

    public static function getTypes(): array
    {
        return [
            self::TYPE_HEADLINE => [
                'name' => 'Headline',
                'hiddenFields' => ['content', '#assets'],
            ],
            self::TYPE_COLUMN => [
                'name' => 'Column',
                'hiddenFields' => ['name', '#assets'],
            ],
            self::TYPE_GALLERY => [
                'name' => 'Gallery',
                'hiddenFields' => ['name', 'content'],
            ],
        ];
    }
}
