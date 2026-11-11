<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Override;
use yii\filters\AccessControl;

class EntryRelationController extends AbstractController
{
    use EntryControllerTrait;

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
                        'roles' => [Entry::AUTH_ENTRY],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(int $entry): string
    {
        return $this->render('index', [
            'entry' => $this->findEntry($entry),
        ]);
    }
}
