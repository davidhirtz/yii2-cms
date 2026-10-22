<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Test\TestCase;
use Override;
use Yii;

class RequiredTranslationTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$container->set(Category::class, ['i18nAttributes' => ['name', 'slug']]);
        Yii::$container->set(Block::class, ['i18nAttributes' => ['name']]);
        Category::instance(true);
        Block::instance(true);
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Category::class);
        Yii::$container->clear(Block::class);
        Category::instance(true);
        Block::instance(true);

        parent::tearDown();
    }

    public function testACategoryNamedInTheSourceLanguageAloneIsSaved(): void
    {
        $category = Category::create();
        $category->loadDefaultValues();
        $category->name = 'Новости';

        self::assertTrue($category->save(), print_r($category->getErrors(), true));
        self::assertSame('Новости', $category->getAttribute('name_de'));
    }

    public function testABlockNamedInTheSourceLanguageAloneIsSaved(): void
    {
        Block::getModule()->enableBlocks = true;

        $block = Block::create();
        $block->loadDefaultValues();
        $block->name = 'Footer 🙂';

        self::assertTrue($block->save(), print_r($block->getErrors(), true));
        self::assertSame('Footer 🙂', $block->getAttribute('name_de'));
    }
}
