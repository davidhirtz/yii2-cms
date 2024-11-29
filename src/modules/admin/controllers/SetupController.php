<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\modules\admin\controllers;

use davidhirtz\yii2\cms\models\Category;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\models\EntryCategory;
use davidhirtz\yii2\cms\models\Section;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\media\models\collections\FolderCollection;
use davidhirtz\yii2\skeleton\log\ActiveRecordErrorLogger;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\web\Controller;
use Throwable;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * @noinspection PhpUnused
 */

abstract class SetupController extends Controller
{
    use ModuleTrait;

    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
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
        ];
    }

    abstract public function getCategoryAttributes(): array;

    abstract public function getEntryAttributes(): array;

    public function actionIndex(): Response
    {
        foreach ($this->getLanguages() as $language) {
            Yii::$app->getI18n()->callback($language, function () {
                if ($this->shouldInsertCategories()) {
                    $this->insertCategories();
                }

                if ($this->shouldInsertEntries()) {
                    $this->insertEntries();
                }
            });
        }


        $this->ensureDefaultFolder();

        return $this->redirect(['/admin/entry/index']);
    }

    protected function insertCategories(): void
    {
        $this->insertInTransaction(function () {
            foreach ($this->getCategoryAttributes() as $attributes) {
                $category = Category::create();
                $category->setAttributes($attributes);

                if (!$category->insert()) {
                    ActiveRecordErrorLogger::log($category);
                }
            }
        });
    }

    protected function insertEntries(): void
    {
        $this->insertInTransaction(function () {
            foreach ($this->getEntryAttributes() as $attributes) {
                $this->insertEntry($attributes);
            }
        });
    }

    protected function insertInTransaction(callable $callback): void
    {
        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
            $callback();
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }
    }

    protected function insertEntry(array $attributes, Entry $parent = null): void
    {
        $subentries = ArrayHelper::remove($attributes, 'entries', []);
        $categories = ArrayHelper::remove($attributes, 'categories', []);
        $sections = ArrayHelper::remove($attributes, 'sections', []);

        $entry = Entry::create();
        $entry->setAttributes($attributes);

        if ($parent instanceof Entry) {
            $entry->populateRelation('parent', $parent);
            $entry->parent_id = $parent->id;
        }

        if ($entry->insert()) {
            foreach ($subentries as $attributes) {
                $this->insertEntry($attributes, $entry);
            }

            foreach ($categories as $attributes) {
                $entryCategory = EntryCategory::create();
                $entryCategory->setAttributes($attributes);
                $entryCategory->populateEntryRelation($entry);

                if (!$entryCategory->insert()) {
                    ActiveRecordErrorLogger::log($entryCategory);
                }
            }

            foreach ($sections as $attributes) {
                $section = Section::create();
                $section->setAttributes($attributes);
                $section->populateEntryRelation($entry);

                if (!$section->insert()) {
                    ActiveRecordErrorLogger::log($section);
                }
            }
        } else {
            ActiveRecordErrorLogger::log($entry);
        }
    }

    protected function ensureDefaultFolder(): void
    {
        FolderCollection::getDefault();
    }

    protected function shouldInsertCategories(): bool
    {
        return !Category::find()->count();
    }

    protected function shouldInsertEntries(): bool
    {
        return !Entry::find()->count();
    }

    protected function getLanguages(): array
    {
        return static::getModule()->enableI18nTables ? Yii::$app->getI18n()->getLanguages() : [Yii::$app->language];
    }
}
