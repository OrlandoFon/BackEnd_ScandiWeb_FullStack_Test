<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class StandardProduct extends Product
{
    private static array $categories = [];
    private static array $allowedAttributes = [];

    public static function registerCategory(string $name, array $attributes = []): void
    {
        self::$categories[$name] = true;
        self::$allowedAttributes[$name] = $attributes;
    }

    public static function addAllowedAttribute(string $category, string $attributeName): void
    {
        if (!isset(self::$allowedAttributes[$category])) {
            self::$allowedAttributes[$category] = [];
        }
        if (!in_array($attributeName, self::$allowedAttributes[$category], true)) {
            self::$allowedAttributes[$category][] = $attributeName;
        }
    }

    public static function isCategoryRegistered(string $name): bool
    {
        return isset(self::$categories[$name]);
    }

    public function addAttribute(string $name, array $items): bool
    {
        // Allow any attribute if none are registered for this category
        $category = $this->getCategory()->getName();
        if (empty(self::$allowedAttributes[$category])) {
            return parent::addAttribute($name, $items);
        }

        if ($this->validateAttribute($name)) {
            return parent::addAttribute($name, $items);
        }
        return false;
    }

    protected function validateAttribute(string $name): bool
    {
        $category = $this->getCategory()->getName();
        return empty(self::$allowedAttributes[$category]) ||
            in_array($name, self::$allowedAttributes[$category], true);
    }
}
