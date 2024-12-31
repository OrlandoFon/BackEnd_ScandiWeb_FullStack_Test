<?php

$entityManager = require 'bootstrap.php';

use App\Entities\Product;
use App\Entities\Category;
use App\Entities\Attribute;
use App\Entities\Price;
use App\Entities\Currency;

echo "Starting database seeding...\n";

try {
    // Drop existing tables
    $connection = $entityManager->getConnection();
    $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
    $connection->executeQuery('DROP TABLE IF EXISTS prices');
    $connection->executeQuery('DROP TABLE IF EXISTS attributes');
    $connection->executeQuery('DROP TABLE IF EXISTS products');
    $connection->executeQuery('DROP TABLE IF EXISTS categories');
    $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');

    // Create schema
    $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
    $classes = [
        $entityManager->getClassMetadata(Category::class),
        $entityManager->getClassMetadata(Product::class),
        $entityManager->getClassMetadata(Attribute::class),
        $entityManager->getClassMetadata(Price::class)
    ];
    $schemaTool->createSchema($classes);

    // Load JSON data
    $jsonData = json_decode(file_get_contents(__DIR__ . '/../data/data.json'), true);

    // Create categories
    $categories = [];
    foreach ($jsonData['data']['categories'] as $categoryData) {
        $category = new Category();
        $category->setName($categoryData['name']);
        $entityManager->persist($category);
        $categories[$categoryData['name']] = $category;
    }
    $entityManager->flush();

    // Create products with attributes and prices
    foreach ($jsonData['data']['products'] as $productData) {
        $product = new Product();
        $product->setName($productData['name'])
            ->setInStock($productData['inStock'])
            ->setGallery($productData['gallery'])
            ->setDescription($productData['description'])
            ->setBrand($productData['brand']);

        if (isset($categories[$productData['category']])) {
            $product->setCategory($categories[$productData['category']]);
        }

        $entityManager->persist($product);
        $entityManager->flush();

        // Create attributes
        foreach ($productData['attributes'] as $attributeData) {
            $attribute = new Attribute();
            $attribute->setName($attributeData['name']);
            $attribute->setItems($attributeData['items']);
            $attribute->setProduct($product);
            $entityManager->persist($attribute);
        }

        // Create price and currency
        if (!empty($productData['prices'])) {
            $priceData = $productData['prices'][0]; // Get first price

            $currency = new Currency();
            $currency->setLabel($priceData['currency']['label'])
                ->setSymbol($priceData['currency']['symbol']);

            $price = new Price();
            $price->setAmount($priceData['amount'])
                ->setCurrency($currency)
                ->setProduct($product);

            $entityManager->persist($price);
            $product->setPrice($price);
        }
    }

    $entityManager->flush();

    echo "Database seeded successfully!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
