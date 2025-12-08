<?php

declare(strict_types=1);

namespace Hirtz\Cms\tests\support;

use Hirtz\Cms\models\Entry;
use Yii;

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
class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;

    public function amOnDraftSubdomain(): void
    {
        $httpHost = parse_url((string) Yii::$app->getUrlManager()->getDraftHostInfo(), PHP_URL_HOST);
        $this->haveServerParameter('HTTP_HOST', $httpHost);
    }


    public function grabEntryFixture(string $key): Entry
    {
        /** @var Entry $entry */
        $entry = $this->grabFixture('entries', $key);
        return $entry;
    }
}
