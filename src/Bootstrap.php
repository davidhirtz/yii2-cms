<?php

declare(strict_types=1);

namespace Hirtz\Cms;

use Hirtz\Cms\Models\Actions\ReorderCategories;
use Hirtz\Cms\Models\Actions\ReorderEntries;
use Hirtz\Cms\Models\Actions\ReorderSections;
use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\BlockAsset;
use Hirtz\Cms\Models\BlockEntry;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Models\Collections\MenuCollection;
use Hirtz\Cms\Widgets\Artwork;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryAsset;
use Hirtz\Cms\Models\Events\TenantAfterSaveEventHandler;
use Hirtz\Cms\Models\Events\TenantBeforeDeleteEventHandler;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Models\SectionAsset;
use Hirtz\Cms\Models\SectionEntry;
use Hirtz\Media\Mcp\AssetResources;
use Hirtz\Skeleton\Base\ConfigBootstrapInterface;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Filters\PageCache;
use Hirtz\Skeleton\Helpers\EventHelper;
use Hirtz\Skeleton\Mcp\Mcp as SkeletonMcp;
use Hirtz\Skeleton\Mcp\Prompt;
use Hirtz\Skeleton\Mcp\Resource;
use Hirtz\Skeleton\Modules\Admin\Controllers\DashboardController;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Console\Application as ConsoleApplication;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Tenant\Models\Tenant;
use Hirtz\Tenant\Modules\Admin\Widgets\Grids\TenantGridView;
use Override;
use Yii;
use yii\base\ModelEvent;
use yii\db\BaseActiveRecord;
use yii\i18n\PhpMessageSource;

