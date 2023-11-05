<?php

namespace davidhirtz\yii2\cms\models\traits;

use davidhirtz\yii2\skeleton\validators\UniqueValidator;
use Yii;
use yii\helpers\Inflector;

trait SlugAttributeTrait
{
    /**
     * @var bool whether slugs should not automatically be checked and processed.
     */
    public bool $customSlugBehavior = false;

    public int|false $slugMaxLength = 100;

    /**
     * @var array|string the class name of the unique validator
     */
    public array|string $slugUniqueValidator = UniqueValidator::class;

    private ?bool $_isSlugRequired = null;

    public function ensureSlug(string $attribute = 'name'): void
    {
        if ($this->isSlugRequired()) {
            foreach ($this->getI18nAttributeNames('slug') as $language => $attributeName) {
                if (!$this->$attributeName && ($name = $this->getI18nAttribute($attribute, $language))) {
                    $this->$attributeName = mb_substr((string)$name, 0, $this->slugMaxLength);
                }
            }
        }

        if (!$this->customSlugBehavior) {
            foreach ($this->getI18nAttributeNames('slug') as $attributeName) {
                $this->$attributeName = Inflector::slug($this->$attributeName);
            }
        }
    }

    public function generateUniqueSlug(): void
    {
        foreach ($this->getI18nAttributeNames('slug') as $attributeName) {
            if ($baseSlug = $this->getAttribute($attributeName)) {
                $iteration = 1;

                while (!$this->validate($attributeName) && $iteration < 100) {
                    $baseSlug = mb_substr((string)$baseSlug, 0, $this->slugMaxLength - 1 - ceil($iteration / 10), Yii::$app->charset);
                    $this->setAttribute($attributeName, $baseSlug . '-' . $iteration++);
                }
            }
        }
    }

    /**
     * @return bool whether slugs are required, override this method to not rely on db schema.
     */
    public function isSlugRequired(): bool
    {
        if ($this->_isSlugRequired === null) {
            $schema = static::getDb()->getSchema();
            $this->_isSlugRequired = !$schema->getTableSchema(static::tableName())->getColumn('slug')->allowNull;
        }

        return $this->_isSlugRequired;
    }
}