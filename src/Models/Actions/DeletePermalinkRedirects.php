<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Models\Redirect;

/**
 * Redirects pointing at a URL that no longer resolves would send visitors to a 404. Runs before the entry is
 * deleted: the {@see Permalink} records go with it through the foreign key cascade.
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
        $url = Redirect::sanitizeUrl($this->entry->getPermalinkUrl($permalink->uri, $permalink->language));

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
