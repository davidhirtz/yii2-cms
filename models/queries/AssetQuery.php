<?php

namespace davidhirtz\yii2\cms\models\queries;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\media\models\queries\FileQuery;
use davidhirtz\yii2\media\models\queries\FolderQuery;

/**
 * Class AssetQuery
 * @package davidhirtz\yii2\cms\models\queries
 *
 * @method Asset one($db = null)
 */
class AssetQuery extends \davidhirtz\yii2\skeleton\db\ActiveQuery
{
    /**
     * @return AssetQuery
     */
    public function selectSiteAttributes()
    {
        return $this->addSelect(array_diff($this->getModelInstance()->attributes(),
            ['updated_by_user_id', 'created_at']));
    }

    /**
     * @return AssetQuery
     */
    public function withFiles()
    {
        return $this->with([
            'file' => function (FileQuery $query) {
                $query->selectSiteAttributes()
                    ->with([
                        'folder' => function (FolderQuery $query) {
                            $query->selectSiteAttributes();
                        }
                    ]);
            }
        ]);
    }
}