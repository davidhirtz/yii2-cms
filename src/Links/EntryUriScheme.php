<?php

declare(strict_types=1);

namespace Hirtz\Cms\Links;

use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URI;
use HTMLPurifier_URIScheme;
use Override;

/**
 * Not browsable, so `HTML.TargetBlank` leaves it alone.
 */
class EntryUriScheme extends HTMLPurifier_URIScheme
{
    /**
     * @var bool
     */
    public $browsable = false;

    /**
     * @var bool
     */
    public $may_omit_host = true;

    /**
     * @param HTMLPurifier_URI $uri
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     */
    #[Override]
    public function doValidate(&$uri, $config, $context): bool
    {
        return $uri->host === null
            && $uri->userinfo === null
            && $uri->port === null
            && $uri->query === null
            && preg_match('/^[1-9]\d*$/', (string)$uri->path) === 1;
    }
}
