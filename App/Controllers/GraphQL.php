<?php

namespace App\Controllers;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use App\Entities\Product;
use App\Entities\Category;
use App\Entities\Price;
use App\Entities\Attribute;
use App\Entities\Currency;
use Doctrine\ORM\EntityManager;
use RuntimeException;
use Throwable;
use Config\Bootstrap;

/**
 * Controller class handling GraphQL queries and schema definitions.
 */
class GraphQL
{
    /**
     * The Doctrine EntityManager instance for database interactions.
     */
    private static ?EntityManager $entityManager = null;

    /**
     * Sets the EntityManager to be used for database operations.
     *
     * @param EntityManager $em The Doctrine EntityManager instance.
     */
    public static function setEntityManager(EntityManager $em): void
    {
        self::$entityManager = $em;
    }

    /**
     * Handles incoming GraphQL queries and executes them.
     *
     * @return string The JSON-encoded response.
     */
    public static function handle()
    {
        try {
            //------------------------------------------------------------------
            // 1) Define sub-types for Currency, Price, and Attribute
            //------------------------------------------------------------------

            // Define the Currency type
            $currencyType = new ObjectType([
                'name' => 'Currency',
                'fields' => [
                    'label' => [
                        'type' => Type::string(),
                        'resolve' => fn(Currency $currency) => $currency->getLabel(),
                    ],
                    'symbol' => [
                        'type' => Type::string(),
                        'resolve' => fn(Currency $currency) => $currency->getSymbol(),
                    ],
                ],
            ]);

            // Define the Attribute type
            $attributeType = new ObjectType([
                'name' => 'Attribute',
                'fields' => [
                    'id' => [
                        'type' => Type::int(),
                        'resolve' => fn(Attribute $attr) => $attr->getId(),
                    ],
                    'name' => [
                        'type' => Type::string(),
                        'resolve' => fn(Attribute $attr) => $attr->getName(),
                    ],
                    'items' => [
                        'type' => Type::listOf(Type::string()),
                        'resolve' => fn(Attribute $attr) => array_map(
                            fn($item) => is_array($item) ? $item['value'] : json_decode($item, true)['value'] ?? '',
                            $attr->getItems()
                        ),
                    ],
                ],
            ]);

            // Define the Price type
            $priceType = new ObjectType([
                'name' => 'Price',
                'fields' => [
                    'id' => [
                        'type' => Type::int(),
                        'resolve' => fn(Price $price) => $price->getId(),
                    ],
                    'amount' => [
                        'type' => Type::float(),
                        'resolve' => fn(Price $price) => $price->getAmount(),
                    ],
                    'currency' => [
                        'type' => $currencyType,
                        'resolve' => fn(Price $price) => $price->getCurrency(),
                    ],
                ],
            ]);

            // Define the Category type
            $categoryType = new ObjectType([
                'name' => 'Category',
                'fields' => [
                    'id' => [
                        'type' => Type::int(),
                        'resolve' => fn(Category $category) => $category->getId(),
                    ],
                    'name' => [
                        'type' => Type::string(),
                        'resolve' => fn(Category $category) => $category->getName(),
                    ],
                ],
            ]);

            //------------------------------------------------------------------
            // 2) Define the Product type
            //------------------------------------------------------------------
            $productType = new ObjectType([
                'name' => 'Product',
                'fields' => [
                    'id' => ['type' => Type::int(), 'resolve' => fn(Product $product) => $product->getId()],
                    'name' => ['type' => Type::string(), 'resolve' => fn(Product $product) => $product->getName()],
                    'brand' => ['type' => Type::string(), 'resolve' => fn(Product $product) => $product->getBrand()],
                    'inStock' => ['type' => Type::boolean(), 'resolve' => fn(Product $product) => $product->isInStock()],
                    'description' => ['type' => Type::string(), 'resolve' => fn(Product $product) => $product->getDescription()],
                    'gallery' => ['type' => Type::listOf(Type::string()), 'resolve' => fn(Product $product) => $product->getGallery()],
                    'category' => ['type' => $categoryType, 'resolve' => fn(Product $product) => $product->getCategory()],
                    'attributes' => ['type' => Type::listOf($attributeType), 'resolve' => fn(Product $product) => $product->getAttributes()->toArray()],
                    'price' => ['type' => $priceType, 'resolve' => fn(Product $product) => $product->getPrice()],
                ],
            ]);

            //------------------------------------------------------------------
            // 3) Define the Query type
            //------------------------------------------------------------------
            $queryType = new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'products' => [
                        'type' => Type::listOf($productType),
                        'resolve' => fn() => self::$entityManager->getRepository(Product::class)->findAll(),
                    ],
                    'product' => [
                        'type' => $productType,
                        'args' => [
                            'id' => ['type' => Type::nonNull(Type::int()), 'description' => 'Product ID'],
                        ],
                        'resolve' => fn($root, array $args) => self::$entityManager->find(Product::class, $args['id']),
                    ],
                ],
            ]);

            //------------------------------------------------------------------
            // 4) Define the Mutation type
            //------------------------------------------------------------------
            $mutationType = new ObjectType([
                'name' => 'Mutation',
                'fields' => [
                    'sum' => [
                        'type' => Type::int(),
                        'args' => ['x' => ['type' => Type::int()], 'y' => ['type' => Type::int()]],
                        'resolve' => fn($root, array $args) => $args['x'] + $args['y'],
                    ],
                ],
            ]);

            //------------------------------------------------------------------
            // 5) Build schema and process incoming query
            //------------------------------------------------------------------
            $schema = new Schema(
                (new SchemaConfig())
                    ->setQuery($queryType)
                    ->setMutation($mutationType)
            );

            $rawInput = file_get_contents('php://input') ?? throw new RuntimeException('Failed to get php://input');
            $input = json_decode($rawInput, true);
            $query = $input['query'] ?? '';
            $variables = $input['variables'] ?? null;

            $result = GraphQLBase::executeQuery($schema, $query, null, null, $variables);
            $output = $result->toArray();
        } catch (Throwable $e) {
            $output = ['error' => ['message' => $e->getMessage()]];
        }

        // Set JSON headers and return the result
        header('Content-Type: application/json; charset=UTF-8');
        return json_encode($output);
    }
}
