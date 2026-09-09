<?php

declare(strict_types=1);

namespace Hirtz\Cms\Models\Traits;

use Hirtz\Skeleton\Validators\UniqueValidator;
use Yii;
use yii\helpers\Inflector;

trait SlugAttributeTrait
{
    public bool $customSlugBehavior = false;
    public bool $slugLowercase = true;
    public int|false $slugMaxLength = 100;
    public string $slugReplacement = '-';
    public array|string $slugUniqueValidator = UniqueValidator::class;

    private ?bool $isSlugRequired = null;

    public function ensureSlug(string $attribute = 'name'): void
    {
        if ($this->isSlugRequired()) {
            foreach ($this->getI18nAttributeNames('slug') as $language => $attributeName) {
                if (!$this->$attributeName) {
                    $name = $this->getI18nAttribute($attribute, $language);

                    if ($name) {
                        $this->$attributeName = mb_substr((string)$name, 0, $this->slugMaxLength);
                    }
                }
            }
        }

        if (!$this->customSlugBehavior) {
            foreach ($this->getI18nAttributeNames('slug') as $attributeName) {
                $this->$attributeName = Inflector::slug($this->$attributeName, $this->slugReplacement, $this->slugLowercase);
            }
        }
    }

    public function generateUniqueSlug(): void
    {
        foreach ($this->getI18nAttributeNames('slug') as $attributeName) {
            if ($baseSlug = $this->getAttribute($attributeName)) {
                $i = 1;

                // This needs to run the full validation, not just on the attribute to make sure the slug is unique
                while ($i < 100 && !$this->validate()) {
                    if (!$this->hasErrors($attributeName)) {
                        break;
                    }

                    $prevSlug = $baseSlug;
                    $baseSlug = mb_substr((string)$baseSlug, 0, (int)($this->slugMaxLength - 1 - ceil($i / 10)), Yii::$app->charset);
                    $this->setAttribute($attributeName, $baseSlug . '-' . $i++);

                    Yii::debug("Slug '$prevSlug' already exists, trying '{$this->getAttribute($attributeName)}' instead ...");
                }
            }
        }
    }

    public function isSlugRequired(): bool
    {
        if ($this->isSlugRequired === null) {
            $schema = static::getDb()->getSchema();
            $this->isSlugRequired = !$schema->getTableSchema(static::tableName())->getColumn('slug')->allowNull;
        }

        return $this->isSlugRequired;
    }

    protected function isUniqueRule(mixed $ruleName): bool
    {
        return $ruleName === $this->slugUniqueValidator;
    }
}
