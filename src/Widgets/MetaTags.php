<?php

declare(strict_types=1);

namespace Hirtz\Cms\Widgets;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Collections\CategoryCollection;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Models\Asset;
use Hirtz\Media\Models\File;
use Hirtz\Media\Models\Interfaces\AssetModelInterface;
use Hirtz\Media\Transformations\Transformation;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Web\UrlManager;
use Hirtz\Skeleton\Helpers\StructuredData;
use Hirtz\Skeleton\Widgets\StructuredData\BreadcrumbList;
use Hirtz\Skeleton\Widgets\StructuredData\Organization;
use Hirtz\Skeleton\Widgets\StructuredData\Thing;
use Hirtz\Skeleton\Widgets\StructuredData\WebSite;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class MetaTags extends Widget
{
    use ContainerConfigurationTrait;
    use ModuleTrait;

    protected Category|Entry $model;

    /**
     * @var list<string>|null
     */
    protected ?array $languages = null;
    protected bool $enableHrefLangLinks = true;
    protected bool $enableCanonicalUrl = true;
    protected bool $enableBreadcrumbs = true;
    protected bool $enableImages = true;
    protected bool $enableSocialMetaTags = true;
    protected bool $enableStructuredData = true;
    protected ?int $assetType = Asset::TYPE_META_IMAGE;
    protected Transformation|string|null $transformation = Transformation::NAME_OPEN_GRAPH;
    protected string|false $ogType = 'website';

    private UrlManager $urlManager;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->urlManager = Yii::$app->getUrlManager();
        parent::__construct($config);
    }

    public function model(Category|Entry $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @param list<string>|null $languages the languages of the hreflang links, by default those of the URL manager
     */
    public function languages(?array $languages): static
    {
        $this->languages = $languages;
        return $this;
    }

    public function enableHrefLangLinks(bool $enableHrefLangLinks = true): static
    {
        $this->enableHrefLangLinks = $enableHrefLangLinks;
        return $this;
    }

    public function enableCanonicalUrl(bool $enableCanonicalUrl = true): static
    {
        $this->enableCanonicalUrl = $enableCanonicalUrl;
        return $this;
    }

    /**
     * Renders the model's ancestors as a schema.org `BreadcrumbList`, only for a nested model.
     */
    public function enableBreadcrumbs(bool $enableBreadcrumbs = true): static
    {
        $this->enableBreadcrumbs = $enableBreadcrumbs;
        return $this;
    }

    public function enableStructuredData(bool $enableStructuredData = true): static
    {
        $this->enableStructuredData = $enableStructuredData;
        return $this;
    }

    public function enableImages(bool $enableImages = true): static
    {
        $this->enableImages = $enableImages;
        return $this;
    }

    public function enableSocialMetaTags(bool $enableSocialMetaTags = true): static
    {
        $this->enableSocialMetaTags = $enableSocialMetaTags;
        return $this;
    }

    /**
     * @param int|null $assetType the asset type offered as the share image, `null` for every asset
     */
    public function assetType(?int $assetType): static
    {
        $this->assetType = $assetType;
        return $this;
    }

    /**
     * The share image's transformation, or its name on the media module; `null` shares the original file, as does a
     * transformation the file is too small for.
     */
    public function transformation(Transformation|string|null $transformation): static
    {
        $this->transformation = $transformation;
        return $this;
    }

    public function ogType(string|false $ogType): static
    {
        $this->ogType = $ogType;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->enableImages = $this->enableImages
            && $this->model instanceof Entry
            && static::getModule()->enableEntryAssets;

        $this->languages ??= $this->urlManager->i18nUrl
            ? array_keys($this->urlManager->languages)
            : [];

        $this->enableBreadcrumbs = $this->enableBreadcrumbs
            && $this->model->parent_id !== null;

        if (count($this->languages) < 2) {
            $this->enableHrefLangLinks = false;
        }

        parent::configure();
    }

    protected function renderContent(): string|Stringable
    {
        $this->registerMetaTags();

        if ($this->enableStructuredData) {
            $this->registerStructuredData();
        }

        return '';
    }

    /**
     * An entry's ancestors are the ones `PreloadEntrySiteRelations` loaded with the related entries, so they cost no
     * query on the site and leave out what the request's status hides; a view rendered without the preload
     * queries them. A category's come from the cached collection.
     *
     * @return list<Breadcrumb>
     */
    protected function getBreadcrumbs(): array
    {
        $ancestors = $this->model instanceof Entry
            ? $this->model->getAncestors()
            : CategoryCollection::getAncestors($this->model);

        $breadcrumbs = [];

        foreach ($ancestors as $ancestor) {
            $breadcrumbs[] = new Breadcrumb($ancestor->getI18nAttribute('name'), $ancestor->getRoute() ?: null);
        }

        $breadcrumbs[] = new Breadcrumb($this->model->getI18nAttribute('name'));

        return $breadcrumbs;
    }

    protected function registerMetaTags(): void
    {
        $this->setDocumentTitle();
        $this->setMetaDescription();

        if ($this->enableHrefLangLinks) {
            $this->registerHrefLangLinkTags();
        }

        if ($this->enableCanonicalUrl) {
            $this->registerCanonicalUrlTags();
        }

        if ($this->enableImages) {
            $this->registerImageMetaTags();
        }

        if ($this->enableSocialMetaTags) {
            $this->registerSocialMetaTags();
        }
    }

    protected function setDocumentTitle(): void
    {
        $title = $this->getTitle();

        if ($title) {
            $this->view->title($title);
        }
    }

    protected function setMetaDescription(): void
    {
        $content = $this->getDescription();

        if ($content) {
            $this->view->description($content);
        }
    }

    protected function getTitle(): ?string
    {
        return $this->model->getVisibleAttribute('title') ?? $this->model->getI18nAttribute('name');
    }

    protected function getDescription(): ?string
    {
        $content = $this->model->getVisibleAttribute('description');

        if ($content === null) {
            $content = $this->model->getVisibleAttribute('content');

            if ($content && $this->model->getCustomAttribute('content') instanceof HtmlCustomAttribute) {
                $content = html_entity_decode(strip_tags(str_replace('<', ' <', $content)), ENT_QUOTES | ENT_HTML5);
            }
        }

        return is_string($content) ? $content : null;
    }

    protected function registerHrefLangLinkTags(): void
    {
        foreach ($this->languages as $language) {
            Yii::$app->getI18n()->callback($language, function () use ($language): void {
                $route = $this->model->getRoute();

                if ($route) {
                    $this->view->registerHrefLangLinkTag($language, $this->urlManager->createAbsoluteUrl($route));
                }
            });
        }

        $this->registerDefaultHrefLangLinkTag();
    }

    protected function registerDefaultHrefLangLinkTag(): void
    {
        $this->view->registerDefaultHrefLangLinkTag($this->urlManager->defaultLanguage);
    }

    protected function registerCanonicalUrlTags(): void
    {
        $url = $this->getUrl();

        if ($url) {
            $this->view->registerCanonicalTag($url);
        }
    }

    protected function getUrl(): ?string
    {
        $route = $this->model->getRoute();
        return $route ? $this->urlManager->createAbsoluteUrl($route) : null;
    }

    protected function registerSocialMetaTags(): void
    {
        if ($this->ogType) {
            $this->view->registerOpenGraphMetaTags($this->ogType);
        }
    }

    protected function registerImageMetaTags(): void
    {
        foreach ($this->getImages() as [$url, $width, $height]) {
            $this->view->registerImageMetaTags($url, $width, $height);
        }
    }

    /**
     * A category carries no assets. The URL comes from the media module, which only serves a transformation it
     * declares under that name.
     *
     * @return list<array{string, int|null, int|null}>
     */
    protected function getImages(): array
    {
        if (!$this->enableImages || !$this->model instanceof AssetModelInterface) {
            return [];
        }

        $transformation = is_string($this->transformation)
            ? File::getModule()->getTransformation($this->transformation)
            : $this->transformation;

        $images = [];

        foreach ($this->model->assets as $asset) {
            if ($this->assetType && $this->assetType !== $asset->type) {
                continue;
            }

            $file = $asset->file;
            $url = $transformation ? $file->getTransformationUrl($transformation->name) : null;

            if ($url) {
                $images[] = [$url, ...$transformation->getSizeFor($file)];
                continue;
            }

            $images[] = [$file->getUrl(), $file->width, $file->height];
        }

        return $images;
    }

    protected function registerStructuredData(): void
    {
        $organization = Organization::make();
        $organization->register();

        $publisher = $organization->isVisible() ? $organization->getId() : null;

        $website = WebSite::make()->publisher($publisher);
        $website->register();

        $breadcrumbs = $this->enableBreadcrumbs ? $this->getBreadcrumbs() : [];
        $breadcrumbList = null;

        if (count($breadcrumbs) > 1) {
            $breadcrumbList = BreadcrumbList::make()
                ->id($this->getStructuredDataId('breadcrumb'))
                ->breadcrumbs($breadcrumbs);
        }

        $node = $this->getPageNode($website->getId(), $breadcrumbList?->getId());
        $structuredData = $this->model->getType()?->getStructuredData();
        $result = $structuredData ? $structuredData($this->model, $node) : $node;

        if ($result instanceof Thing) {
            $main = $this->getMainEntityNode($result, $node);

            if ($main !== null) {
                $node['mainEntity'] = ['@id' => $main['@id']];
                $this->view->registerStructuredData($main);
            }

            $result = $node;
        }

        if ($result !== null) {
            $this->view->registerStructuredData($result);
        }

        $breadcrumbList?->register();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPageNode(?string $website, ?string $breadcrumb): array
    {
        $image = $this->getImages()[0] ?? null;

        $publishDate = $this->model instanceof Entry && ($this->model->getType()?->schedules() ?? true)
            ? $this->model->publish_date
            : null;

        return StructuredData::filter([
            '@type' => $this->model instanceof Entry ? 'WebPage' : 'CollectionPage',
            '@id' => $this->getStructuredDataId('webpage'),
            'url' => $this->getUrl(),
            'name' => $this->getTitle(),
            'description' => $this->getDescription(),
            'inLanguage' => Yii::$app->language,
            'primaryImageOfPage' => $image ? StructuredData::image(...$image) : null,
            'datePublished' => StructuredData::date($publishDate),
            'dateModified' => StructuredData::date($this->model->updated_at),
            'isPartOf' => $website ? ['@id' => $website] : null,
            'breadcrumb' => $breadcrumb ? ['@id' => $breadcrumb] : null,
        ]);
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>|null
     */
    protected function getMainEntityNode(Thing $thing, array $page): ?array
    {
        $node = $thing->build();

        if ($node === null) {
            return null;
        }

        $type = is_string($node['@type'] ?? null) ? $node['@type'] : 'thing';
        $node = ['@type' => $type, '@id' => $node['@id'] ?? $this->getStructuredDataId(lcfirst($type)), ...$node];

        $inherited = StructuredData::filter([
            'name' => $page['name'] ?? null,
            'description' => $page['description'] ?? null,
            'image' => $page['primaryImageOfPage'] ?? null,
            'url' => $page['url'] ?? null,
        ]);

        return [...$node, ...array_diff_key($inherited, $node)];
    }

    protected function getStructuredDataId(string $fragment): string
    {
        return StructuredData::id($this->getUrl() ?? '', $fragment);
    }
}
