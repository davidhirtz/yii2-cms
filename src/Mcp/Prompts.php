<?php

declare(strict_types=1);

namespace Hirtz\Cms\Mcp;

use Hirtz\Skeleton\Mcp\Prompt;

class Prompts
{
    public static function translateEntry(): Prompt
    {
        return Prompt::make('translate_entry')
            ->title('Translate an entry')
            ->description('Translates an entry and its sections into the content languages it is missing.')
            ->argument('id', 'The id of the entry.', true)
            ->argument('language', 'One content language, e.g. "de"; every language when it is not given.')
            ->text(static fn (array $arguments): string => implode("\n\n", [
                "Translate entry {$arguments['id']} of this website into "
                . (isset($arguments['language']) ? "the content language \"{$arguments['language']}\"" : 'every content language whose attributes are empty') . '.',
                'Read it with get_record, and its sections with find_records (resource "section", filter {"entry_id": '
                . $arguments['id'] . '}). describe_resource names the translated attributes and their language suffixes.',
                'Write each translation with update_record, passing the updated_at get_record answered. Keep the HTML '
                . 'markup as it is, write a slug as lowercase words joined by hyphens, and leave the source language '
                . 'and every status unchanged.',
                'Finish with a list of what you translated.',
            ]));
    }

    public static function writeMetaDescriptions(): Prompt
    {
        return Prompt::make('write_meta_descriptions')
            ->title('Write meta descriptions')
            ->description('Writes the missing meta descriptions of the entries.')
            ->argument('limit', 'How many entries at most, 20 when it is not given.')
            ->text(static fn (array $arguments): string => implode("\n\n", [
                'Find up to ' . ($arguments['limit'] ?? '20') . ' entries without a meta description: find_records with '
                . 'resource "entry" and filter {"description": [null, ""]}.',
                'For each, read the entry and its sections (find_records, resource "section", filter {"entry_id": <id>}) '
                . 'and write a description of what the page offers, at most 160 characters, in every content language '
                . 'whose description is empty. Save it with update_record, passing the updated_at get_record answered.',
                'Finish with a list of the entries and their new descriptions.',
            ]));
    }
}
