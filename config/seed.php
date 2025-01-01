<?php

use Config\Bootstrap;
use App\Factories\ProductFactory;
use App\Entities\Category;
use App\Entities\Product;
use App\Entities\Price;
use App\Entities\Attribute;
use App\Entities\Tech;
use App\Entities\Clothes;

// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Register product types
ProductFactory::registerProductType('tech', Tech::class);
ProductFactory::registerProductType('clothes', Clothes::class);

// Register categories and their valid product types
ProductFactory::registerCategory('tech', ['tech']);
ProductFactory::registerCategory('clothes', ['clothes']);
ProductFactory::registerCategory('all', ['tech', 'clothes']);

// Load database parameters and initialize EntityManager
$entityManager = Bootstrap::initEntityManager(require __DIR__ . '/../config/db_params.php');

echo "Starting database seeding...\n";

try {
    // Disable foreign key checks to allow table deletion without constraints
    $connection = $entityManager->getConnection();
    $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');

    // Drop existing tables to ensure a clean start
    $connection->executeQuery('DROP TABLE IF EXISTS prices');
    $connection->executeQuery('DROP TABLE IF EXISTS attributes');
    $connection->executeQuery('DROP TABLE IF EXISTS products');
    $connection->executeQuery('DROP TABLE IF EXISTS categories');

    // Re-enable foreign key checks after dropping tables
    $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');

    // Create schema for all required entities
    $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
    $classes = [
        $entityManager->getClassMetadata(Category::class),
        $entityManager->getClassMetadata(Product::class),
        $entityManager->getClassMetadata(Price::class),
        $entityManager->getClassMetadata(Attribute::class),
    ];
    $schemaTool->createSchema($classes);

    echo "Schema created successfully\n";

    // Load JSON data from the data.json file
    $jsonData = json_decode(file_get_contents(__DIR__ . '/../data/data.json'), true);

    // Initialize categories
    $categories = [];
    foreach ($jsonData['data']['categories'] as $categoryData) {
        $category = new Category();
        $category->setName($categoryData['name']); // Set category name
        $entityManager->persist($category); // Mark category for persistence
        $categories[$categoryData['name']] = $category; // Store category for reference
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
}
