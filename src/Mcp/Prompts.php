<?php

declare(strict_types=1);

namespace Hirtz\Cms\Mcp;

use Hirtz\Skeleton\Mcp\Prompt;

class Prompts
{
    /**
     * Written for the person, not the model: what this connection lets them do, in their own language.
     */
    public static function gettingStarted(): Prompt
    {
        return Prompt::make('getting_started')
            ->title('Getting started')
            ->description('Explains in plain language what you can do on this website through this connection.')
            ->text(static fn (): string => implode("\n\n", [
                'I am an editor of this website, not a developer. Explain to me in plain language, in the language '
                . 'I write in, what I can do on this website with you.',
                'First look around: list_resources for what I may see and edit, describe_resource for the pages '
                . '(entries) and their types, and find_records for a few recent entries, so your examples name real '
                . 'pages of this site. Which of your tools you can call tells you whether this connection may write '
                . 'or only read.',
                'Then answer briefly: what the site consists of (page types, sections, images, categories, '
                . 'languages), what I can ask you to do, with five to eight example requests that fit this site, '
                . 'and the rules: new content is saved as a draft until it is published, nothing is deleted, every '
                . 'change shows in the site\'s history. Say what is not possible this way: the design or layout, '
                . 'new languages, users and settings. Use no technical terms such as tool, resource or attribute.',
            ]));
    }

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
                'Find up to ' . ($arguments['limit'] ?? '20') . ' entries without a meta description in one of the '
                . 'content languages: find_records with resource "entry" and "missing" naming the description of each '
                . 'language (description, description_de, …, as describe_resource lists them).',
                'For each, read the entry and its sections (find_records, resource "section", filter {"entry_id": <id>}) '
                . 'and write a description of what the page offers, at most 160 characters, in every content language '
                . 'whose description is empty. Save it with update_record, passing the updated_at get_record answered.',
                'Finish with a list of the entries and their new descriptions.',
            ]));
    }
}
