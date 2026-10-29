<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Actions;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockEntry;
use Hirtz\Cms\Models\Section;
use Hirtz\Media\Models\Actions\DuplicateAsset;
use Throwable;
use Yii;

/**
 * Everything rolls back when either record does not validate.
 */
class CreateBlockFromSection
{
    public readonly Block $block;

    public function __construct(protected Section $section)
    {
        $this->block = Block::instantiateByType($this->section->type);
        $this->block->loadDefaultValues();
    }

    public function run(): bool
    {
        if (!$this->section->allowsBlockConversion()) {
            $this->section->addInvalidAttributeError('type');
            return false;
        }

        $transaction = Section::getDb()->beginTransaction();

        try {
            if ($this->runInternal()) {
                $transaction->commit();
                return true;
            }

            $transaction->rollBack();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        return false;
    }

    protected function runInternal(): bool
    {
        $names = $this->copyAttributes();

        if (!$this->block->insert()) {
            return false;
        }

        foreach ($names as $name) {
            $this->block->getCustomAttribute($name)?->afterDuplicate($this->block, $this->section, $name);
        }

        $this->moveAssets();
        $this->moveEntryRelations();

        $this->section->type = (int)Section::findBlockSectionType()?->value;
        $this->section->populateBlockRelation($this->block);

        return (bool)$this->section->update();
    }

    /**
     * @return list<string>
     */
    protected function copyAttributes(): array
    {
        $this->block->status = $this->section->status;

        foreach ($this->block->getI18nAttributeNames('name') as $name) {
            if ($this->section->hasAttribute($name)) {
                $this->block->setAttribute($name, $this->section->getAttribute($name));
            }
        }

        $this->block->name = (string)$this->block->name ?: $this->section->getTypeName();

        $names = [];

        foreach ($this->block->getCustomAttributeNames() as $name) {
            if ($this->section->hasAttribute($name)) {
                $this->block->$name = $this->section->$name;
                $names[] = $name;
            }
        }

        return $names;
    }

    protected function moveAssets(): void
    {
        $position = 0;

        foreach ($this->section->getAssets()->all() as $asset) {
            DuplicateAsset::create([
                'asset' => $asset,
                'model' => $this->block,
                'shouldUpdateModelAfterInsert' => false,
                'attributes' => [
                    'status' => $asset->status,
                    'position' => ++$position,
                ],
            ]);

            $asset->setIsBatch(true);
            $asset->delete();
        }

        if ($position) {
            $this->block->updateAssetCount();
            $this->section->updateAssetCount();
        }
    }

    protected function moveEntryRelations(): void
    {
        $position = 0;

        foreach ($this->section->getEntryRelations()->all() as $relation) {
            $blockEntry = BlockEntry::create();
            $blockEntry->populateEntryRelation($relation->entry);
            $blockEntry->populateModelRelation($this->block);
            $blockEntry->setIsBatch(true);
            $blockEntry->position = ++$position;
            $blockEntry->insert();

            $relation->populateModelRelation($this->section);
            $relation->setIsBatch(true);
            $relation->delete();
        }

        if ($position) {
            $this->block->updateEntryCount();
            $this->section->updateEntryCount();
        }
    }

    /**
     * @return array<string, string>
     */
    public function getFirstErrors(): array
    {
        return [...$this->block->getFirstErrors(), ...$this->section->getFirstErrors()];
    }

    public static function create(Section $section): static
    {
        $action = Yii::createObject(static::class, [$section]);
        $action->run();

        return $action;
    }
}
