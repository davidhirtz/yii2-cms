<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Interfaces;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Cms\Models\Queries\PermalinkQuery;
use Hirtz\Cms\Models\Traits\PermalinkTrait;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;

/**
 * A model that is reachable under its own URL. Implemented via {@see PermalinkTrait}.
 *
 * Extends {@see I18nAttributeInterface} because permalinks are stored per language: the owner has to be able to
 * report its slug for each of them, whether that slug lives in an I18N column or not.
 */
interface PermalinkInterface extends I18nAttributeInterface
{
    /**
     * Whether {@see Permalink} records are written for this model. Models that are addressable in principle but not
     * in a given application return `false` here, which removes their URL without removing their slug.
     */
    public function hasPermalink(): bool;

    /**
     * @return PermalinkQuery<Permalink>
     */
    public function getPermalinks(): PermalinkQuery;

    public function getPermalink(?string $language = null): ?Permalink;

    /**
     * The class stored in {@see Permalink::$model}, and matched on when reading the records back.
     *
     * It must be the canonical base class, never `static::class`: the container resolves `Entry::class` to whatever
     * an application configured, so the very same row is a `TestEntry` when a test builds it and a
     * `Hirtz\Cms\Tenant\Models\Entry` when a relation loads it. Keying on the runtime class makes the record
     * invisible from the other path.
     *
     * @return class-string
     */
    public function getPermalinkModelClass(): string;

    /**
     * The languages a {@see Permalink} record is written for.
     *
     * @return list<string>
     */
    public function getPermalinkLanguages(): array;

    /**
     * The full path this model resolves under, without leading or trailing slashes. Reads the stored permalink, so
     * it is safe to call for every row of a listing.
     */
    public function getFormattedSlug(?string $language = null): string;

    /**
     * The URI a save would store, recomputed from the current attributes (a nested model like {@see Entry} prepends
     * its parent path). The save path and validation call this; an implementation may query, so keep it off listings.
     */
    public function composeFormattedSlug(?string $language = null): string;

    /**
     * Builds the {@see Permalink} this model's current state would save, without saving it.
     */
    public function buildPermalink(?string $language = null): Permalink;

    public function getPermalinkUrl(string $uri, ?string $language = null): false|string;

    /**
     * @return list<string> the languages whose URL changed
     */
    public function savePermalinks(): array;

    public function deletePermalinks(): void;

    /**
     * Extra attributes copied onto the model's {@see Permalink} records on every save. `yii2-cms-tenant` uses this
     * to carry `tenant_id` across, which is what scopes the uniqueness of a URL to one tenant.
     *
     * @return array<string, mixed>
     */
    public function getPermalinkAttributes(): array;
}
