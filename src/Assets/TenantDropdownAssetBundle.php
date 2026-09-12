<?php

declare(strict_types=1);

namespace Hirtz\Cms\Assets;

use yii\web\AssetBundle;

class TenantDropdownAssetBundle extends AssetBundle
{
    public $js = ['js/dropdown.js'];
    public $sourcePath = __DIR__ . '/../../resources/assets/dist';
}
