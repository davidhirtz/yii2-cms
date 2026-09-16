<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Cms\Models\Queries\BlockQuery;
use Hirtz\Cms\Models\Queries\SectionQuery;
use Hirtz\Cms\Models\Traits\EntryRelationModelTrait;
use Hirtz\Cms\Models\Types\BlockType;
use Hirtz\Media\Models\Interfaces\AssetModelInterface;
use Hirtz\Media\Models\Traits\AssetModelTrait;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;
use Hirtz\Skeleton\Models\Traits\TranslatableAttributesTrait;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Yii;

/**
 * A section with no owner: the same types, assets, linked entries and custom attributes, but no tenant and no
 * entry. A section carrying a `block_id` renders it in place of its own content.
 *
 * @property string $name
 * @property string|null $content
 * @property int $section_count
 * @property int $asset_count
 * @property int $entry_count
 *
 * @property-read BlockAsset[] $assets {@see static::getAssets()}
 * @property-read Section[] $sections {@see static::getSections()}
 */
class Block extends ActiveRecord implements AssetModelInterface, EntryRelationModelInterface, SearchableInterface
{
    use AssetModelTrait;
    use EntryRelationModelTrait;
    use SearchableTrait;
    use TranslatableAttributesTrait;

    final public const string AUTH_BLOCK = 'block';

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getI18nRules([
                [
                    ['name'],
                    'required',
                ],
                [
                    ['name'],
                    'trim',
                ],
                [
                    ['name'],
                    'string',
                    'max' => 250,
                ],
            ]),
        ];
    }

    /**
     * @return list<CustomAttribute>
     */
    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            ...$this->getDefaultCustomAttributes(),
            ...parent::getCustomAttributes(),
        ];
    }

    /**
     * @return list<CustomAttribute>
     */
    protected function getDefaultCustomAttributes(): array
    {
        return [
            HtmlCustomAttribute::make('content')
                ->label(Yii::t('cms', 'MODEL_CONTENT_LABEL'))
                ->translatable($this->isTranslatableAttribute('content')),
        ];
    }

    #[Override]
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        if ($this->asset_count) {
            foreach ($this->assets as $asset) {
                $asset->setIsBatch($this->getIsBatch());
                $asset->delete();
            }
        }

        if ($this->entry_count) {
            $this->deleteEntryRelations();
        }

        return true;
    }

    /**
     * @return BlockQuery<static>
     */
    #[Override]
    public static function find(): BlockQuery
    {
        return Yii::createObject(BlockQuery::class, [static::class]);
    }

    /**
     * A block belongs to nothing, so it has no `position` and nothing to be ordered within — the base needs the
     * method for {@see \Hirtz\Cms\Models\ActiveRecord::getMaxPosition()}, which is never reached here.
     *
     * @return BlockQuery<static>
     */
    public function findSiblings(): BlockQuery
    {
        return static::find();
    }

    public function recalculateSectionCount(): static
    {
        $this->section_count = (int)$this->getSections()->count();
        return $this;
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function getTrailAttributes(): array
    {
        return array_values(array_diff(parent::getTrailAttributes(), ['section_count']));
    }

    /**
     * The sections that place this block. There is no count column for them: a block is edited on its own page,
     * where the number only matters when it is about to be deleted.
     *
     * @return SectionQuery<Section>
     */
    public function getSections(): SectionQuery
    {
        /** @var SectionQuery<Section> $relation */
        $relation = $this->hasMany(Section::class, ['block_id' => 'id']);
        return $relation;
    }

    public function getEntryRelationClass(): string
    {
        return BlockEntry::class;
    }

    public function getAssetClass(): string
    {
        return BlockAsset::class;
    }

    public function allowsAssets(): bool
    {
        return static::getModule()->enableBlockAssets && $this->typeAllowsAssets();
    }

    public function allowsEntries(): bool
    {
        return static::getModule()->enableBlockEntries && $this->typeAllowsEntries();
    }

    public function getViewFile(): ?string
    {
        return $this->getType()?->getViewFile();
    }

    /**
     * @return list<BlockAsset>
     */
    public function getVisibleAssets(): array
    {
        return $this->allowsAssets() ? array_values($this->assets) : [];
    }

    public function getAdminType(): string
    {
        return $this->getTypeName() ?: Yii::t('cms', 'COMMON_BLOCK');
    }

    public function getAdminRoute(): array
    {
        return $this->id ? ['/admin/cms/block/update', 'id' => $this->id] : ['/admin/cms/block/index'];
    }

    /**
     * `content` has no column of its own: it is indexed only where the project declares it as a custom attribute.
     */
    public function getSearchAttributes(): array
    {
        return ['name', 'content'];
    }

    public function getSearchWeight(): float
    {
        return 0.6;
    }

    protected function isSearchResultVisible(): bool
    {
        return WebUser::current()?->can(static::AUTH_BLOCK) ?? false;
    }

    /**
     * A block has no URL of its own — it is rendered wherever a section points at it.
     *
     * @return array<int|string, mixed>|false
     */
    public function getRoute(): array|false
    {
        return false;
    }

    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    #[Override]
    public static function getTypeClass(): string
    {
        return BlockType::class;
    }

    #[Override]
    public function getType(): ?BlockType
    {
        /** @var BlockType|null */
        return static::findType(static::normalizeTypeValue($this->type ?? null));
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'name' => Yii::t('cms', 'BLOCK_NAME_LABEL'),
            'section_count' => Yii::t('cms', 'BLOCK_SECTION_COUNT_LABEL'),
            'entry_count' => Yii::t('cms', 'BLOCK_ENTRY_COUNT_LABEL'),
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Block';
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%block}}';
    }
}
