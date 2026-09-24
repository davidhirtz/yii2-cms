<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models;

use Closure;
use Hirtz\Cms\Models\Actions\UpdateBlockSectionCounts;
use Hirtz\Cms\Models\CustomAttributes\SlugCustomAttribute;
use Hirtz\Cms\Models\Interfaces\EntryRelationModelInterface;
use Hirtz\Cms\Models\Queries\BlockQuery;
use Hirtz\Cms\Models\Queries\SectionQuery;
use Hirtz\Cms\Models\Traits\EntryRelationModelTrait;
use Hirtz\Cms\Models\Traits\EntryRelationTrait;
use Hirtz\Cms\Models\Types\SectionType;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\Interfaces\AssetModelInterface;
use Hirtz\Media\Models\Traits\AssetModelTrait;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;
use Hirtz\Skeleton\Models\Traits\TranslatableAttributesTrait;
use Hirtz\Skeleton\Search\SearchText;
use Hirtz\Skeleton\Validators\RelationValidator;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Yii;

/**
 * @property int $entry_id
 * @property int|null $block_id
 * @property int $position
 * @property string|null $name
 * @property string|null $slug
 * @property string|null $content
 * @property int $asset_count
 * @property int $entry_count
 *
 * @property-read SectionAsset[] $assets {@see static::getAssets()}
 * @property-read Block|null $block {@see static::getBlock()}
 */
class Section extends ActiveRecord implements AssetModelInterface, EntryRelationModelInterface, SearchableInterface
{
    use AssetModelTrait;
    use EntryRelationModelTrait;
    use EntryRelationTrait;
    use SearchableTrait;
    use TranslatableAttributesTrait;

    final public const int SLUG_MAX_LENGTH = 100;

    public bool|null $shouldUpdateEntryAfterSave = null;

