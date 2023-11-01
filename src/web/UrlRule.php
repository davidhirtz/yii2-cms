<?php

namespace davidhirtz\yii2\cms\web;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\skeleton\web\Request;
use davidhirtz\yii2\skeleton\web\UrlManager;

/**
 * Class UrlManager.
 * @package davidhirtz\yii2\cms\web
 */
class UrlRule extends \yii\web\UrlRule
{
    /**
     * @var string
     */
    public $paramName = 'category';

    /**
     * @var bool
     */
    public $encodeParams = false;

    static private bool $mismatch = false;

    /**
     * @param UrlManager $manager
     * @param Request $request
     * @return mixed
     */
    public function parseRequest($manager, $request)
    {
        if (!static::$mismatch) {
            $placeholders = array_flip($this->placeholders);
            $placeholder = $placeholders[$this->paramName] ?? null;

            if ($placeholder) {
                $matches = preg_split('~(?<!\\\)\/~', str_replace(['#^', '$#u'], '', $this->pattern));

                $pattern = '(?P<' . $placeholder . '>[^\\/]+)';
                $start = array_search($pattern, $matches);

                $parts = explode('/', $request->getPathInfo());
                $parentId = null;
                $branch = 0;

                foreach (array_slice($parts, $start) as $part) {
                    if ($category = Category::getBySlug($part, $parentId)) {
                        $parentId = $category->id;
                        $branch++;
                        continue;
                    }

                    break;
                }

                if ($branch) {
                    $slug = implode('/', array_splice($parts, $start, $branch));

                    $this->pattern = str_replace($pattern, $slug, $this->pattern);
                    $request->setQueryParams(array_merge($request->getQueryParams(), [$this->paramName => $slug]));

                    return parent::parseRequest($manager, $request);
                }

                static::$mismatch = true;
            }
        }

        return false;
    }
}