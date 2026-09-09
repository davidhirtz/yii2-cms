<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Queries;

use Hirtz\Cms\Models\Interfaces\PermalinkInterface;
use Hirtz\Cms\Models\Permalink;
use Hirtz\Skeleton\Db\ActiveQuery;
use Yii;
use yii\db\ActiveRecordInterface;

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
     * Matches the full path a request resolves to. Hits the unique `(language, uri)` index.
     */
    public function whereUri(string $uri, ?string $language = null): static
    {
        return $this->whereLanguage($language)
            ->andWhere([$this->getTableAlias() . '.[[uri]]' => trim($uri, '/')]);
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
