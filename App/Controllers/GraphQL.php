<?php

namespace App\Controllers;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use App\Entities\StandardProduct;
use App\Entities\Product;
use App\Entities\Category;
use App\Entities\Price;
use App\Entities\Attribute;
use App\Entities\Currency;
use App\Entities\Order;
use App\Entities\StandardOrder;
use Doctrine\ORM\EntityManager;
use RuntimeException;
use Throwable;

/**
 * Controller class handling GraphQL queries and schema definitions.
 */
class GraphQL
{
    private static ?EntityManager $entityManager = null;

    public static function setEntityManager(EntityManager $em): void
    {
        self::$entityManager = $em;
        error_log("[GraphQL] EntityManager set: " . get_class($em));
    }

    /**
     * Handles incoming GraphQL queries and executes them.
     *
     * @return string JSON-encoded response containing the query result or error.
     */
    public static function handle(): string
    {
        $logFile = dirname(__DIR__, 2) . '/logs/graphql.log';

        if (!self::$entityManager) {
            throw new RuntimeException('EntityManager not initialized');
        }

        error_log("[GraphQL] Using EntityManager: " . get_class(self::$entityManager));


        try {
            //------------------------------------------------------------------
            // 1) Define sub-types for Currency, Price, and Attribute
            //------------------------------------------------------------------

            $currencyType = new ObjectType([
                'name' => 'Currency',
                'fields' => [
                    'label' => ['type' => Type::string(), 'resolve' => fn (Currency $currency) => $currency->getLabel()],
                    'symbol' => ['type' => Type::string(), 'resolve' => fn (Currency $currency) => $currency->getSymbol()],
                ],
            ]);

            $attributeType = new ObjectType([
                'name' => 'Attribute',
                'fields' => [
                    'id' => ['type' => Type::int(), 'resolve' => fn (Attribute $attr) => $attr->getId()],
                    'name' => ['type' => Type::string(), 'resolve' => fn (Attribute $attr) => $attr->getName()],
                    'items' => [
                        'type' => Type::listOf(
                            new ObjectType([
                                'name' => 'AttributeItem',
                                'fields' => [
                                    'value' => Type::string(),
                                    'displayValue' => Type::string(),
                                ]
                            ])
                        ),
                        'resolve' => fn (Attribute $attr) => array_map(
                            fn ($item) => [
                                'value' => is_array($item) ? $item['value'] : json_decode($item, true)['value'] ?? '',
                                'displayValue' => is_array($item) ? $item['displayValue'] : json_decode($item, true)['displayValue'] ?? '',
                            ],
                            $attr->getItems()
                        ),
                    ],
                    'product_id' => ['type' => Type::int(), 'resolve' => fn (Attribute $attr) => $attr->getProduct()->getId()],
                ],
            ]);

            $priceType = new ObjectType([
                'name' => 'Price',
                'fields' => [
                    'id' => ['type' => Type::int(), 'resolve' => fn (Price $price) => $price->getId()],
                    'amount' => ['type' => Type::float(), 'resolve' => fn (Price $price) => $price->getAmount()],
                    'currency' => ['type' => $currencyType, 'resolve' => fn (Price $price) => $price->getCurrency()],
                    'product_id' => ['type' => Type::int(), 'resolve' => fn (Price $price) => $price->getProduct()->getId()],
                ],
            ]);

            $categoryType = new ObjectType([
                'name' => 'Category',
                'fields' => [
                    'id' => ['type' => Type::int(), 'resolve' => fn (Category $category) => $category->getId()],
                    'name' => ['type' => Type::string(), 'resolve' => fn (Category $category) => $category->getName()],
                ],
            ]);

            //------------------------------------------------------------------
            // 2) Define the Product type and Order Type
            //------------------------------------------------------------------
            $productType = new ObjectType([
                'name' => 'Product',
                'fields' => [
                    'id' => ['type' => Type::int(), 'resolve' => fn (Product $product) => $product->getId()],
                    'name' => ['type' => Type::string(), 'resolve' => fn (Product $product) => $product->getName()],
                    'brand' => ['type' => Type::string(), 'resolve' => fn (Product $product) => $product->getBrand()],
                    'inStock' => ['type' => Type::boolean(), 'resolve' => fn (Product $product) => $product->isInStock()],
                    'description' => ['type' => Type::string(), 'resolve' => fn (Product $product) => $product->getDescription()],
                    'gallery' => ['type' => Type::listOf(Type::string()), 'resolve' => fn (Product $product) => $product->getGallery()],
                    'category' => ['type' => $categoryType, 'resolve' => fn (Product $product) => $product->getCategory()],
                    'attributes' => ['type' => Type::listOf($attributeType), 'resolve' => fn (Product $product) => $product->getAttributes()->toArray()],
                    'price' => ['type' => $priceType, 'resolve' => fn (Product $product) => $product->getPrice()],
                ],
            ]);

            $orderType = new ObjectType([
                'name' => 'Order',
                'fields' => [
                    'id' => ['type' => Type::int(), 'resolve' => fn (Order $order) => $order->getId()],
                    'product' => ['type' => $productType, 'resolve' => fn (Order $order) => $order->getProduct()],
                    'quantity' => ['type' => Type::int(), 'resolve' => fn (Order $order) => $order->getQuantity()],
                    'unit_price' => ['type' => Type::float(), 'resolve' => fn (Order $order) => $order->getUnitPrice()],
                    'total' => ['type' => Type::float(), 'resolve' => fn (Order $order) => $order->getTotal()],
                    'created_at' => ['type' => Type::string(), 'resolve' => fn (Order $order) => $order->getCreatedAt()->format('Y-m-d H:i:s')],
                ],
            ]);

            //------------------------------------------------------------------
            // 3) Define the Query type
            //------------------------------------------------------------------
            $queryType = new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'categories' => [
                        'type' => Type::listOf($categoryType),
                        'resolve' => function () use ($logFile) {
                            $result = self::$entityManager->getRepository(Category::class)->findAll();
                            error_log("Query executed: categories\n", 3, $logFile);
                            return $result;
                        },
                    ],
                    'products' => [
                        'type' => Type::listOf($productType),
                        'resolve' => function () use ($logFile) {
                            $result = self::$entityManager->getRepository(Product::class)->findAll();
                            error_log("Query executed: products\n", 3, $logFile);
                            return $result;
                        },
                    ],
                    'product' => [
                        'type' => $productType,
                        'args' => [
                            'id' => ['type' => Type::nonNull(Type::int()), 'description' => 'Product ID'],
                        ],
                        'resolve' => function ($root, array $args) use ($logFile) {
                            $result = self::$entityManager->find(Product::class, $args['id']);
                            error_log("Query executed: product with ID " . $args['id'] . "\n", 3, $logFile);
                            return $result;
                        },
                    ],
                    'orders' => [
                        'type' => Type::listOf($orderType),
                        'resolve' => function () use ($logFile) {
                            try {
                                $orders = self::$entityManager->getRepository(StandardOrder::class)->findAll();
                                error_log("Orders queried, found: " . count($orders) . "\n", 3, $logFile);
                                return $orders;
                            } catch (Throwable $e) {
                                error_log("Orders query failed: {$e->getMessage()}\n", 3, $logFile);
                                throw $e;
                            }
                        }
                    ]
                ],
            ]);

            //------------------------------------------------------------------
            // 4) Define the Mutation type
            //------------------------------------------------------------------
            $mutationType = new ObjectType([
                'name' => 'Mutation',
                'fields' => [
                    'createOrder' => [
                        'type' => $orderType,
                        'args' => [
                            'productId' => ['type' => Type::nonNull(Type::int())],
                            'quantity' => ['type' => Type::nonNull(Type::int())],
                        ],
                        'resolve' => function ($root, array $args) use ($logFile) {
                            try {
                                $orderFactory = new \App\Factories\OrderFactory(self::$entityManager);
                                $order = $orderFactory->createOrder($args['productId'], $args['quantity']);

                                self::$entityManager->persist($order);
                                self::$entityManager->flush();

                                error_log("Order created: Product ID " . $args['productId'] . ", Quantity " . $args['quantity'] . "\n", 3, $logFile);
                                return $order;
                            } catch (Throwable $e) {
                                error_log("Order creation failed: " . $e->getMessage() . "\n", 3, $logFile);
                                throw new RuntimeException($e->getMessage());
                            }
                        },
                    ],
                    'createProduct' => [
                        'type' => $productType,
                        'args' => [
                            'name' => ['type' => Type::nonNull(Type::string())],
                            'category' => ['type' => Type::nonNull(Type::string())],
                            'brand' => ['type' => Type::string()],
                            'description' => ['type' => Type::string()],
                            'inStock' => ['type' => Type::boolean()],
                            'gallery' => ['type' => Type::listOf(Type::string())],
                            'attributes' => ['type' => Type::listOf(new InputObjectType([
                                'name' => 'AttributeInput',
                                'fields' => [
                                    'name' => ['type' => Type::nonNull(Type::string())],
                                    'items' => ['type' => Type::listOf(new InputObjectType([
                                        'name' => 'AttributeItemInput',
                                        'fields' => [
                                            'value' => ['type' => Type::nonNull(Type::string())],
                                            'displayValue' => ['type' => Type::nonNull(Type::string())]
                                        ]
                                    ]))]
                                ]
                            ]))],
                            'price' => ['type' => new InputObjectType([
                                'name' => 'PriceInput',
                                'fields' => [
                                    'amount' => ['type' => Type::nonNull(Type::float())],
                                    'currency' => ['type' => new InputObjectType([
                                        'name' => 'CurrencyInput',
                                        'fields' => [
                                            'label' => ['type' => Type::nonNull(Type::string())],
                                            'symbol' => ['type' => Type::nonNull(Type::string())]
                                        ]
                                    ])]
                                ]
                            ])]
                        ],
                        'resolve' => function ($root, array $args) use ($logFile) {
                            try {
                                $category = self::$entityManager->getRepository(Category::class)
                                    ->findOneBy(['name' => $args['category']]);

                                if (!$category) {
                                    throw new RuntimeException("Category not found: {$args['category']}");
                                }

                                // Create product using factory
                                $product = \App\Factories\ProductFactory::create($args['category'], $category);

                                // Set basic properties
                                $product->setName($args['name'])
                                    ->setBrand($args['brand'] ?? '')
                                    ->setDescription($args['description'] ?? '')
                                    ->setInStock($args['inStock'] ?? true)
                                    ->setGallery($args['gallery'] ?? []);

                                // Add attributes if provided
                                if (isset($args['attributes'])) {
                                    foreach ($args['attributes'] as $attr) {
                                        if (!$product->addAttribute($attr['name'], $attr['items'])) {
                                            throw new RuntimeException("Invalid attribute: {$attr['name']} for category {$args['category']}");
                                        }
                                    }
                                }

                                // Set price if provided
                                if (isset($args['price'])) {
                                    $product->setPrice(
                                        $args['price']['amount'],
                                        $args['price']['currency']['label'],
                                        $args['price']['currency']['symbol']
                                    );
                                }

                                self::$entityManager->persist($product);
                                self::$entityManager->flush();

                                error_log("Product created successfully: {$product->getName()}\n", 3, $logFile);
                                return $product;
                            } catch (Throwable $e) {
                                error_log("Product creation failed: {$e->getMessage()}\n", 3, $logFile);
                                throw $e;
                            }
                        }
                    ],
                    'updateProduct' => [
                        'type' => $productType,
                        'args' => [
                            'id' => ['type' => Type::nonNull(Type::int())],
                            'name' => ['type' => Type::string()],
                            'brand' => ['type' => Type::string()],
                            'description' => ['type' => Type::string()],
                            'inStock' => ['type' => Type::boolean()],
                            'gallery' => ['type' => Type::listOf(Type::string())]
                        ],
                        'resolve' => function ($root, array $args) {
                            $product = self::$entityManager->find(Product::class, $args['id']);
                            if (!$product) {
                                throw new RuntimeException("Product not found: {$args['id']}");
                            }

                            if (isset($args['name'])) {
                                $product->setName($args['name']);
                            }
                            if (isset($args['brand'])) {
                                $product->setBrand($args['brand']);
                            }
                            if (isset($args['description'])) {
                                $product->setDescription($args['description']);
                            }
                            if (isset($args['inStock'])) {
                                $product->setInStock($args['inStock']);
                            }
                            if (isset($args['gallery'])) {
                                $product->setGallery($args['gallery']);
                            }

                            self::$entityManager->flush();
                            return $product;
                        }
                    ],
                    'deleteProduct' => [
                        'type' => Type::boolean(),
                        'args' => [
                            'id' => ['type' => Type::nonNull(Type::int())]
                        ],
                        'resolve' => function ($root, array $args) use ($logFile) {
                            try {
                                $product = self::$entityManager->find(StandardProduct::class, $args['id']);
                                if (!$product) {
                                    throw new RuntimeException("Product with ID {$args['id']} not found");
                                }

                                self::$entityManager->remove($product);
                                self::$entityManager->flush();

                                error_log("Product deleted successfully: ID {$args['id']}\n", 3, $logFile);
                                return true;
                            } catch (Throwable $e) {
                                error_log("Product deletion failed: {$e->getMessage()}\n", 3, $logFile);
                                throw $e;
                            }
                        }
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

            if (isset($output['errors'])) {
                error_log("GraphQL errors: " . json_encode($output['errors']) . "\n", 3, $logFile);
            } else {
                error_log("GraphQL query executed successfully\n", 3, $logFile);
            }
        } catch (Throwable $e) {
            $output = ['error' => ['message' => $e->getMessage()]];
            error_log("GraphQL execution failed: " . $e->getMessage() . "\n", 3, $logFile);
        }

        // Set JSON headers and return the result
        header('Content-Type: application/json; charset=UTF-8');
        return json_encode($output);
    }
}
