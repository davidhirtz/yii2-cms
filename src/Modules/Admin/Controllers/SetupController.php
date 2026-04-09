<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\EntryCategory;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Module;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Media\Models\Collections\FolderCollection;
use Hirtz\Skeleton\Log\ActiveRecordErrorLogger;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Throwable;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * @noinspection PhpUnused
 */

/**
 * @extends Controller<Module>
 */
abstract class SetupController extends Controller
{
    use ModuleTrait;

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
            Yii::$app->getI18n()->callback($language, function (): void {
                if ($this->shouldInsertCategories()) {
                    $this->insertCategories();
                }

                if ($this->shouldInsertEntries()) {
                    $this->insertEntries();
                }
            });
        }


        $this->ensureDefaultFolder();

        return $this->redirect(['/admin/cms/entry/index']);
    }

    protected function insertCategories(): void
    {
        $this->insertInTransaction(function (): void {
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
        $this->insertInTransaction(function (): void {
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

    protected function insertEntry(array $attributes, ?Entry $parent = null): void
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
