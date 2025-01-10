<?php

namespace App\Factories;

use App\Entities\Product;
use App\Entities\Category;
use App\Entities\StandardProduct;
use InvalidArgumentException;

/**
 * Factory class for creating Product instances.
 * This class ensures that products are associated with appropriate categories and attributes.
 */
class ProductFactory
{
    /**
     * Indicates whether categories have been initialized.
     *
     * @var bool
     */
    private static bool $initialized = false;

    /**
     * Initializes default categories and their allowed attributes.
     * This method registers categories and their corresponding attributes
     * if they haven't been initialized yet.
     */
    private static function initializeCategories(): void
    {
        if (!self::$initialized) {
            // Register the 'tech' category with its attributes
            StandardProduct::registerCategory('tech', [
                'Capacity',
                'Color',
                'With USB 3 ports',
                'Touch ID in keyboard'
            ]);

            // Register the 'clothes' category with its attributes
            StandardProduct::registerCategory('clothes', [
                'Size',
                'Color'
            ]);

            // Mark categories as initialized
            self::$initialized = true;
        }
    }

    /**
     * Creates a new Product instance associated with a specific category.
     *
     * @param string   $categoryName The name of the category to associate the product with.
     * @param Category $category     The Category entity to associate with the product.
     *
     * @return Product The newly created Product instance.
     *
     * @throws InvalidArgumentException If the category is invalid.
     */
    public static function create(string $categoryName, Category $category): Product
    {
        // Ensure that default categories are initialized
        self::initializeCategories();

        // Register the category if it hasn't been registered
        if (!StandardProduct::isCategoryRegistered($categoryName)) {
            StandardProduct::registerCategory($categoryName);
        }

        // Create a new StandardProduct and associate it with the category
        $product = new StandardProduct();
        $product->setCategory($category);

        return $product;
    }
}
