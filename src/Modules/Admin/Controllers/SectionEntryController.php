<?php

declare(strict_types=1);

namespace Hirtz\Cms\Modules\Admin\Controllers;

use Hirtz\Cms\Models\Entry;
use Hirtz\Cms\Models\Section;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\EntryRelationControllerTrait;
use Hirtz\Cms\Modules\Admin\Controllers\Traits\SectionControllerTrait;
use Override;
use yii\filters\AccessControl;
use yii\web\Response;

class SectionEntryController extends AbstractController
{
    use EntryControllerTrait;
    use EntryRelationControllerTrait;
    use SectionControllerTrait;

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
                        'actions' => ['create', 'delete', 'index', 'order'],
                        'roles' => [Entry::AUTH_ENTRY],
                    ],
                ],
            ],
            'verbs' => $this->getEntryRelationVerbs(),
        ];
    }

    public function actionIndex(int $section): Response|string
    {
        return $this->renderEntryRelationIndex($this->findSection($section));
    }

    public function actionCreate(
        int $section,
        ?int $entry = null,
        ?int $category = null,
        ?int $parent = null,
        ?string $q = null,
        ?int $type = null
    ): Response|string {
        return $this->createEntryRelation($this->findSection($section), $entry, $category, $parent, $q, $type);
    }

    public function actionDelete(int $section, int $entry): Response|string
    {
        return $this->deleteEntryRelation($this->findSection($section), $entry);
    }

    public function actionOrder(int $section): string
    {
        return $this->reorderEntryRelations($this->findSection($section));
    }

    protected function isSectionAllowed(Section $section): bool
    {
        return $section->allowsEntries();
    }
}
