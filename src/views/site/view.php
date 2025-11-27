<?php

declare(strict_types=1);

/**
 * @see SiteController::actionView()
 *
 * @var View $this
 * @var Entry $entry
 */

use davidhirtz\yii2\cms\controllers\SiteController;
use davidhirtz\yii2\cms\models\Entry;
use davidhirtz\yii2\cms\widgets\MetaTags;
use davidhirtz\yii2\cms\widgets\Sections;
use davidhirtz\yii2\skeleton\web\View;

MetaTags::make(['model' => $entry]);
echo Sections::make(['entry' => $entry]);
