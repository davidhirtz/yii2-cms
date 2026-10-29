<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Redirect;

/**
 * Runs before the entry is deleted, since the {@see Permalink} records go with it through the cascade.
 */
class DeletePermalinkRedirects
{
    public function __construct(
        protected Entry $entry,
    ) {
    }

    public function delete(): void
    {
        foreach ($this->entry->getPermalinks()->all() as $permalink) {
            $this->deleteRedirects($permalink);
        }
    }

    protected function deleteRedirects(Permalink $permalink): void
    {
        $urls = [];

        foreach ($this->entry->getRedirectLanguages($permalink->language) as $language) {
            $urls[] = Url::sanitize($this->entry->getPermalinkUrl($permalink->uri, $language));
        }

        $urls = array_values(array_unique(array_filter($urls)));

        if (!$urls) {
            return;
        }

        /** @var Redirect[] $redirects */
        $redirects = Redirect::find()
            ->where(['url' => $urls])
            ->all();

        foreach ($redirects as $redirect) {
            $redirect->delete();
        }
    }
}
