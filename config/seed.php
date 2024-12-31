<?php

$entityManager = require 'bootstrap.php';

use App\Entities\Tech;
use App\Entities\Clothes;
use App\Entities\Product;

echo "Starting database seeding...\n";

try {
    // Drop existing tables
    $connection = $entityManager->getConnection();
    $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
    $connection->executeQuery('DROP TABLE IF EXISTS products');
    $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');

    // Create schema
    $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
    $classes = [
        $entityManager->getClassMetadata(Product::class)
    ];
    $schemaTool->createSchema($classes);

    // Load JSON data
    $jsonData = json_decode(file_get_contents(__DIR__ . '/../data/data.json'), true);

    // Create products only (no separate categories needed)
    foreach ($jsonData['data']['products'] as $productData) {
        $product = $productData['category'] === 'tech' ? new Tech() : new Clothes();

        $product->setName($productData['name'])
            ->setInStock($productData['inStock'])
            ->setGallery($productData['gallery'])
            ->setDescription($productData['description'])
            ->setBrand($productData['brand'])
            ->setCategoryName($productData['category']);

        // Add attributes
        foreach ($productData['attributes'] as $attributeData) {
            $product->addAttribute($attributeData['name'], $attributeData['items']);
        }

        // Add price
        if (!empty($productData['prices'])) {
            $priceData = $productData['prices'][0];
            $product->addPrice(
                $priceData['amount'],
                $priceData['currency']['label'],
                $priceData['currency']['symbol']
            );
        }

        $entityManager->persist($product);
    }

    $entityManager->flush();

    echo "Database seeded successfully!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
