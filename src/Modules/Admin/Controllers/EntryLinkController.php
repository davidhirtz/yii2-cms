<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Links\EntryLinks;
use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Widgets\Forms\AutocompleteList;
use Hirtz\Skeleton\Widgets\Forms\Fields\LinkField;
use Hirtz\Skeleton\Widgets\Forms\LinkDialog;
use Override;
use Yii;
use yii\filters\AccessControl;

/**
 * @see \Hirtz\Cms\Links\EntryLinks::getSuggestionRoute()
 */
class EntryLinkController extends AbstractController
{
    public int $limit = 20;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['dialog', 'index'],
                        'roles' => [Entry::AUTH_ENTRY, Block::AUTH_BLOCK],
                    ],
                ],
            ],
        ];
    }

    /**
     * Answers nothing for what is a URL already (a scheme, an anchor, a query), so the typed value stands without a list
     * in its way; a path starting with `/` is matched against the permalinks, anything else against the names.
     */
    public function actionIndex(
        ?string $q = null,
        ?int $tenant = null,
        ?string $types = null,
        ?int $anyTenant = null,
        ?string $language = null,
    ): string {
        $q = trim((string)$q);

        if ($q === '' || preg_match('#^([a-z][a-z\d+.-]*:|[?\#])#i', $q)) {
            return '';
        }

        if ($language !== null && !Yii::$app->getI18n()->hasLanguage($language)) {
            $language = null;
        }

        $tenantId = $this->getTenantId($tenant);
        $query = Entry::find()
            ->withTranslations()
            ->withPermalinks()
            ->limit($this->limit);

        if ($types) {
            $query->andWhere([Entry::tableName() . '.[[type]]' => array_map(intval(...), explode(',', $types))]);
        }

        if (!$anyTenant && $tenantId !== null) {
            $query->andWhere([Entry::tableName() . '.[[tenant_id]]' => $tenantId]);
        }

        if (str_starts_with($q, '/')) {
            $query->andWhere(['in', Entry::tableName() . '.[[id]]', Permalink::find()
                ->select('entry_id')
                ->where(['like', 'uri', $this->getPath($q) . '%', false])]);
        } else {
            Yii::$app->getI18n()->callback(
                $language ?? Yii::$app->getI18n()->getContentLanguage(),
                static fn () => $query->matching($q),
            );
        }

        $options = [];

        foreach ($query->all() as $entry) {
            $options[] = [
                'text' => EntryLinks::getAdminName($entry, $language),
                'value' => EntryLinks::SCHEME . ':' . $entry->id,
                'description' => EntryLinks::getAdminDescription($entry, $tenantId, $language),
                'url' => EntryLinks::getAdminUrl($entry),
            ];
        }

        return $options ? (string)AutocompleteList::make()->options($options) : '';
    }

    /**
     * The editor's link dialog, filled with the link it edits.
     */
    public function actionDialog(
        ?int $tenant = null,
        ?int $anyTenant = null,
        ?string $language = null,
        ?string $href = null,
        ?string $text = null,
        ?string $title = null,
        ?string $target = null,
        ?string $class = null,
        ?string $classes = null,
    ): string {
        if ($language !== null && !Yii::$app->getI18n()->hasLanguage($language)) {
            $language = null;
        }

        $tenantId = $this->getTenantId($tenant);

        $field = EntryLinks::configureField(LinkField::make(), $href, array_filter([
            '/admin/cms/entry-link/index',
            'tenant' => $tenantId,
            'anyTenant' => $anyTenant ? 1 : null,
            'language' => $language,
        ], static fn (mixed $value): bool => $value !== null), $tenantId, $language);

        return $this->renderContent(LinkDialog::make()
            ->field($field)
            ->href($href)
            ->text($text)
            ->title($title)
            ->blank($target === '_blank')
            ->cssClass($class)
            ->classes($this->getClasses($classes))
            ->render());
    }

    /**
     * @return array<string, string>
     */
    protected function getClasses(?string $classes): array
    {
        $decoded = json_decode((string)$classes, true);
        $result = [];

        foreach (is_array($decoded) ? $decoded : [] as $value => $label) {
            if (is_string($label)) {
                $result[(string)$value] = $label;
            }
        }

        return $result;
    }

    /**
     * Without the leading slash and a language prefix, which the permalinks do not store.
     */
    protected function getPath(string $q): string
    {
        $segments = explode('/', ltrim($q, '/'), 2);

        if (count($segments) === 2 && Yii::$app->getI18n()->hasLanguage($segments[0])) {
            return addcslashes($segments[1], '%_\\');
        }

        return addcslashes(ltrim($q, '/'), '%_\\');
    }
}
