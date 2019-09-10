<?php

namespace davidhirtz\yii2\cms\modules\admin\data\base;

use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\modules\admin\models\forms\CategoryForm;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\cms\modules\ModuleTrait;
use davidhirtz\yii2\skeleton\db\ActiveQuery;
use yii\data\ActiveDataProvider;
use yii\data\Sort;

/**
 * Class EntryActiveDataProvider.
 * @package davidhirtz\yii2\cms\modules\admin\data\base
 * @see \davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider
 *
 * @property EntryQuery $query
 */
class EntryActiveDataProvider extends ActiveDataProvider
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
     * @var int
     */
    public $type;

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->initQuery();
        $this->initSort();

        parent::init();
    }

    /**
     * Inits query.
     */
    protected function initQuery()
    {
        $this->query = EntryForm::find()->replaceI18nAttributes();

        if ($this->getModule()->defaultEntryOrderBy) {
            $this->query->orderBy($this->getModule()->defaultEntryOrderBy);
        }

        if ($this->category) {
            $this->query->orderBy($this->category->getEntryOrderBy())->innerJoinWith([
                'entryCategory' => function (ActiveQuery $query) {
                    $query->onCondition(['category_id' => $this->category->id]);
                }
            ]);
        }

        if ($this->type && isset(EntryForm::getTypes()[$this->type])) {
            if (isset(EntryForm::getTypes()[$this->type]['orderBy'])) {
                $this->query->orderBy(EntryForm::getTypes()[$this->type]['orderBy']);
            }

            $this->query->andWhere(['type' => $this->type]);
        }

        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }

        if ($this->getModule()->enabledNestedEntries) {
            if ($this->entry) {
                $this->query->andWhere(['parent_id' => $this->entry->id]);
            }

            $this->query->orderBy(['lft' => SORT_ASC]);
        }
    }

    /**
     * Inits sort.
     */
    protected function initSort()
    {
        $this->setSort($this->isOrderedByPosition() ? false : new Sort([
            'attributes' => [
                'type' => [
                    'asc' => ['type' => SORT_ASC, 'name' => SORT_ASC],
                    'desc' => ['type' => SORT_DESC, 'name' => SORT_DESC],
                ],
                'name' => [
                    'asc' => ['name' => SORT_ASC],
                    'desc' => ['name' => SORT_DESC],
                ],
                'asset_count' => [
                    'asc' => ['asset_count' => SORT_ASC, 'name' => SORT_ASC],
                    'desc' => ['asset_count' => SORT_DESC, 'name' => SORT_ASC],
                    'default' => SORT_DESC,
                ],
                'section_count' => [
                    'asc' => ['section_count' => SORT_ASC, 'name' => SORT_ASC],
                    'desc' => ['section_count' => SORT_DESC, 'name' => SORT_ASC],
                    'default' => SORT_DESC,
                ],
                'publish_date' => [
                    'asc' => ['publish_date' => SORT_ASC],
                    'desc' => ['publish_date' => SORT_DESC],
                    'default' => SORT_DESC,
                ],
                'updated_at' => [
                    'asc' => ['updated_at' => SORT_ASC],
                    'desc' => ['updated_at' => SORT_DESC],
                    'default' => SORT_DESC,
                ],
            ],
            'defaultOrder' => ['updated_at' => SORT_DESC],
        ]));
    }

    /**
     * @inheritDoc
     */
    public function getPagination()
    {
        return !$this->isOrderedByPosition() ? parent::getPagination() : false;
    }

    /**
     * @return bool
     */
    public function isOrderedByPosition()
    {
        return $this->query->orderBy && in_array(key($this->query->orderBy), ['lft', 'position']);
    }
}