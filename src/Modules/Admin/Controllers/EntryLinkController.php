<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Block;
use Hirtz\Cms\Models\Category;
use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider;
use Override;
use Yii;
use yii\filters\AccessControl;

/**
 * @see \Hirtz\Cms\Links\EntryLinks::getPickerRoute()
 */
class EntryLinkController extends AbstractController
{
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
                        'roles' => [Entry::AUTH_ENTRY, Block::AUTH_BLOCK],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(
        ?int $tenant = null,
        ?int $parent = null,
        ?int $category = null,
        ?string $q = null,
        ?int $type = null,
        ?string $types = null,
        ?int $anyTenant = null,
        ?string $language = null,
    ): string {
        $allowedTypes = $types ? array_map(intval(...), explode(',', $types)) : [];

        if ($allowedTypes && $type !== null && !in_array($type, $allowedTypes, true)) {
            $type = null;
        }

        $provider = Yii::$container->get(EntryActiveDataProvider::class, [], [
            'tenantId' => $this->getTenantId($tenant),
            'category' => Category::findOne($category),
            'parent' => Entry::findOne($parent),
            'searchString' => $q,
            'type' => $type,
        ]);

        if ($allowedTypes) {
            $provider->query->andWhere([Entry::tableName() . '.[[type]]' => $allowedTypes]);
        }

        if ($language !== null && !Yii::$app->getI18n()->hasLanguage($language)) {
            $language = null;
        }

        return $this->render('index', [
            'provider' => $provider,
            'types' => $allowedTypes,
            'anyTenant' => (bool)$anyTenant,
            'language' => $language,
        ]);
    }
}
