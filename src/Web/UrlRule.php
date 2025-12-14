<?php

declare(strict_types=1);

namespace Hirtz\Cms\web;

use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Web\UrlManager;

class UrlRule extends \yii\web\UrlRule
{
    /**
     * @var string the name of the GET parameter that contains the category slug.
     */
    public string $paramName = 'category';

    public $encodeParams = false;
    protected static bool $mismatch = false;

    /**
     * @param UrlManager $manager
     * @param Request $request
     */
    #[\Override]
    public function parseRequest($manager, $request): array|bool
    {
        if (!static::$mismatch) {
            $placeholders = array_flip($this->placeholders);
            $placeholder = $placeholders[$this->paramName] ?? null;

            if ($placeholder) {
                $matches = preg_split('~(?<!\\\)/~', str_replace(['#^', '$#u'], '', $this->pattern));

                $pattern = '(?P<' . $placeholder . '>[^\\/]+)';
                $start = array_search($pattern, $matches, true);

                $parts = explode('/', $request->getPathInfo());
                $parentId = null;
                $branch = 0;

                foreach (array_slice($parts, $start) as $part) {
                    if ($category = CategoryCollection::getBySlug($part, $parentId)) {
                        $parentId = $category->id;
                        $branch++;
                        continue;
                    }

                    break;
                }

                if ($branch) {
                    $slug = implode('/', array_splice($parts, $start, $branch));

                    $this->pattern = str_replace($pattern, $slug, $this->pattern);
                    $request->setQueryParams([...$request->getQueryParams(), $this->paramName => $slug]);

                    return parent::parseRequest($manager, $request);
                }

                static::$mismatch = true;
            }
        }

        return false;
    }
}
