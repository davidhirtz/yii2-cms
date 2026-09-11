<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Interfaces;

use Hirtz\Cms\Models\Actions\SavePermalinks;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Queries\PermalinkQuery;
use Hirtz\Cms\Models\Traits\PermalinkTrait;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;

/**
 * A model reachable under its own URL, implemented via {@see PermalinkTrait}.
 */
interface PermalinkInterface extends I18nAttributeInterface
{
    /**
     * `false` removes the URL of a model that is addressable in principle, without removing its slug.
     */
    public function hasPermalink(): bool;

    /**
     * @return PermalinkQuery<Permalink>
     */
    public function getPermalinks(): PermalinkQuery;

    public function getPermalink(?string $language = null): ?Permalink;

    /**
     * The canonical base class, never `static::class`: the container may resolve a subclass, and the record has to
     * stay visible from both.
     *
     * @return class-string
     */
    public function getPermalinkModelClass(): string;

    /**
     * @return list<string> the languages a {@see Permalink} record is written for
     */
    public function getPermalinkLanguages(): array;

    /**
     * The stored path, without leading or trailing slashes, safe to call for every row of a listing.
     */
    public function getFormattedSlug(?string $language = null): string;

    /**
     * The path a save would store, recomputed from the current attributes. An implementation may query, so keep it
     * off listings.
     */
    public function composeFormattedSlug(?string $language = null): string;

    public function buildPermalink(?string $language = null): Permalink;

    public function getPermalinkUrl(string $uri, ?string $language = null): false|string;

    public function savePermalinks(): SavePermalinks;

    public function deletePermalinks(): void;

    /**
     * @return array<string, mixed> extra attributes copied onto every {@see Permalink} record of this model
     */
    public function getPermalinkAttributes(): array;
}
