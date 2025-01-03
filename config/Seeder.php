<?php

namespace Config;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use App\Factories\ProductFactory;
use App\Entities\{
    Category,
    Product,
    Price,
    Attribute,
    Order,
    StandardProduct
};

/**
 * Class Seeder
 *
 * Handles the initialization and seeding of the database for testing purposes.
 */
class Seeder
{
    private static function cleanDatabase(EntityManager $entityManager): void
    {
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        try {
            if ($platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform) {
                $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
                $tables = ['prices', 'attributes', 'products', 'categories', 'orders'];
                foreach ($tables as $table) {
                    $connection->executeQuery("DROP TABLE IF EXISTS $table");
                }
                $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
            } else {
                // For SQLite, we can just delete all rows
                $tables = ['prices', 'attributes', 'products', 'categories', 'orders'];
                foreach ($tables as $table) {
                    $connection->executeQuery("DELETE FROM $table");
                }
            }

            // Clear EntityManager to avoid stale references
            $entityManager->clear();
            echo "Database cleaned successfully\n";
        } catch (\Exception $e) {
            echo "Error cleaning database: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public static function seedDatabase(EntityManager $entityManager): void
    {
        echo "Starting database seeding...\n";

        try {
            self::cleanDatabase($entityManager);

            $connection = $entityManager->getConnection();
            $platform = $connection->getDatabasePlatform()->getName();

            // Create schema for all required entities
            $schemaTool = new SchemaTool($entityManager);
            $classes = [
                $entityManager->getClassMetadata(Category::class),
                $entityManager->getClassMetadata(Product::class),
                $entityManager->getClassMetadata(Price::class),
                $entityManager->getClassMetadata(Attribute::class),
                $entityManager->getClassMetadata(Order::class),
            ];

            // Drop and recreate schema
            $schemaTool->dropSchema($classes);
            $schemaTool->createSchema($classes);

            echo "Schema created successfully\n";

            // Load JSON data from the data.json file
            $dataFile = dirname(__DIR__) . '/data/data.json';
            if (!file_exists($dataFile)) {
                throw new \RuntimeException("Data file not found: $dataFile");
            }

            $jsonData = json_decode(file_get_contents($dataFile), true);

            if (!isset($jsonData['data'])) {
                throw new \RuntimeException("Invalid data.json structure. The 'data' key is required.");
            }

            // Pre-register all categories and their attributes from products
            foreach ($jsonData['data']['products'] as $productData) {
                StandardProduct::registerCategory($productData['category']);
                foreach ($productData['attributes'] as $attr) {
                    StandardProduct::addAllowedAttribute($productData['category'], $attr['name']);
                }
            }

            // Initialize categories
            $categories = [];
            foreach ($jsonData['data']['categories'] as $categoryData) {
                $category = new Category();
                $category->setName($categoryData['name']);
                $entityManager->persist($category);
                $categories[$categoryData['name']] = $category;
                echo "Created category: {$categoryData['name']}\n";
            }

            // Flush categories to the database
            $entityManager->flush();
            echo "Categories flushed to database\n";

            // Process and create products with proper category assignment
            foreach ($jsonData['data']['products'] as $productData) {
                $categoryName = $productData['category']; // Get the category name for the product
                echo "Processing product {$productData['name']} with category {$categoryName}\n";

                // Ensure the category exists
                if (!isset($categories[$categoryName])) {
                    throw new \RuntimeException("Category not found: $categoryName");
                }

                // Create product instance using the factory
                $category = $categories[$categoryName];
                $product = ProductFactory::create($categoryName, $category);

                // Populate product fields
                $product->setName($productData['name'])
                    ->setInStock($productData['inStock'])
                    ->setGallery($productData['gallery'])
                    ->setDescription($productData['description'])
                    ->setBrand($productData['brand']);

                // Add attributes to the product
                foreach ($productData['attributes'] as $attrData) {
                    $product->addAttribute($attrData['name'], $attrData['items']);
                }

                // Set the product price (if available)
                if (!empty($productData['prices'])) {
                    $priceData = $productData['prices'][0];
                    $product->setPrice(
                        $priceData['amount'],
                        $priceData['currency']['label'],
                        $priceData['currency']['symbol']
                    );
                }

                // Link product to category and persist
                $category->addProduct($product);
                $entityManager->persist($product);
                echo "Product {$productData['name']} created with category {$categoryName}\n";
            }

            // Flush all products to the database
            $entityManager->flush();
            echo "Products flushed to database\n";

            echo "Database seeded successfully!\n";
        } catch (\Exception $e) {
            // Catch and log any errors during seeding
            echo "Error: " . $e->getMessage() . "\n";
            echo "Trace: " . $e->getTraceAsString() . "\n";
            throw $e; // Rethrow exception after logging
        }
    }
}
