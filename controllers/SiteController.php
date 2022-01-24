<?php

namespace davidhirtz\yii2\cms\controllers;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\media\Module;
use davidhirtz\yii2\skeleton\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class SiteController.
 * @package davidhirtz\yii2\cms\controllers
 *
 * @property Module $module
 */
class SiteController extends Controller
{

    /**
     * @param string $category
     * @return string|Response
     * @todo
     */
    public function actionIndex($category = null)
    {
        $category = Category::getBySlug($category);
        $entries = [];

        /** @noinspection MissedViewInspection */
        return $this->render('index', [
            'category' => $category,
            'entries' => $entries,
        ]);
    }

    /**
     * @param string $entry
     * @return string|Response
     */
    public function actionView($entry)
    {
        $entry = $this->getQuery()
            ->whereSlug($entry)
            ->withAssets()
            ->withSections()
            ->limit(1)
            ->one();

        if (!$entry || !$entry->getRoute()) {
            throw new NotFoundHttpException();
        }

        $entry->populateAssetRelations();

        /** @noinspection MissedViewInspection */
        return $this->render('view', [
            'entry' => $entry,
        ]);
    }

    /**
     * @return EntryQuery
     */
    protected function getQuery()
    {
        return Entry::find()
            ->selectSiteAttributes()
            ->replaceI18nAttributes();
    }
}