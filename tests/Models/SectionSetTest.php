<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Models;

use Closure;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Cms\Models\Sets\SectionTemplate;
use Hirtz\Cms\Test\TestCase;
use Hirtz\Skeleton\Models\Definitions\DefinitionRegistry;
use Override;
use Yii;
use yii\base\InvalidConfigException;

class SectionSetTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(Section::class);
        Section::instance(true);

        parent::tearDown();
    }

    public function testNoSetsAreDeclared(): void
    {
        self::assertSame([], Section::getSectionSetDefinitions());
        self::assertNull(Section::findSectionSet(1));
    }

    public function testTheContainerDeclaresTheSets(): void
    {
        $this->setSectionSets(fn (): array => [
            SectionSet::make(1)
                ->name('Landing page')
                ->icon('layer-group')
                ->sections(
                    SectionTemplate::make(Section::TYPE_DEFAULT)
                        ->attribute('name', 'Hero'),
                    SectionTemplate::make(Section::TYPE_DEFAULT)
                        ->attributes(['name' => 'Text', 'status' => Section::STATUS_DISABLED]),
                ),
        ]);

        $sets = Section::getSectionSetDefinitions();

        self::assertSame([1], array_keys($sets));
        self::assertSame('Landing page', $sets[1]->getName());
        self::assertSame('layer-group', $sets[1]->getIcon());

        $sections = $sets[1]->getSections();

        self::assertCount(2, $sections);
        self::assertSame(Section::TYPE_DEFAULT, $sections[0]->type);
        self::assertSame(['name' => 'Hero'], $sections[0]->getAttributes());
        self::assertSame(['name' => 'Text', 'status' => Section::STATUS_DISABLED], $sections[1]->getAttributes());

        self::assertSame($sets[1], Section::findSectionSet(1));
        self::assertNull(Section::findSectionSet(2));
        self::assertNull(Section::findSectionSet(null));
    }

    public function testASetWithoutSectionsIsRefused(): void
    {
        $this->setSectionSets(fn (): array => [
            SectionSet::make(1)->name('Empty'),
        ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('declares no sections');

        Section::getSectionSetDefinitions();
    }

    public function testASetNamingAnUndeclaredSectionTypeIsRefused(): void
    {
        $this->setSectionSets(fn (): array => [
            SectionSet::make(1)
                ->name('Broken')
                ->sections(SectionTemplate::make(99)),
        ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('names the undeclared section type 99');

        Section::getSectionSetDefinitions();
    }

    /**
     * @param Closure(): list<SectionSet> $sectionSets
     */
    private function setSectionSets(Closure $sectionSets): void
    {
        Yii::$container->set(Section::class, ['sectionSets' => $sectionSets]);
        DefinitionRegistry::resetClass(Section::class);
    }
}
