<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\models\actions;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\modules\ModuleTrait;
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

    public function replaceIndexEntry(): bool
    {
        if ($this->previous) {
            $this->replacePreviousIndexEntry();
        }

        $this->setEntryAttributes();

        return $this->entry->update() === 1;
    }

    protected function replacePreviousIndexEntry(): void
    {
        $this->generateUniqueSlugForPrevious();

        if ($this->disablePreviousIndex) {
            $this->previous->status = $this->previous::STATUS_DISABLED;
        }

        $this->previous->update();
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

    public static function run(array $params = []): static
    {
        $action = Yii::createObject(static::class, $params);
        $action->replaceIndexEntry();

        return $action;
    }
}
