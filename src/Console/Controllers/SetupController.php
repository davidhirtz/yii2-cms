<?php

declare(strict_types=1);

namespace Hirtz\Cms\Console\Controllers;

use Closure;
use Hirtz\Cms\Models\Actions\SetupContent;
use Hirtz\Cms\Modules\ModuleTrait;
use Hirtz\Skeleton\Console\Controllers\Traits\UserIdentityTrait;
use Override;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Creates the first content of an installation.
 */
class SetupController extends Controller
{
    use ModuleTrait;
    use UserIdentityTrait;

    /**
     * @var string|null the file returning the `categories` and `entries` to insert, `@app/config/setup.php` when it
     * is not given
     */
    public ?string $file = null;

    /**
     * @param string $actionID
     * @return list<string>
     */
    #[Override]
    public function options($actionID): array
    {
        return array_values([...parent::options($actionID), 'file', 'user']);
    }

    #[Override]
    public function beforeAction($action): bool
    {
        return parent::beforeAction($action) && $this->setUserIdentity();
    }

    /**
     * Inserts the declared categories while there are none, then the declared entries while there are none.
     *
     * Without a setup file, the entry is a homepage named after the application.
     */
    public function actionIndex(): int
    {
        $path = Yii::getAlias($this->file ?? '@app/config/setup.php');

        if ($this->file !== null && !is_file($path)) {
            $this->stderr("File $path not found." . PHP_EOL, Console::FG_RED);
            return ExitCode::NOINPUT;
        }

        /** @var array{categories?: list<array<string, mixed>>, entries?: Closure(): list<array<string, mixed>>|list<array<string, mixed>>} $config */
        $config = is_file($path) ? require $path : [];

        $setup = new SetupContent(
            $config['categories'] ?? [],
            $config['entries'] ?? $this->getDefaultEntries(),
        );

        $success = $setup->run();

        foreach ($setup->getInserted() as $record) {
            $id = implode('-', (array)$record->getPrimaryKey());
            $this->stdout('Inserted ' . $record::class . " #$id" . PHP_EOL);
        }

        foreach ($setup->getFailed() as $record) {
            $errors = implode(' ', $record->getErrorSummary(true));
            $this->stderr('Failed to insert ' . $record::class . ": $errors" . PHP_EOL, Console::FG_RED);
        }

        return $success ? ExitCode::OK : ExitCode::DATAERR;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function getDefaultEntries(): array
    {
        return [
            [
                'name' => 'Home',
                'slug' => self::getModule()->entryIndexSlug,
                'title' => Yii::$app->name,
            ],
        ];
    }
}
