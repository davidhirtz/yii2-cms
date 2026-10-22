<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Throwable;
use Yii;

class ReplaceIndexEntry
{
    use ModuleTrait;

    public bool $disablePreviousIndex = true;

    public function __construct(
        protected Entry $entry,
        protected ?Entry $previous = null,
    ) {
    }

    /**
     * Without a homepage the site answers nothing, so the previous one keeps its place unless the entry takes it.
     */
    public function replaceIndexEntry(): bool
    {
        $transaction = $this->entry::getDb()->beginTransaction();

        try {
            if ($this->previous && !$this->replacePreviousIndexEntry()) {
                $transaction->rollBack();
                return false;
            }

            $this->setEntryAttributes();

            if ($this->entry->update() !== 1) {
                $transaction->rollBack();
                return false;
            }

            $transaction->commit();
            return true;
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    protected function replacePreviousIndexEntry(): bool
    {
        $this->generateUniqueSlugForPrevious();

        if ($this->disablePreviousIndex) {
            $this->previous->status = $this->previous::STATUS_DISABLED;
        }

        return $this->previous->update() !== false;
    }

    protected function generateUniqueSlugForPrevious(): void
    {
        foreach ($this->previous->getI18nAttributesNames('slug') as $attribute) {
            $this->previous->$attribute = $this->previous->$attribute . '-' . $this->previous->id;
        }

        $this->previous->generateUniqueSlug();
    }

    protected function setEntryAttributes(): void
    {
        foreach ($this->entry->getI18nAttributesNames('slug') as $attribute) {
            $this->entry->$attribute = static::getModule()->entryIndexSlug;
        }

        $this->entry->status = $this->entry::STATUS_ENABLED;
        $this->entry->type = $this->entry::TYPE_DEFAULT;
        $this->entry->parent_id = null;
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function run(array $params = []): static
    {
        // The container would otherwise build a blank entry for an omitted `previous`.
        $action = Yii::createObject(static::class, [...$params, 'previous' => $params['previous'] ?? null]);
        $action->replaceIndexEntry();

        return $action;
    }
}
