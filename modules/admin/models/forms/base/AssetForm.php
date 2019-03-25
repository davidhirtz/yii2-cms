<?php

namespace davidhirtz\yii2\cms\modules\admin\models\forms\base;

use davidhirtz\yii2\cms\models\Asset;
use davidhirtz\yii2\cms\models\queries\EntryQuery;
use davidhirtz\yii2\cms\models\queries\SectionQuery;
use davidhirtz\yii2\cms\modules\admin\models\forms\EntryForm;
use davidhirtz\yii2\cms\modules\admin\models\forms\SectionForm;
use davidhirtz\yii2\media\models\queries\FileQuery;
use davidhirtz\yii2\media\modules\admin\models\forms\FileForm;

/**
 * Class AssetForm
 * @package davidhirtz\yii2\cms\modules\admin\models\forms\base
 *
 * @property EntryForm $entry
 * @property SectionForm $section
 * @property FileForm $file
 * @method static \davidhirtz\yii2\cms\modules\admin\models\forms\AssetForm findOne($condition)
 */
class AssetForm extends Asset
{
    /**
     * @return EntryQuery
     */
    public function getEntry(): EntryQuery
    {
        return $this->hasOne(EntryForm::class, ['id' => 'entry_id']);
    }

    /**
     * @return SectionQuery
     */
    public function getSection(): SectionQuery
    {
        return $this->hasOne(SectionForm::class, ['id' => 'section_id']);
    }

    /**
     * @return FileQuery
     */
    public function getFile(): FileQuery
    {
        return $this->hasOne(FileForm::class, ['id' => 'file_id']);
    }
}