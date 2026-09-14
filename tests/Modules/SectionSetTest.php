<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Modules;

use Closure;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\Sets\SectionSet;
use Hirtz\Cms\Models\Sets\SectionTemplate;
use Hirtz\Cms\Module;
use Hirtz\Cms\Test\TestCase;
use Yii;
use yii\base\InvalidConfigException;

/**
 * A set is a project-level catalogue rather than per-record state, so it is declared on the module beside the
 * feature flags, not on the model.
 */
class SectionSetTest extends TestCase
{
    public function testNoSetsAreDeclared(): void
    {
        self::assertSame([], $this->getModule()->getSectionSets());
        self::assertNull($this->getModule()->findSectionSet(1));
    }

    public function testTheModuleDeclaresTheSets(): void
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

        $sets = $this->getModule()->getSectionSets();

        self::assertSame([1], array_keys($sets));
        self::assertSame('Landing page', $sets[1]->getName());
        self::assertSame('layer-group', $sets[1]->getIcon());

        $sections = $sets[1]->getSections();

        self::assertCount(2, $sections);
        self::assertSame(Section::TYPE_DEFAULT, $sections[0]->type);
        self::assertSame(['name' => 'Hero'], $sections[0]->getAttributes());
        self::assertSame(['name' => 'Text', 'status' => Section::STATUS_DISABLED], $sections[1]->getAttributes());

        self::assertSame($sets[1], $this->getModule()->findSectionSet(1));
        self::assertNull($this->getModule()->findSectionSet(2));
        self::assertNull($this->getModule()->findSectionSet(null));
    }

    /**
     * The declaration is resolved once, so a set is the same object wherever it is read.
     */
    public function testTheSetsAreResolvedOnce(): void
    {
        $calls = 0;

        $this->setSectionSets(function () use (&$calls): array {
            $calls++;

            return [
                SectionSet::make(1)
                    ->name('Landing page')
                    ->sections(SectionTemplate::make(Section::TYPE_DEFAULT)),
            ];
        });

        $this->getModule()->getSectionSets();
        $this->getModule()->getSectionSets();

        self::assertSame(1, $calls);
    }

    public function testASetWithoutANameIsRefused(): void
    {
        $this->setSectionSets(fn (): array => [
            SectionSet::make(1)->sections(SectionTemplate::make(Section::TYPE_DEFAULT)),
        ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('has no name');

        $this->getModule()->getSectionSets();
    }

    public function testASetWithoutSectionsIsRefused(): void
    {
        $this->setSectionSets(fn (): array => [
            SectionSet::make(1)->name('Empty'),
        ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('declares no sections');

        $this->getModule()->getSectionSets();
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

        $this->getModule()->getSectionSets();
    }

    public function testTwoSetsCannotShareAValue(): void
    {
        $this->setSectionSets(fn (): array => [
            SectionSet::make(1)
                ->name('First')
                ->sections(SectionTemplate::make(Section::TYPE_DEFAULT)),
            SectionSet::make(1)
                ->name('Second')
                ->sections(SectionTemplate::make(Section::TYPE_DEFAULT)),
        ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('declares "1" twice');

        $this->getModule()->getSectionSets();
    }

    public function testAnythingButASetIsRefused(): void
    {
        $this->setSectionSets(fn (): array => ['Landing page']);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('must be a list of');

        $this->getModule()->getSectionSets();
    }

    /**
     * @param Closure(): array<mixed> $sectionSets
     */
    private function setSectionSets(Closure $sectionSets): void
    {
        $this->getModule()->setSectionSets($sectionSets);
    }

    private function getModule(): Module
    {
        return Section::getModule();
    }
}
