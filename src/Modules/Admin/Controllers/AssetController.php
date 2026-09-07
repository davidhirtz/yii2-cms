<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Actions\DuplicateAsset;
use Hirtz\Cms\Models\Actions\ReorderAssets;
use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\AssetControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Hirtz\Media\Modules\Admin\Controllers\Traits\FileControllerTrait;
use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Flashes;
use yii\web\Response;

abstract class AssetController extends AbstractController
{
    use AssetControllerTrait;
    use EntryControllerTrait;
    use SectionControllerTrait;
    use FileControllerTrait;

    public function actionUpdate(int $id): Response|string
    {
        $asset = $this->findAsset($id, Asset::AUTH_ASSET_UPDATE);

        if ($asset->load($this->request->post())) {
            if ($asset->update()) {
                $this->success(Lang::t('cms', 'ASSET_SUCCESS_UPDATED'));
            }

            return $this->redirectToParent($asset);
        }

        return $this->render('update', [
            'asset' => $asset,
        ]);
    }

    public function actionDelete(int $id): Response|string
    {
        $asset = $this->findAsset($id, Asset::AUTH_ASSET_DELETE);

        $asset->delete();
        $this->errorOrSuccess($asset, Lang::t('cms', 'ASSET_SUCCESS_DELETED'));

        return $this->redirectToParent($asset);
    }

    public function actionDuplicate(int $id): Response|string
    {
        $asset = $this->findAsset($id, Asset::AUTH_ASSET_UPDATE);

        $duplicate = DuplicateAsset::create([
            'asset' => $asset,
        ]);

        if ($errors = $duplicate->getFirstErrors()) {
            $this->error($errors);
            return $this->redirect(['update', 'id' => $asset->id]);
        }

        $this->success(Lang::t('cms', 'ASSET_SUCCESS_DUPLICATED'));
        return $this->redirect(['update', 'id' => $duplicate->id]);
    }

    public function actionOrder(?int $entry = null, ?int $section = null): string
    {
        $parent = $section
            ? $this->findSection($section, Section::AUTH_SECTION_ASSET_ORDER)
            : $this->findEntry($entry, Entry::AUTH_ENTRY_ASSET_ORDER);

        $success = ReorderAssets::runWithBodyParam('asset', [
            'parent' => $parent,
        ]);

        if ($success) {
            $this->success(Lang::t('cms', 'ASSET_SUCCESS_ORDERED'));
        }

        return (string)Flashes::make();
    }

    abstract protected function redirectToParent(Asset $asset): Response;
}