class Bootstrap implements ConfigBootstrapInterface
{
    #[Override]
    public static function getDefaultConfig(): array
    {
        return [
            'components' => [
                'i18n' => [
                    'translations' => [
                        'cms' => [
                            'class' => PhpMessageSource::class,
                            'basePath' => '@cms/../messages',
                            'forceTranslation' => true,
                        ],
                    ],
                ],
                'mcp' => [
                    'resources' => [
                        static fn (): Resource => Resource::make('entry', Entry::class)
                            ->defaultType(static fn (): ?int => Entry::getModule()->defaultEntryType)
                            ->parent('parent', Entry::class, static fn (Entry $entry, ?Entry $parent) => $entry->populateParentRelation($parent), false)
                            ->beforeInsert(static function (Entry $entry): void {
                                if ($entry->isNewIndex()) {
                                    foreach ($entry->getI18nAttributesNames('slug') as $attribute) {
                                        $entry->$attribute = (string)Entry::getModule()->entryIndexSlug;
                                    }
                                }
                            })
                            ->reorder(static fn (?ActiveRecord $parent, array $ids): bool => Yii::createObject(ReorderEntries::class, [
                                $parent instanceof Entry ? $parent : null,
                                null,
                                $ids,
                            ])->run() !== false)
                            ->url(static fn (Entry $entry): array|false => $entry->getRoute()),
                        static fn (): Resource => Resource::make('section', Section::class)
                            ->parent('entry', Entry::class, static function (Section $section, ?Entry $entry): bool {
                                if (!$entry?->allowsSections()) {
                                    return false;
                                }

                                $section->populateEntryRelation($entry);
                                return true;
                            })
                            ->reorder(static fn (?ActiveRecord $entry, array $ids): bool => $entry instanceof Entry
                                && Yii::createObject(ReorderSections::class, [$entry, $ids])->run() !== false)
                            ->url(static fn (Section $section): array|false => $section->getRoute()),
                        static fn (): Resource => Resource::make('category', Category::class)
                            ->parent('parent', Category::class, static function (Category $category, ?Category $parent): void {
                                $category->parent_id = $parent?->id;
                            }, false)
                            ->reorder(static fn (?ActiveRecord $parent, array $ids): bool => Yii::createObject(ReorderCategories::class, [
                                $parent instanceof Category ? $parent : null,
                                $ids,
                            ])->run() !== false)
                            ->url(static fn (Category $category): array|false => $category->getRoute()),
                        static fn (): Resource => Resource::make('block', Block::class),
                        static fn (): Resource => AssetResources::make('entry_asset', EntryAsset::class, 'entry'),
                        static fn (): Resource => AssetResources::make('section_asset', SectionAsset::class, 'section'),
                        static fn (): Resource => AssetResources::make('block_asset', BlockAsset::class, 'block'),
                    ],
                    'instructions' => [
                        static fn (): string => 'An entry is a page or article; it nests under another entry through '
                            . '"parent" on create_record, and its frontend URL comes from its slug. A section is a '
                            . 'content block of an entry, created with "entry" set to the entry\'s id where the entry\'s '
                            . 'type allows sections, and shows a shared block through its block_id. reorder puts '
                            . 'entries, sections and categories in order under their parent.',
                    ],
                    'tools' => [
                        static fn (SkeletonMcp $mcp): ?Mcp\AssignCategoriesTool => Entry::getModule()->enableCategories
                            ? new Mcp\AssignCategoriesTool($mcp)
                            : null,
                    ],
                    'prompts' => [
                        static fn (): Prompt => Mcp\Prompts::gettingStarted(),
                        static fn (): Prompt => Mcp\Prompts::translateEntry(),
                        static fn (): Prompt => Mcp\Prompts::writeMetaDescriptions(),
                    ],
                ],
                'search' => [
                    'models' => [
                        Block::class,
                        BlockAsset::class,
                        Category::class,
                        Entry::class,
                        EntryAsset::class,
                        Section::class,
                        SectionAsset::class,
                    ],
                ],
            ],
            'container' => [
                'definitions' => [
                    TenantGridView::class => Modules\Admin\Widgets\Grids\TenantGridView::class,
                ],
            ],
            'modules' => [
                'admin' => [
                    'modules' => [
                        'cms' => [
                            'class' => Modules\Admin\Module::class,
                        ],
                    ],
                ],
                'cms' => [
                    'class' => Module::class,
                    'entryRelations' => [
                        BlockEntry::class,
                        SectionEntry::class,
                    ],
                ],
                'media' => [
                    'class' => \Hirtz\Media\Module::class,
                    'assetClasses' => [
                        BlockAsset::class,
                        EntryAsset::class,
                        SectionAsset::class,
                    ],
                    'fileReferenceModelClasses' => [
                        Block::class,
                        Entry::class,
                        Section::class,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param Application<User>|ConsoleApplication $app
     */
    public function bootstrap($app): void
    {
        Yii::setAlias('@cms', __DIR__);

        // In `bootstrap()`, not the default config: a web application must not route to a console controller
        if ($app instanceof ConsoleApplication) {
            $app->controllerMap['permalink'] ??= Console\Controllers\PermalinkController::class;
            $app->controllerMap['setup'] ??= Console\Controllers\SetupController::class;
        }

        Artwork::reset();
        CategoryCollection::reset();
        MenuCollection::reset();

        $this->addDefaultUrlRules();
        $this->addTenantEventHandlers();
        $this->addPageCacheEventHandlers();

        DashboardController::addRoles(static fn (): array => [
            Entry::AUTH_ENTRY,
            Category::AUTH_CATEGORY,
        ]);

        $app->setMigrationNamespace('Hirtz\Cms\Migrations');

    }

    /**
     * A cached page must not outlive the moment a scheduled entry goes live on it.
     */
    protected function addPageCacheEventHandlers(): void
    {
        EventHelper::on(PageCache::class, PageCache::EVENT_CONFIGURE, function (PageCache $filter): void {
            $module = Yii::$app->getModule('cms');
            $time = $module instanceof Module ? $module->getNextPublishTime() : null;

            if ($time !== null) {
                $seconds = max(1, $time - time());
                $filter->duration = $filter->duration > 0 ? min($filter->duration, $seconds) : $seconds;
            }
        });
    }

    protected function addTenantEventHandlers(): void
    {
        EventHelper::on(
            Tenant::class,
            BaseActiveRecord::EVENT_BEFORE_DELETE,
            fn (Tenant $tenant, ModelEvent $event) => Yii::createObject(TenantBeforeDeleteEventHandler::class, [
                $event,
                $tenant,
            ]),
            ModelEvent::class
        );

        foreach ([BaseActiveRecord::EVENT_AFTER_INSERT, BaseActiveRecord::EVENT_AFTER_UPDATE] as $name) {
            EventHelper::on(
                Tenant::class,
                $name,
                fn () => Yii::createObject(TenantAfterSaveEventHandler::class)
            );
        }
    }

    /**
     * @see Module::$enableUrlRules
     */
    protected function addDefaultUrlRules(): void
    {
        if (Yii::$app->getModules()['cms']['enableUrlRules'] ?? true) {
            Yii::$app->addUrlManagerRules($this->getDefaultUrlRules());
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getDefaultUrlRules(): array
    {
        return [
            [
                'pattern' => '<slug:.+>',
                'route' => 'cms/site/view',
                'encodeParams' => false,
                'position' => 1000,
            ],
            [
                'pattern' => '',
                'route' => 'cms/site/index',
                'position' => 1100,
            ],
        ];
    }
}
