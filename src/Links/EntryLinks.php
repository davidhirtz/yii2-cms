<?php

declare(strict_types=1);

namespace Hirtz\Cms\Links;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Queries\EntryQuery;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttributeGroupItem;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Cms\Models\CustomAttributes\LinkCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Widgets\Forms\Fields\LinkField;
use Hirtz\Skeleton\Web\User as WebUser;
use Hirtz\Tenant\Models\Collections\TenantCollection;
use Normalizer;
use Yii;
use yii\base\Model;
use yii\helpers\Html;

/**
 * The statics outlive the application and are reset from the cms `Bootstrap`.
 */
class EntryLinks
{
    use ModuleTrait;

    final public const string SCHEME = 'entry';

    private const string HTML_PATTERN = '#<a\b([^>]*?)\bhref="entry:(\d+)(\#[^"]*)?"([^>]*)>(.*?)</a>#su';

    /**
     * `null` for an id asked for and found unreachable, so rendering never asks again.
     *
     * @var array<int, Entry|null>
     */
    private static array $entries = [];

    /**
     * Any status, for the admin's labels.
     *
     * @var array<int, Entry|null>
     */
    private static array $adminEntries = [];

    public static function reset(): void
    {
        self::$entries = [];
        self::$adminEntries = [];
    }

    /**
     * @return array{id: int, fragment: string}|null
     */
    public static function parse(mixed $value): ?array
    {
        return is_string($value) && preg_match('/^entry:([1-9]\d*)(#\S*)?$/', $value, $matches)
            ? ['id' => (int)$matches[1], 'fragment' => $matches[2] ?? '']
            : null;
    }

    /**
     * @return list<int>
     */
    public static function findIds(string $html): array
    {
        if (!str_contains($html, 'entry:')) {
            return [];
        }

        preg_match_all('/href="entry:([1-9]\d*)(?:#[^"]*)?"/', $html, $matches);
        return array_values(array_unique(array_map(intval(...), $matches[1])));
    }

    /**
     * The ids every language of the models' link and HTML custom attributes names, group rows included.
     *
     * @param list<Model> $models
     * @return list<int>
     */
    public static function findModelIds(array $models): array
    {
        $ids = [];

        foreach ($models as $model) {
            $ids = [...$ids, ...array_keys(self::getReferenceCounts($model))];
        }

        return array_values(array_unique($ids));
    }

    /**
     * How often each attribute of the model names an entry, in every language; nested names are joined by `.`.
     *
     * @return array<int, array<string, int>>
     */
    public static function getReferenceCounts(Model $model): array
    {
        if (!$model instanceof CustomAttributeInterface) {
            return [];
        }

        $counts = [];

        foreach ($model->getCustomAttributeDefinitions() as $definition) {
            foreach ($definition->getAttributeNames($model) as $name) {
                $value = $model->$name;

                if ($definition instanceof LinkCustomAttribute) {
                    $link = $definition->allowsEntries() ? self::parse($value) : null;

                    if ($link !== null) {
                        $counts[$link['id']][$name] = ($counts[$link['id']][$name] ?? 0) + 1;
                    }
                } elseif ($definition instanceof HtmlCustomAttribute) {
                    if (is_string($value) && str_contains($value, 'entry:')) {
                        preg_match_all('/href="entry:([1-9]\d*)(?:#[^"]*)?"/', $value, $matches);

                        foreach ($matches[1] as $id) {
                            $counts[(int)$id][$name] = ($counts[(int)$id][$name] ?? 0) + 1;
                        }
                    }
                } elseif ($definition instanceof GroupCustomAttribute) {
                    foreach ($model->getCustomAttributeItems($name) as $item) {
                        foreach (self::getReferenceCounts($item) as $id => $itemCounts) {
                            foreach ($itemCounts as $itemName => $count) {
                                $counts[$id]["$name.$itemName"] = ($counts[$id]["$name.$itemName"] ?? 0) + $count;
                            }
                        }
                    }
                }
            }
        }

        foreach ($counts as &$attributeCounts) {
            ksort($attributeCounts);
        }

        return $counts;
    }

    /**
     * What a preload already loaded. An id mapped to `null` is known to be unreachable.
     *
     * @param array<int, Entry|null> $entries
     */
    public static function register(array $entries): void
    {
        foreach ($entries as $id => $entry) {
            self::$entries[$entry->id ?? $id] = $entry;
        }
    }

    /**
     * For models rendered outside a preload, such as a teaser list: one query for every link they hold.
     *
     * @param list<Model> $models
     */
    public static function preload(array $models): void
    {
        self::load(self::findModelIds($models));
    }

