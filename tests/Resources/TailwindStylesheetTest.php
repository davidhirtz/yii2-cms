<?php

declare(strict_types=1);

namespace Hirtz\Cms\Tests\Resources;

use Hirtz\Cms\Test\TestCase;
use Hirtz\Cms\Widgets\Traits\TailwindViewportsTrait;

class TailwindStylesheetTest extends TestCase
{
    public function testTheStylesheetNamesTheDefaultViewportClasses(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/resources/css/tailwind.css');
        self::assertNotFalse($css);

        $inline = preg_match('/@source inline\("([^"]+)"\);/', $css, $matches) ? explode(' ', $matches[1]) : [];
        $classes = explode(' ', implode(' ', array_keys((new TailwindViewports())->getViewports())));

        sort($inline);
        sort($classes);

        self::assertSame($classes, $inline);
    }
}

class TailwindViewports
{
    use TailwindViewportsTrait;

    /**
     * @return array<string, list<int>>
     */
    public function getViewports(): array
    {
        return $this->viewports;
    }
}
