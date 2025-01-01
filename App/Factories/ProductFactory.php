<?php

namespace App\Factories;

use App\Entities\Product;
use App\Entities\Category;
use InvalidArgumentException;

/**
 * Factory class to create products dynamically based on type.
 */
class ProductFactory
{
    /**
     * Array mapping product types to their respective classes.
     */
    private static array $productTypes = [];

    /**
     * Array mapping category names to valid product types.
     */
    private static array $categoryValidations = [];

    /**
     * Registers a new product type.
     *
     * @param string $type The type identifier (e.g., 'tech').
     * @param string $className The fully qualified class name for the product.
     */
    public static function registerProductType(string $type, string $className): void
    {
        if (!is_subclass_of($className, Product::class)) {
            throw new InvalidArgumentException("Class $className must extend " . Product::class);
        }

        self::$productTypes[$type] = $className;
    }

    /**
     * Registers valid product types for a category.
     *
     * @param string $categoryName The name of the category.
     * @param array $productTypes An array of product type identifiers.
     */
    public static function registerCategory(string $categoryName, array $productTypes): void
    {
        foreach ($productTypes as $type) {
            if (!isset(self::$productTypes[$type])) {
                throw new InvalidArgumentException("Product type $type is not registered.");
            }
        }

        self::$categoryValidations[$categoryName] = $productTypes;
    }

    /**
     * Creates a product instance based on the provided type and category.
     *
     * @param string $type The product type identifier.
     * @param Category $category The category associated with the product.
     * @return Product The created product instance.
     */
    public static function create(string $type, Category $category): Product
    {
        if (!isset(self::$productTypes[$type])) {
            throw new InvalidArgumentException("Product type $type is not registered.");
        }

        if (!in_array($type, self::$categoryValidations[$category->getName()] ?? [], true)) {
            throw new InvalidArgumentException("Product type $type is not valid for category " . $category->getName());
        }

        $className = self::$productTypes[$type];
        $product = new $className();
        $product->setCategory($category);

        return $product;
    }
}