    /**
     * @var list<TrailModelInterface>|null
     */
    private ?array $trailParents = null;

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getI18nRules([
            [
                ['entry_id'],
                RelationValidator::class,
                'required' => true,
            ],
            [
                ['entry_id'],
                $this->validateEntryId(...),
            ],
            // a validator reads the attribute, and a project may leave `slug` out
            ...($this->hasAttribute('slug') ? [[['slug'], $this->validateSlug(...)]] : []),
            [
                ['block_id'],
                RelationValidator::class,
            ],
            [
                ['block_id'],
                $this->validateBlockId(...),
            ],
        ])];
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
            TextCustomAttribute::make('name')
                ->label(Yii::t('cms', 'MODEL_NAME_LABEL'))
                ->translatable($this->isTranslatableAttribute('name')),
            HtmlCustomAttribute::make('content')
                ->label(Yii::t('cms', 'MODEL_CONTENT_LABEL'))
                ->translatable($this->isTranslatableAttribute('content')),
            SlugCustomAttribute::make('slug')
                ->max(self::SLUG_MAX_LENGTH)
                ->label(Yii::t('cms', 'SECTION_SLUG_LABEL'))
                ->translatable($this->isTranslatableAttribute('slug')),
        ];
    }

    #[Override]
    public function safeAttributes(): array
    {
        return array_diff(parent::safeAttributes(), ['entry_id']);
    }

    /**
     * The slug is the section's HTML id, so it only has to be unique among the sections of its entry.
     */
    protected function validateSlug(string $attribute): void
    {
        if ($this->$attribute && in_array($this->$attribute, $this->findSiblingSlugs($attribute), true)) {
            $this->addError($attribute, Yii::t('yii', '{attribute} "{value}" has already been taken.', [
                'attribute' => $this->getAttributeLabel($attribute),
                'value' => $this->$attribute,
            ]));
        }
    }

    public function generateUniqueSlug(): void
    {
        foreach ($this->getI18nAttributeNames('slug') as $name) {
            $slug = $this->getAttribute($name);

            if (!$slug) {
                continue;
            }

            $slugs = $this->findSiblingSlugs($name);

            for ($i = 1; $i < 100 && in_array($this->getAttribute($name), $slugs, true); $i++) {
                $length = self::SLUG_MAX_LENGTH - 1 - (int)ceil($i / 10);
                $this->setAttribute($name, mb_substr((string)$slug, 0, $length) . '-' . $i);
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function findSiblingSlugs(string $attribute): array
    {
        $slugs = [];

        foreach ($this->findSiblings()->all() as $section) {
            if ($section->id !== $this->id && ($slug = $section->getAttribute($attribute))) {
                $slugs[] = (string)$slug;
            }
        }

        return $slugs;
    }

    public function validateEntryId(): void
    {
        if (!$this->entry->allowsSections()) {
            $this->addInvalidAttributeError('entry_id');
        }
    }

    public function validateBlockId(): void
    {
        if ($this->block_id && !$this->allowsBlock()) {
            $this->addInvalidAttributeError('block_id');
        }
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        $this->shouldUpdateEntryAfterSave ??= !$this->getIsBatch();

        // Handle section move / clone, inserts will be handled by parent implementation
        if (!$insert && $this->isAttributeChanged('entry_id')) {
            $this->position = $this->getMaxPosition() + 1;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @param array<string, mixed> $changedAttributes
     */
    #[Override]
    public function afterSave($insert, $changedAttributes): void
    {
        if ($this->shouldUpdateEntryAfterSave) {
            if (array_key_exists('entry_id', $changedAttributes)) {
                $this->updateOldEntryRelation($changedAttributes['entry_id'] ?? null);
                $this->entry->updateSectionCount();
            }

            // A changed section is a changed entry, whether or not the count moved with it.
            if ($changedAttributes) {
                $this->entry->updateAttributes(['updated_at' => $this->updated_at]);
            }
        }

        if (!$this->getIsBatch()) {
            (new UpdateBlockSectionCounts([$this->block_id, $changedAttributes['block_id'] ?? null]))->update();
        }

        parent::afterSave($insert, $changedAttributes);
    }

    #[Override]
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Unconditional: an entry deletion no longer sweeps the section assets through a shared column.
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

    #[Override]
    public function afterDelete(): void
    {
        if (!$this->getIsBatch()) {
            if (!$this->entry->isDeleted()) {
                $this->entry->updateSectionCount();
            }

            (new UpdateBlockSectionCounts([$this->block_id]))->update();
        }

        parent::afterDelete();
    }

    /**
     * @return SectionQuery<static>
     */
    public function findSiblings(): SectionQuery
    {
        return static::find()->where(['entry_id' => $this->entry_id]);
    }

    /**
     * @return SectionQuery<static>
     */
    #[Override]
    public static function find(): SectionQuery
    {
        return Yii::createObject(SectionQuery::class, [static::class]);
    }

    public function getEntryRelationClass(): string
    {
        return SectionEntry::class;
    }

    /**
     * @return BlockQuery<Block>
     */
    public function getBlock(): BlockQuery
    {
        /** @var BlockQuery<Block> $relation */
        $relation = $this->hasOne(Block::class, ['id' => 'block_id']);
        return $relation;
    }

    public function populateBlockRelation(?Block $block): void
    {
        $this->populateRelation('block', $block);
        $this->block_id = $block?->id;
    }

    /**
     * The block a section of this type actually carries, or null — a `block_id` left over from a type that no
     * longer allows one renders nothing.
     */
    public function getVisibleBlock(): ?Block
    {
        return $this->allowsBlock() ? $this->block : null;
    }

    /**
     * Updates the old entry relation after the section was moved to another entry. Override this method if the old
     * entry should be further manipulated after the section's entry was changed.
     */
    protected function updateOldEntryRelation(?int $entryId): void
    {
        $entry = Entry::findOne($entryId);

        if ($entry) {
            $entry->updateSectionCount();
            $this->trailParents = [$entry, $this->entry];
        }
    }

    /**
     * @return list<TrailModelInterface>
     */
    public function getTrailParents(): array
    {
        return $this->trailParents ?? [$this->entry];
    }

    #[Override]
    public function getAdminType(): string
    {
        return Yii::t('cms', 'COMMON_SECTION');
    }

    #[Override]
    protected function getDefaultAdminIcon(): ?string
    {
        return 'th-list';
    }

    public function getGridContent(): ?string
    {
        $content = $this->getType()?->getGridContent();
        return $content instanceof Closure ? $content($this) : $content;
    }

    public function getAdminRoute(): array|false
    {
        return $this->id ? ['/admin/cms/section/update', 'id' => $this->id] : false;
    }

    #[Override]
    public function getAdminParent(): Entry
    {
        return $this->entry;
    }

    #[Override]
    public function getAdminSubtitle(): string
    {
        return $this->getAdminPositionLabel(total: $this->entry->section_count);
    }

    #[Override]
    public function getAdminIndexBreadcrumb(): Breadcrumb
    {
        return new Breadcrumb(Yii::t('cms', 'COMMON_SECTIONS'), [
            '/admin/cms/section/index',
            'entry' => $this->entry_id,
        ]);
    }

    public function getPermissionName(): string
    {
        return Entry::AUTH_ENTRY;
    }

    public function getSearchAttributes(): array
    {
        return ['name', 'content'];
    }

    public function getSearchWeight(): float
    {
        return 0.7;
    }

    public function getSearchTenantId(): ?int
    {
        return $this->entry?->tenant_id;
    }

    /**
     * @return SectionQuery<static>
     */
    public static function findSearchable(): SectionQuery
    {
        return static::find()->with('entry');
    }

    /**
     * Not the entry's name: with the title boost, that would rank every section of an entry beside the entry itself.
     */
    public function getSearchTitle(?string $language = null): string
    {
        $name = (string)$this->getSearchAttributeValue('name', $language);
        return mb_substr(SearchText::normalize($name !== '' ? $name : $this->getTypeName()), 0, 255);
    }

    protected function isSearchResultVisible(): bool
    {
        return WebUser::current()?->can(Entry::AUTH_ENTRY) ?? false;
    }

    public function getHtmlId(): ?string
    {
        // not `getVisibleAttribute()`: a type hiding the slug keeps the id it had
        $slug = $this->hasAttribute('slug') ? $this->getI18nAttribute('slug') : null;
        return $slug ?: ('section-' . $this->id);
    }

    /**
     * @return array<int|string, mixed>|false
     */
    public function getRoute(): false|array
    {
        return ($route = $this->entry->getRoute()) ? [...$route, '#' => $this->getHtmlId()] : false;
    }

    #[Override]
    public static function getTypeClass(): string
    {
        return SectionType::class;
    }

    #[Override]
    public function getType(): ?SectionType
    {
        /** @var SectionType|null */
        return static::findType(static::normalizeTypeValue($this->type ?? null));
    }

    /**
     * A section carrying a block is a placeholder for it, so the block answers for the rendered view.
     */
    public function getViewFile(): ?string
    {
        return $this->getVisibleBlock()?->getViewFile() ?? $this->getType()?->getViewFile();
    }

    /**
     * @return list<Asset>
     */
    public function getVisibleAssets(): array
    {
        if ($block = $this->getVisibleBlock()) {
            return $block->getVisibleAssets();
        }

        return $this->allowsAssets() ? array_values($this->assets) : [];
    }

    /**
     * @return list<Entry>
     */
    public function getVisibleEntries(): array
    {
        if ($block = $this->getVisibleBlock()) {
            return $block->getVisibleEntries();
        }

        return $this->allowsEntries() ? array_values($this->entries) : [];
    }

    public function allowsBlock(): bool
    {
        return static::getModule()->enableBlocks && ($this->getType()?->allowsBlock() ?? false);
    }

    public function getAssetClass(): string
    {
        return SectionAsset::class;
    }

    public function allowsAssets(): bool
    {
        return static::getModule()->enableSectionAssets && $this->typeAllowsAssets();
    }

    public function allowsEntries(): bool
    {
        return static::getModule()->enableSectionEntries && $this->typeAllowsEntries();
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'block_id' => Yii::t('cms', 'SECTION_BLOCK_ID_LABEL'),
            'entry_id' => Yii::t('cms', 'SECTION_ENTRY_ID_LABEL'),
            'entry_count' => Yii::t('cms', 'SECTION_ENTRY_COUNT_LABEL'),
            'section_count' => Yii::t('cms', 'SECTION_SECTION_COUNT_LABEL')
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Section';
    }

    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%section}}';
    }
}