    /**
     * @param list<int> $ids
     */
    public static function load(array $ids): void
    {
        $ids = array_values(array_diff(array_unique($ids), array_keys(self::$entries)));

        if (!$ids) {
            return;
        }

        $entries = static::getEntryQuery()
            ->andWhere([Entry::tableName() . '.[[id]]' => $ids])
            ->all();

        foreach ($ids as $id) {
            self::$entries[$id] = $entries[$id] ?? null;
        }
    }

    /**
     * The status conditions of the frontend, so a disabled, draft or scheduled target, or one under such a parent,
     * never loads.
     *
     * @return EntryQuery<Entry>
     */
    public static function getEntryQuery(): EntryQuery
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->withTranslations()
            ->withPermalinks()
            ->whereStatus()
            ->andWhereParentStatus()
            ->indexBy('id');
    }

    /**
     * The target, loaded if need be; `null` when it cannot be reached.
     */
    public static function getEntry(int $id): ?Entry
    {
        self::load([$id]);
        return self::$entries[$id] ?? null;
    }

    /**
     * @param list<int> $ids
     * @return array<int, string|null>
     */
    public static function getUrls(array $ids, ?string $language = null): array
    {
        self::load($ids);

        $urls = [];

        foreach ($ids as $id) {
            $entry = self::$entries[$id] ?? null;
            $urls[$id] = $entry ? self::createUrl($entry, $language) : null;
        }

        return $urls;
    }

    /**
     * A URL passes through, `entry:12#anchor` becomes the target's URL or `null`.
     */
    public static function getUrl(mixed $value, ?string $language = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $link = self::parse($value);

        if ($link === null) {
            return is_string($value) && !str_starts_with($value, self::SCHEME . ':') ? $value : null;
        }

        $url = self::getUrls([$link['id']], $language)[$link['id']];
        return $url !== null ? $url . $link['fragment'] : null;
    }

    /**
     * Every `entry:` link gets its URL, or is unwrapped to its text when the target cannot be reached.
     */
    public static function replaceHtml(string $html, ?string $language = null): string
    {
        $ids = self::findIds($html);

        if (!$ids) {
            return $html;
        }

        $urls = self::getUrls($ids, $language);
        $labels = static::getModule()->entryLinkAriaLabels;

        return (string)preg_replace_callback(self::HTML_PATTERN, static function (array $matches) use ($urls, $labels, $language): string {
            [, $before, $id, $fragment, $after, $content] = $matches;
            $url = $urls[(int)$id] ?? null;

            if ($url === null) {
                return $content;
            }

            $attributes = $before . 'href="' . Html::encode($url . $fragment) . '"' . $after;

            if ($labels && !str_contains($attributes, 'aria-label=')) {
                $label = self::getAriaLabel(self::$entries[(int)$id] ?? null, $content, $language);

                if ($label !== null) {
                    $attributes .= ' aria-label="' . Html::encode($label) . '"';
                }
            }

            return "<a$attributes>$content</a>";
        }, $html);
    }

    /**
     * Only for text that does not already name the target, such as "Read more". The visible text stays first, so a
     * voice-control user can say what they see (WCAG 2.5.3).
     */
    public static function getAriaLabel(?Entry $entry, string $content, ?string $language): ?string
    {
        if ($entry === null) {
            return null;
        }

        $language ??= Yii::$app->language;

        return Yii::$app->getI18n()->callback($language, static function () use ($entry, $content): ?string {
            $name = self::normalizeText((string)$entry->getI18nAttribute('name'));

            if ($name === '') {
                return null;
            }

            $text = self::normalizeText(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($text === '') {
                return $name;
            }

            return mb_stripos($text, $name) === false
                ? Yii::t('cms', 'ENTRY_LINK_ARIA_LABEL_FORMAT', ['text' => $text, 'name' => $name])
                : null;
        });
    }

    /**
     * The index needs no permalink; every other entry needs a route and a permalink in the language or for all of
     * them. An entry of a disabled tenant is not reachable.
     */
    protected static function createUrl(Entry $entry, ?string $language): ?string
    {
        if (TenantCollection::getById($entry->tenant_id) === null) {
            return null;
        }

        $language ??= Yii::$app->language;

        return Yii::$app->getI18n()->callback($language, static function () use ($entry): ?string {
            // `getPermalink()` reads the permalink a request matched the page by, so a link to the page itself costs no
            // query; unlike `getFormattedSlug()` it does not fall back to another language.
            if (!$entry->isIndex() && (!$entry->hasRoute() || $entry->getPermalink() === null)) {
                return null;
            }

            $route = $entry->getRoute();
            return $route !== false ? Yii::$app->getUrlManager()->createUrl($route) : null;
        });
    }

    private static function normalizeText(string $text): string
    {
        $text = trim((string)preg_replace('/\s+/u', ' ', $text));

        return class_exists(Normalizer::class)
            ? (string)Normalizer::normalize($text, Normalizer::FORM_C)
            : $text;
    }

    /**
     * Entries suggested on the tenant of the record the link is made in; `null` for a user who may not open them.
     *
     * @param list<int> $types
     * @return array<int|string, mixed>|null
     * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryLinkController::actionIndex()
     */
    public static function getSuggestionRoute(Model $owner, array $types = [], bool $anyTenant = false, ?string $language = null): ?array
    {
        $user = WebUser::current();

        if (!$user?->can(Entry::AUTH_ENTRY) && !$user?->can(Block::AUTH_BLOCK)) {
            return null;
        }

        $tenantId = self::findTenantId($owner);

        return array_filter([
            '/admin/cms/entry-link/index',
            'tenant' => $tenantId,
            'types' => $types ? implode(',', $types) : null,
            'anyTenant' => $anyTenant || $tenantId === null ? 1 : null,
            'language' => $language,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * The tenant of the entry a record belongs to; `null` for a block, which any tenant shows.
     */
    public static function findTenantId(Model $owner): ?int
    {
        $model = $owner;

        while ($model !== null) {
            if ($model instanceof CustomAttributeGroupItem) {
                $model = $model->getOwner();
                continue;
            }

            if ($model instanceof Entry) {
                return $model->tenant_id;
            }

            if ($model instanceof Block || !$model instanceof AdminModelInterface) {
                return null;
            }

            $parent = $model->getAdminParent();
            $model = $parent instanceof Model ? $parent : null;
        }

        return null;
    }

    public static function findAdminEntry(int $id): ?Entry
    {
        if (!array_key_exists($id, self::$adminEntries)) {
            self::$adminEntries[$id] = Entry::find()
                ->withTranslations()
                ->withPermalinks()
                ->where([Entry::tableName() . '.[[id]]' => $id])
                ->one();
        }

        return self::$adminEntries[$id];
    }

    /**
     * Suggests entries from `$route` and shows a picked one by its name, in a link attribute and in the editor's dialog.
     *
     * @param array<int|string, mixed>|null $route
     */
    public static function configureField(LinkField $field, mixed $value, ?array $route, ?int $tenantId, ?string $language): LinkField
    {
        if ($route !== null) {
            $field->url($route);

            if ($field->getAttribute('placeholder') === null) {
                $field->placeholder(Yii::t('cms', 'ENTRY_LINK_PLACEHOLDER'));
            }
        }

        $link = self::parse($value);

        if ($link === null) {
            return $field;
        }

        $entry = self::findAdminEntry($link['id']);

        return $entry
            ? $field->selected(
                self::getAdminName($entry, $language),
                self::getAdminDescription($entry, $tenantId, $language),
                self::getAdminUrl($entry),
            )
            : $field->selected(Yii::t('cms', 'ENTRY_LINK_MISSING_TEXT'), self::SCHEME . ':' . $link['id']);
    }

    /**
     * The editor's link dialog, on the tenant of the record the HTML belongs to.
     *
     * @return array<int|string, mixed>|null
     * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryLinkController::actionDialog()
     */
    public static function getDialogRoute(Model $owner): ?array
    {
        $route = self::getSuggestionRoute($owner);

        if ($route === null) {
            return null;
        }

        $route[0] = '/admin/cms/entry-link/dialog';
        return $route;
    }

    public static function getAdminName(Entry $entry, ?string $language = null): string
    {
        $name = $entry->getI18nAttribute('name', $language ?? Yii::$app->getI18n()->getContentLanguage(), true);
        return (string)$name ?: Yii::t('cms', 'MODEL_UNTITLED_TEXT');
    }

    /**
     * The URL, which tells apart the "Home" of several tenants and two "Contact" pages of one; the type without one.
     */
    public static function getAdminDescription(Entry $entry, ?int $tenantId, ?string $language = null): string
    {
        $language ??= Yii::$app->getI18n()->getContentLanguage();

        return Yii::$app->getI18n()->callback(
            $language,
            static fn (): string => self::getDisplayUrl($entry, $tenantId) ?? $entry->getTypeName(),
        );
    }

    /**
     * The entry's admin page, `null` for a user who may not open it.
     */
    public static function getAdminUrl(Entry $entry): ?string
    {
        $route = $entry->getAdminRoute();
        return $route && WebUser::current()?->can(Entry::AUTH_ENTRY) ? Url::to($route) : null;
    }

    /**
     * Relative on the given tenant's host, with the host on another.
     */
    public static function getDisplayUrl(Entry $entry, ?int $tenantId): ?string
    {
        $route = $entry->getRoute();

        if ($route === false) {
            return null;
        }

        $url = Yii::$app->getUrlManager()->createAbsoluteUrl($route);
        $hostInfo = TenantCollection::getById($tenantId)?->getHostInfo();

        if ($hostInfo && str_starts_with($url, $hostInfo . '/')) {
            return substr($url, strlen($hostInfo));
        }

        return (string)preg_replace('#^[a-z]+://#i', '', $url);
    }
}
