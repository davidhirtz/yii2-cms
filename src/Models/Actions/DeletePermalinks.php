<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\ActiveRecord;
use Hirtz\Cms\Models\Interfaces\PermalinkInterface;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Models\Redirect;
use Yii;

class DeletePermalinks
{
    public function __construct(
        protected ActiveRecord&PermalinkInterface $model,
    ) {
    }

    public function delete(): void
    {
        foreach ($this->model->getPermalinks()->all() as $permalink) {
            $this->deleteRedirects($permalink);
            $permalink->delete();
        }

        $this->model->populateRelation('permalinks', []);
    }

    /**
     * Redirects pointing at a URL that no longer resolves would send visitors to a 404.
     */
    protected function deleteRedirects(Permalink $permalink): void
    {
        $url = Redirect::sanitizeUrl($this->model->getPermalinkUrl($permalink->uri, $permalink->language));

        if (!$url) {
            return;
        }

        /** @var Redirect[] $redirects */
        $redirects = Redirect::find()
            ->where(['url' => $url])
            ->all();

        foreach ($redirects as $redirect) {
            $redirect->delete();
        }
    }
}
