<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Asset;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Data\AssetArrayDataProvider;
use Hirtz\Media\Models\Folder;
use Hirtz\Media\Modules\Admin\Data\FileActiveDataProvider;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;

class EntryAssetController extends AssetController
{
    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'update'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_UPDATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'duplicate'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_CREATE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_DELETE],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['order'],
                        'roles' => [Entry::AUTH_ENTRY_ASSET_ORDER],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'duplicate' => ['post'],
                    'order' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(int $entry): Response|string
    {
        $entry = $this->findEntry($entry, Entry::AUTH_ENTRY_ASSET_UPDATE);

        $provider = Yii::$container->get(AssetArrayDataProvider::class, config: [
            'parent' => $entry,
        ]);

        return $this->render('index', [
            'entry' => $entry,
            'provider' => $provider,
        ]);
    }

    public function actionCreate(
        int $entry,
        ?int $file = null,
        ?int $folder = null,
        ?string $q = null
    ): Response|string {
        $entry = $this->findEntry($entry, Entry::AUTH_ENTRY_ASSET_CREATE);

        if ($this->request->getIsGet()) {
            $provider = Yii::$container->get(FileActiveDataProvider::class, config: [
                'folder' => Folder::findOne($folder),
                'search' => $q,
            ]);

            return $this->render('create', [
                'provider' => $provider,
                'entry' => $entry,
            ]);
        }

        if ($file) {
            $file = $this->findFile($file);
        }

        $file ??= $this->insertFileFromRequest($folder);

        if ($this->request->preferNoContent()) {
            $this->response->setStatusCode(204);
        }

        if (!$this->response->getIsOk() || $file->hasErrors()) {
            return $this->response;
        }

        $asset = Asset::create();
        $asset->loadDefaultValues();
        $asset->populateEntryRelation($entry);
        $asset->populateFileRelation($file);
        $asset->insert();

        $this->error($asset);

        return $this->redirectToParent($asset);
    }

    #[Override]
    protected function redirectToParent(Asset $asset): Response
    {
        return $this->redirect(['/admin/cms/entry-asset/index', 'entry' => $asset->entry_id]);
    }
}
