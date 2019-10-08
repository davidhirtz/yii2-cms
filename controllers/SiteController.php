<?php

namespace davidhirtz\yii2\cms\controllers;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\AssetQuery;
use davidhirtz\yii2\media\Module;
use davidhirtz\yii2\skeleton\web\Controller;
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
     * @param string $entry
     * @return string|\yii\web\Response
     */
    public function actionView($entry)
    {
        $entry = $this->getQuery()
            ->with('sections')
            ->whereLower([Entry::instance()->getI18nAttributeName('slug') => $entry])
            ->limit(1)
            ->one();

        if (!$entry) {
            throw new NotFoundHttpException;
        }

        $entry->populateAssetRelations();

        /** @noinspection MissedViewInspection */
        return $this->render('view', [
            'entry' => $entry,
        ]);
    }

    /**
     * @return \davidhirtz\yii2\cms\models\queries\EntryQuery
     */
    protected function getQuery()
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->with([
                'assets' => function (AssetQuery $query) {
                    $query->selectSiteAttributes()->withFiles();
                },
            ]);
    }
}