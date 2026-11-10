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
use Hirtz\Skeleton\Models\CustomAttributes\LinkCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
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
            if (!$model instanceof CustomAttributeInterface) {
                continue;
            }

            foreach ($model->getCustomAttributeDefinitions() as $definition) {
                $names = $model instanceof I18nAttributeInterface && $definition->isTranslatable()
                    ? array_values($model->getI18nAttributeNames($definition->name))
                    : [$definition->name];

                foreach ($names as $name) {
                    $value = $model->$name;

                    if ($definition instanceof LinkCustomAttribute) {
                        $link = self::parse($value);

                        if ($link !== null) {
                            $ids[] = $link['id'];
                        }
                    } elseif ($definition instanceof HtmlCustomAttribute) {
                        $ids = [...$ids, ...self::findIds(is_string($value) ? $value : '')];
                    } elseif ($definition instanceof GroupCustomAttribute) {
                        $ids = [...$ids, ...self::findModelIds(array_values($model->getCustomAttributeItems($name)))];
                    }
                }
            }
        }

        return array_values(array_unique($ids));
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
    protected static function getAriaLabel(?Entry $entry, string $content, ?string $language): ?string
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
     * The entry picker, opening on the tenant of the record the link is made in; `null` for a user who may not open
     * it.
     *
     * @param list<int> $types
     * @return array<int|string, mixed>|null
     * @see \Hirtz\Cms\Modules\Admin\Controllers\EntryLinkController::actionIndex()
     */
    public static function getPickerRoute(Model $owner, array $types = [], bool $anyTenant = false, ?string $language = null): ?array
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

    public static function getPickerTitle(): string
    {
        return Yii::t('cms', 'ENTRY_LINK_PICKER_TITLE');
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
     * Name and URL, which tell apart the "Home" of several tenants and two "Contact" pages of one.
     */
    public static function getAdminLabel(Entry $entry, ?int $tenantId, ?string $language = null): string
    {
        $language ??= Yii::$app->getI18n()->getContentLanguage();

        return Yii::$app->getI18n()->callback($language, static function () use ($entry, $tenantId): string {
            $name = (string)$entry->getI18nAttribute('name', null, true) ?: Yii::t('cms', 'MODEL_UNTITLED_TEXT');
            return $name . ' — ' . (self::getDisplayUrl($entry, $tenantId) ?? $entry->getTypeName());
        });
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
