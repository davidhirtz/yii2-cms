<?php

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\media\models\Folder;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\web\Controller;
use Throwable;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/** @noinspection PhpUnused */

abstract class SetupController extends Controller
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => [User::AUTH_ROLE_ADMIN],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return array[]
     */
    abstract public function getEntryAttributes(): array;

    public function actionIndex(): Response
    {
        if ($this->shouldInsertEntries()) {
            $transaction = Yii::$app->getDb()->beginTransaction();
            Folder::getDefault();

            try {
                foreach ($this->getEntryAttributes() as $attributes) {
                    $this->insertEntry($attributes);
                }

                $transaction->commit();
            } catch (Throwable $exception) {
                $transaction->rollBack();
                throw $exception;
            }
        }

        return $this->redirect(['/admin/entry/index']);
    }

    protected function insertEntry(array $attributes, Entry $parent = null): void
    {
        $subentries = ArrayHelper::remove($attributes, 'entries', []);
        $sections = ArrayHelper::remove($attributes, 'sections', []);

        $entry = new Entry();
        $entry->setAttributes($attributes);

        if ($parent instanceof Entry) {
            $entry->populateRelation('parent', $parent);
            $entry->parent_id = $parent->id;
        }

        if ($entry->insert()) {
            foreach ($subentries as $attributes) {
                $this->insertEntry($attributes, $entry);
            }

            foreach ($sections as $attributes) {
                $section = new Section($attributes);
                $section->populateEntryRelation($entry);
                $section->insert();
            }
        }
    }

    protected function shouldInsertEntries(): bool
    {
        return !Entry::find()->count();
    }
}