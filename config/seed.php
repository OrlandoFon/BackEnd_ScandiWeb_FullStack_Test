<?php

$entityManager = require 'bootstrap.php';

use App\Entities\TechProduct;
use App\Entities\ClothesProduct;
use App\Entities\TechAttribute;
use App\Entities\ClothesAttribute;
use App\Entities\AbstractAttributeItem;
use App\Entities\Price;
use App\Entities\Currency;

echo "Starting database seeding...\n";

try {
    $startTime = microtime(true);

    // Load JSON data
    $jsonData = json_decode(file_get_contents(__DIR__ . '/../data/data.json'), true);

    foreach ($jsonData['data']['products'] as $productData) {
        // Create product based on category
        $product = $productData['category'] === 'tech'
            ? new TechProduct()
            : new ClothesProduct();

        // Set basic product data
        $product
            ->setName($productData['name'])
            ->setInStock($productData['inStock'])
            ->setGallery($productData['gallery'])
            ->setDescription($productData['description'])
            ->setBrand($productData['brand'])
            ->setCategory($productData['category']);

        // Handle price
        foreach ($productData['prices'] as $priceData) {
            $price = new Price();
            $price->setAmount($priceData['amount']);

            $currency = new Currency();
            $currency
                ->setLabel($priceData['currency']['label'])
                ->setSymbol($priceData['currency']['symbol']);

            $price->setCurrency($currency);
            $price->setProduct($product);
            $entityManager->persist($price);
        }

        // Handle attributes
        foreach ($productData['attributes'] as $attributeData) {
            $attribute = $productData['category'] === 'tech'
                ? new TechAttribute()
                : new ClothesAttribute();

            $attribute
                ->setName($attributeData['name'])
                ->setType($attributeData['type']);

            foreach ($attributeData['items'] as $itemData) {
                $item = new AbstractAttributeItem();
                $item
                    ->setDisplayValue($itemData['displayValue'])
                    ->setValue($itemData['value']);

                $attribute->addItem($item);
                $entityManager->persist($item);
            }

            $attribute->setProduct($product);
            $entityManager->persist($attribute);
        }

        $entityManager->persist($product);
    }

    $entityManager->flush();

    $elapsedTime = round(microtime(true) - $startTime, 2);
    echo "Database successfully populated in {$elapsedTime} seconds!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
