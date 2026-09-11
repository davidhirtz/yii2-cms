<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Db\ActiveQuery;
use Yii;
use yii\db\Expression;

/**
 * @template T of Permalink
 * @extends ActiveQuery<T>
 */
class PermalinkQuery extends ActiveQuery
{
    /**
     * Matches permalinks that resolve under the given language: those stored for it, plus the language-agnostic
     * {@see Permalink::LANGUAGE_ALL} records that resolve under every language.
     */
    public function whereLanguage(?string $language = null): static
    {
        return $this->andWhere([
            $this->getTableAlias() . '.[[language]]' => [$language ?? Yii::$app->language, Permalink::LANGUAGE_ALL],
        ]);
    }

    /**
     * Matches the full path a request resolves to.
     *
     * A translated slug has one record per language; an untranslated one has a single {@see Permalink::LANGUAGE_ALL}
     * record that resolves under every language. The lookup accepts both, and a per-language record wins over the
     * language-agnostic fallback when the same path exists as both.
     */
    public function whereUri(string $uri, ?string $language = null): static
    {
        $language ??= Yii::$app->language;
        $alias = $this->getTableAlias();

        return $this->whereLanguage($language)
            ->andWhere(["$alias.[[uri]]" => trim($uri, '/')])
            ->addOrderBy(new Expression("$alias.[[language]] = :permalinkLanguage DESC", [
                ':permalinkLanguage' => $language,
            ]));
    }
}
