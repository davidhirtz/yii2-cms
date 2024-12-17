<?php

declare(strict_types=1);

namespace davidhirtz\yii2\cms\tests\support;

use davidhirtz\yii2\cms\tests\data\models\TestAsset;
use davidhirtz\yii2\cms\tests\data\models\TestEntry;
use davidhirtz\yii2\cms\tests\data\models\TestSection;
use davidhirtz\yii2\media\models\File;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class UnitTester extends \Codeception\Actor
{
    use _generated\UnitTesterActions;

    public function grabAssetFixture(string $key): TestAsset
    {
        return $this->grabFixture('assets', $key);
    }

    public function grabCategoryFixture(string $key): TestAsset
    {
        return $this->grabFixture('categories', $key);
    }

    public function grabEntryFixture(string $key): TestEntry
    {
        return $this->grabFixture('entries', $key);
    }

    public function grabFileFixture(string $key): File
    {
        return $this->grabFixture('files', $key);
    }

    public function grabSectionFixture(string $key): TestSection
    {
        return $this->grabFixture('sections', $key);
    }
}
