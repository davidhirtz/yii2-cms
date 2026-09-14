<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\CustomAttributes;

use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Override;
use yii\helpers\Inflector;

/**
 * The slug is inflected in {@see static::normalize()}, which the filter rule of every definition runs, so a
 * translated slug is normalized in every language rather than only in the source one.
 */
class SlugCustomAttribute extends TextCustomAttribute
{
    protected ?int $max = 100;
    protected string $replacement = '-';
    protected bool $lowercase = true;

    public function replacement(string $replacement): static
    {
        $this->replacement = $replacement;
        return $this;
    }

    public function lowercase(bool $lowercase = true): static
    {
        $this->lowercase = $lowercase;
        return $this;
    }

    #[Override]
    public function normalize(mixed $value): mixed
    {
        $value = parent::normalize($value);

        return $value === null
            ? null
            : (Inflector::slug((string)$value, $this->replacement, $this->lowercase) ?: null);
    }

    #[Override]
    protected function getFingerprintData(): array
    {
        return [...parent::getFingerprintData(), $this->replacement, $this->lowercase];
    }
}
