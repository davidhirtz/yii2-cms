<?php

namespace davidhirtz\yii2\cms\modules\admin\data\base;

use davidhirtz\yii2\cms\models\queries\CategoryQuery;
use davidhirtz\yii2\cms\modules\admin\models\forms\CategoryForm;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use yii\data\ActiveDataProvider;

/**
 * Class CategoryActiveDataProvider.
 * @package davidhirtz\yii2\cms\modules\admin\data\base
 *
 * @property CategoryQuery $query
 */
class CategoryActiveDataProvider extends ActiveDataProvider
{
    use ModuleTrait;

    /**
     * @var CategoryForm
     */
    public $category;

    /**
     * @var EntryForm
     */
    public $entry;

    /**
     * @var string
     */
    public $searchString;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->initQuery();
        parent::init();
    }

    /**
     * Inits query.
     */
    protected function initQuery()
    {
        $this->query = CategoryForm::find()
            ->replaceI18nAttributes();

        if ($this->entry) {
            $this->query->joinWith([
                'entryCategory' => function (ActiveQuery $query) {
                    $query->onCondition(['entry_id' => $this->entry->id]);
                }
            ]);

        } else {
            $this->query->andWhere(['parent_id' => $this->category->id ?? null]);
        }

        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }
    }

    /**
     * @inheritDoc
     */
    public function getPagination()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getSort()
    {
        return false;
    }
}