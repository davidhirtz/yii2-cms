<?php

namespace davidhirtz\yii2\cms\controllers;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\media\models\Transformation;
use davidhirtz\yii2\media\models\File;
use davidhirtz\yii2\media\models\Folder;
use davidhirtz\yii2\media\Module;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Class SiteController.
 * @package davidhirtz\yii2\cms\controllers
 *
 * @property Module $module
 */
class SiteController extends Controller
{

    /**
     * @return string|\yii\web\Response
     */
    public function actionIndex()
    {
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionView($entry)
    {
        $entry = $this->getQuery()
            ->with('sections')
            ->whereLower(['slug' => $entry])
            ->limit(1)
            ->one();

        if(!$entry) {
            throw new NotFoundHttpException;
        }

        $entry->populateAssetRelations();

        foreach($entry->sections as $section) {
            foreach($section->assets as $asset) {
                dump($asset->section->entry->id);
            }
        }

        /** @noinspection MissedViewInspection */
        return $this->render('view', [
            'entry' => $entry,
        ]);
    }

    protected function getQuery()
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->with([
                'assets' => function(ActiveQuery $query) {
                    $query->with('file');
                },
            ]);
    }
}