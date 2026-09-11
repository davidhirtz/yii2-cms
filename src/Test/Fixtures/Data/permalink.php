<?php

declare(strict_types=1);

use Hirtz\Cms\Test\Models\TestEntry;

/**
 * Mirrors the entry fixture. Entry ids 4 and 5 are children of 1, id 6 is a child of 2, so their URIs carry the
 * parent path the way {@see TestEntry::getFormattedSlug()} composes it.
 */
return [
    'page-enabled' => ['uri' => 'test-1', 'slug' => 'test-1', 'entry_id' => 1, 'tenant_id' => 1],
    'page-draft' => ['uri' => 'test-2', 'slug' => 'test-2', 'entry_id' => 2, 'tenant_id' => 1],
    'page-disabled' => ['uri' => 'test-3', 'slug' => 'test-3', 'entry_id' => 3, 'tenant_id' => 1],
    'post-1' => ['uri' => 'test-1/post-1', 'slug' => 'post-1', 'entry_id' => 4, 'tenant_id' => 1],
    'post-2' => ['uri' => 'test-1/post-2', 'slug' => 'post-2', 'entry_id' => 5, 'tenant_id' => 1],
    'post-3' => ['uri' => 'test-2/post-3', 'slug' => 'post-3', 'entry_id' => 6, 'tenant_id' => 1],
];
