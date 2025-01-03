<?php

namespace App\Factories;

use App\Entities\Product;
use App\Entities\Category;
use App\Entities\StandardProduct;
use InvalidArgumentException;

class ProductFactory
{
    private static bool $initialized = false;

    private static function initializeCategories(): void
    {
        if (!self::$initialized) {
            StandardProduct::registerCategory('tech', [
                'Capacity',
                'Color',
                'With USB 3 ports',
                'Touch ID in keyboard'
            ]);

            StandardProduct::registerCategory('clothes', [
                'Size',
                'Color'
            ]);

            self::$initialized = true;
        }
    }

    public static function create(string $categoryName, Category $category): Product
    {
        self::initializeCategories();

        if (!StandardProduct::isCategoryRegistered($categoryName)) {
            StandardProduct::registerCategory($categoryName);
        }

        $product = new StandardProduct();
        $product->setCategory($category);

        return $product;
    }
}
