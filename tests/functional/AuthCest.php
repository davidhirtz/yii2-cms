<?php

/**
 * @noinspection PhpUnused
 */

namespace davidhirtz\yii2\cms\tests\functional;

use davidhirtz\yii2\cms\Module;
use davidhirtz\yii2\cms\modules\admin\data\EntryActiveDataProvider;
use davidhirtz\yii2\cms\modules\admin\widgets\grids\EntryGridView;
use davidhirtz\yii2\cms\tests\fixtures\UserFixture;
use davidhirtz\yii2\cms\tests\support\FunctionalTester;
use davidhirtz\yii2\skeleton\codeception\functional\BaseCest;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\LoginActiveForm;
use Yii;

class AuthCest extends BaseCest
{
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'users.php',
            ],
        ];
    }

    public function checkIndexAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/entry/index');

        $widget = Yii::createObject(LoginActiveForm::class);
        $I->seeElement("#$widget->id");
    }

    public function checkIndexWithoutPermission(FunctionalTester $I): void
    {
        $this->getLoggedInUser();

        $I->amOnPage('/admin/file/index');
        $I->seeResponseCodeIs(403);
    }

    public function checkIndexWithPermission(FunctionalTester $I): void
    {
        $user = $this->getLoggedInUser();
        $auth = Yii::$app->getAuthManager()->getRole(Module::AUTH_ROLE_AUTHOR);
        Yii::$app->getAuthManager()->assign($auth, $user->id);

        $I->amOnPage('/admin/entry/index');

        $widget = Yii::$container->get(EntryGridView::class, [], [
            'dataProvider' => Yii::createObject(EntryActiveDataProvider::class),
        ]);

        $I->seeElement("#$widget->id");
    }

    protected function getLoggedInUser(): User
    {
        $user = User::find()->one();

        $webuser = Yii::$app->getUser();
        $webuser->loginType = 'test';
        $webuser->login($user);

        return $user;
    }
}
