<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use Hirtz\Cms\Models\Interfaces\PermalinkInterface;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Db\ActiveQuery;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\db\Expression;

/**
 * @template T of Permalink
 * @extends ActiveQuery<T>
 */
class PermalinkQuery extends ActiveQuery
{
    public function whereLanguage(?string $language = null): static
    {
        return $this->andWhere([
            $this->getTableAlias() . '.[[language]]' => $language ?? Yii::$app->language,
        ]);
    }

    /**
     * Matches the full path a request resolves to, hitting the unique `(language, uri)` index.
     *
     * A model whose slug is not translated has a single record under the source language rather than one per
     * language, so the lookup accepts that too — otherwise such a URL would only resolve while the source language
     * was active. The same fallback covers a language added after the records were written. An exact match still
     * wins when both exist.
     */
    public function whereUri(string $uri, ?string $language = null): static
    {
        $language ??= Yii::$app->language;
        $alias = $this->getTableAlias();

        $languages = array_values(array_unique([$language, Yii::$app->sourceLanguage]));

        $this->andWhere([
            "$alias.[[uri]]" => trim($uri, '/'),
            "$alias.[[language]]" => $languages,
        ]);

        return count($languages) > 1
            ? $this->addOrderBy(new Expression("$alias.[[language]] = :permalinkLanguage DESC", [
                ':permalinkLanguage' => $language,
            ]))
            : $this;
    }

    public function whereModel(PermalinkInterface|string $model, ?int $modelId = null): static
    {
        if ($model instanceof PermalinkInterface) {
            $modelId ??= $model instanceof ActiveRecordInterface ? $model->getPrimaryKey() : null;
            $model = $model->getPermalinkModelClass();
        }

        $alias = $this->getTableAlias();

        return $this->andWhere([
            "$alias.[[model]]" => $model,
            "$alias.[[model_id]]" => $modelId,
        ]);
    }
}
