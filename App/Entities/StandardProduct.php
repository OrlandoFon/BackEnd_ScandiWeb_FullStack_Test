<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class representing a standard product.
 * This is a concrete implementation of the abstract Product class.
 */
#[ORM\Entity]
class StandardProduct extends Product
{
    /**
     * Array of registered categories.
     *
     * @var array
     */
    private static array $categories = [];

    /**
     * Array of allowed attributes for each category.
     *
     * @var array
     */
    private static array $allowedAttributes = [];

    /**
     * Registers a new category with optional allowed attributes.
     *
     * @param string $name       The name of the category.
     * @param array  $attributes Optional array of allowed attributes for the category.
     */
    public static function registerCategory(string $name, array $attributes = []): void
    {
        self::$categories[$name] = true;
        self::$allowedAttributes[$name] = $attributes;
    }

    /**
     * Adds an allowed attribute for a specific category.
     *
     * @param string $category      The name of the category.
     * @param string $attributeName The name of the attribute to allow.
     */
    public static function addAllowedAttribute(string $category, string $attributeName): void
    {
        if (!isset(self::$allowedAttributes[$category])) {
            self::$allowedAttributes[$category] = [];
        }
        if (!in_array($attributeName, self::$allowedAttributes[$category], true)) {
            self::$allowedAttributes[$category][] = $attributeName;
        }
    }

    /**
     * Checks if a category is registered.
     *
     * @param string $name The name of the category.
     *
     * @return bool True if the category is registered, false otherwise.
     */
    public static function isCategoryRegistered(string $name): bool
    {
        return isset(self::$categories[$name]);
    }

    /**
     * Adds an attribute to the product if it is valid for the product's category.
     * If no attributes are registered for the category, any attribute is allowed.
     *
     * @param string $name  The name of the attribute.
     * @param array  $items The items associated with the attribute.
     *
     * @return bool True if the attribute was added successfully, false otherwise.
     */
    public function addAttribute(string $name, array $items): bool
    {
        $category = $this->getCategory()->getName();

        // Allow any attribute if no specific attributes are registered for the category.
        if (empty(self::$allowedAttributes[$category])) {
            return parent::addAttribute($name, $items);
        }

        // Validate the attribute before adding it.
        if ($this->validateAttribute($name)) {
            return parent::addAttribute($name, $items);
        }
        return false;
    }

    /**
     * Validates if an attribute is allowed for the product's category.
     *
     * @param string $name The name of the attribute to validate.
     *
     * @return bool True if the attribute is valid, false otherwise.
     */
    protected function validateAttribute(string $name): bool
    {
        $category = $this->getCategory()->getName();
        return empty(self::$allowedAttributes[$category]) ||
            in_array($name, self::$allowedAttributes[$category], true);
    }
}
