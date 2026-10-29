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
     * Includes the {@see Permalink::LANGUAGE_ALL} records, which resolve under every language.
     */
    public function whereLanguage(?string $language = null): static
    {
        return $this->andWhere([
            $this->getTableAlias() . '.[[language]]' => [$language ?? Yii::$app->getI18n()->getContentLanguage(), Permalink::LANGUAGE_ALL],
        ]);
    }

    /**
     * A per-language record wins over the {@see Permalink::LANGUAGE_ALL} one when the same path exists as both.
     */
    public function whereUri(string $uri, ?string $language = null): static
    {
        $language ??= Yii::$app->getI18n()->getContentLanguage();
        $alias = $this->getTableAlias();

        return $this->whereLanguage($language)
            ->andWhere(["$alias.[[uri]]" => trim($uri, '/')])
            ->addOrderBy(new Expression("$alias.[[language]] = :permalinkLanguage DESC", [
                ':permalinkLanguage' => $language,
            ]));
    }
}
