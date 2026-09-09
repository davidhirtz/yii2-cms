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
     * The languages a {@see Permalink} record is written for.
     *
     * @return list<string>
     */
    public function getPermalinkLanguages(): array;

    /**
     * The full path this model resolves under, without leading or trailing slashes.
     */
    public function getFormattedSlug(?string $language = null): string;
}
