<?php

namespace Tests\UnitaryTesting;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManager;
use Tests\TestSetup;

/**
 * Test suite for GraphQL API.
 */
class GraphQLTest extends TestCase
{
    /**
     * Base URL for the GraphQL API.
     *
     * @var string
     */
    private string $baseUrl;

    /**
     * Data extracted from the JSON seed file.
     *
     * @var array
     */
    private array $jsonData = [];

    /**
     * Shared Doctrine EntityManager instance.
     *
     * @var EntityManager
     */
    protected static $entityManager;

    /**
     * Initializes the shared EntityManager and populates the database before running tests.
     *
     * This method is executed once before any tests are run. It ensures that the database is
     * initialized and populated with the necessary test data.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        // Initialize the EntityManager
        self::$entityManager = TestSetup::initializeEntityManager();

        // Populate the database, ensuring it is clean before populating
        TestSetup::populateDatabase(self::$entityManager);

        // Optional: Verify that the data has been correctly persisted
        $categories = self::$entityManager->getRepository(\App\Entities\Category::class)->findAll();
        $products = self::$entityManager->getRepository(\App\Entities\Product::class)->findAll();

        error_log(sprintf(
            '[GraphQLTest] Database initialized: %d categories, %d products',
            count($categories),
            count($products)
        ));
    }

    /**
     * Sets up the test environment before each test.
     *
     * This method is executed before each test method. It establishes the base URL for the GraphQL API
     * and loads the test data from the JSON seed file.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->baseUrl = 'http://localhost:9000/graphql';

        // Load test data from the JSON file
        $dataFile = __DIR__ . '/../../data/data.json';
        if (!file_exists($dataFile)) {
            throw new \RuntimeException("Data file not found: $dataFile");
        }

        $decodedData = json_decode(file_get_contents($dataFile), true);
        if (!isset($decodedData['data'])) {
            throw new \RuntimeException("Invalid data.json structure. The 'data' key is required.");
        }

        $this->jsonData = $decodedData['data'];
    }

    /**
     * Executes a GraphQL query or mutation via cURL and returns the response.
     *
     * @param array $payload The GraphQL query or mutation payload.
     * @return array The decoded JSON response.
     *
     * @throws \RuntimeException if the cURL request fails or the response cannot be decoded.
     */
    private function executeGraphQL(array $payload): array
    {
        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("cURL error: $error");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(200, $httpCode, "Expected HTTP 200, got $httpCode.");

        $decoded = json_decode($response, true);
        $this->assertIsArray($decoded, 'Failed to decode JSON response.');

        return $decoded;
    }

    /**
     * Test querying all seeded data (categories and products) from the GraphQL API.
     *
     * @return void
     */
    public function testQueryAllSeededData(): void
    {
        $query = [
            'query' => '
            query {
                categories {
                    id
                    name
                }
                products {
                    id
                    name
                    brand
                    inStock
                    description
                    gallery
                    category {
                        id
                        name
                    }
                    attributes {
                        id
                        name
                        items {
                            value
                            displayValue
                        }      
                    }
                    price {
                        amount
                        currency {
                            label
                            symbol
                        }
                    }
                }
            }
        '
        ];

        $responseData = $this->executeGraphQL($query);

        // Ensure the 'data' key exists in the response
        $this->assertArrayHasKey('data', $responseData);

        $data = $responseData['data'];

        // Verify categories
        $this->assertArrayHasKey('categories', $data, 'Response missing "categories".');
        foreach ($data['categories'] as $index => $category) {
            $expectedCategory = $this->jsonData['categories'][$index];
            $this->assertEquals($expectedCategory['name'], $category['name'], "Category mismatch at index $index.");
        }

        // Verify products
        $this->assertArrayHasKey('products', $data, 'Response missing "products".');
        foreach ($data['products'] as $index => $product) {
            $expectedProduct = $this->jsonData['products'][$index];

            // Check product fields
            $this->assertEquals($expectedProduct['name'], $product['name'], "Product name mismatch at index $index.");
            $this->assertEquals($expectedProduct['brand'], $product['brand'], "Product brand mismatch at index $index.");
            $this->assertEquals($expectedProduct['inStock'], $product['inStock'], "Product stock mismatch at index $index.");
            $this->assertEquals($expectedProduct['description'], $product['description'], "Product description mismatch at index $index.");
            $this->assertEquals($expectedProduct['gallery'], $product['gallery'], "Product gallery mismatch at index $index.");

            // Check product category
            $this->assertEquals($expectedProduct['category'], $product['category']['name'], "Category mismatch for product $index.");

            // Check attributes
            foreach ($product['attributes'] as $attrIndex => $attribute) {
                $expectedAttr = $expectedProduct['attributes'][$attrIndex];
                $this->assertEquals($expectedAttr['name'], $attribute['name'], "Attribute name mismatch for product $index.");

                // Validate items within the attribute
                foreach ($attribute['items'] as $itemIndex => $item) {
                    $expectedItem = $expectedAttr['items'][$itemIndex];
                    $expectedItemFiltered = [
                        'value' => $expectedItem['value'],
                        'displayValue' => $expectedItem['displayValue']
                    ];
                    $this->assertEquals($expectedItemFiltered, $item, "Attribute items mismatch for product $index.");
                }
            }

            // Check product price
            if (isset($expectedProduct['prices'])) {
                $expectedPrice = $expectedProduct['prices'][0];
                $this->assertNotNull($product['price'], "Price missing for product $index.");
                $this->assertEquals($expectedPrice['amount'], $product['price']['amount'], "Price amount mismatch for product $index.");
                $this->assertEquals($expectedPrice['currency']['label'], $product['price']['currency']['label'], "Currency label mismatch for product $index.");
                $this->assertEquals($expectedPrice['currency']['symbol'], $product['price']['currency']['symbol'], "Currency symbol mismatch for product $index.");
            }
        }
    }

    /**
     * Test creating a new order via GraphQL mutation.
     *
     * @return void
     */
    public function testCreateOrderMutation(): void
    {
        // Query products first to get valid IDs
        $query = [
            'query' => '
                query {
                    products {
                        id
                        name
                        price {
                            amount
                        }
                        attributes {
                            name
                            items {
                                value
                            }
                        }
                    }
                }
            '
        ];

        $productsResponse = $this->executeGraphQL($query);
        $this->assertArrayHasKey('data', $productsResponse);
        $this->assertNotEmpty($productsResponse['data']['products']);

        // Prepare order items
        $orderProducts = [];
        foreach (array_slice($productsResponse['data']['products'], 0, 2) as $product) {
            $orderProducts[] = [
                'productId' => $product['id'],
                'quantity' => 2,
                'selectedAttributes' => array_map(
                    fn($attr) => [
                        'name' => $attr['name'],
                        'value' => $attr['items'][0]['value']
                    ],
                    $product['attributes']
                )
            ];
        }

        $mutation = [
            'query' => '
                mutation CreateOrder($products: [OrderProductInput!]!) {
                    createOrder(products: $products) {
                        id
                        orderedProducts {
                            product {
                                name
                            }
                            quantity
                            unitPrice
                            total
                            selectedAttributes {
                                name
                                value
                            }
                        }
                        total
                        createdAt
                    }
                }
            ',
            'variables' => [
                'products' => $orderProducts
            ]
        ];

        $responseData = $this->executeGraphQL($mutation);

        // Verify response structure
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('createOrder', $responseData['data']);

        $order = $responseData['data']['createOrder'];
        $this->assertNotNull($order['id']);
        $this->assertNotEmpty($order['orderedProducts']);
        $this->assertCount(count($orderProducts), $order['orderedProducts']);

        // Verify each ordered product
        foreach ($order['orderedProducts'] as $index => $item) {
            $this->assertEquals(2, $item['quantity']);
            $this->assertGreaterThan(0, $item['unitPrice']);
            $this->assertEquals($item['unitPrice'] * $item['quantity'], $item['total']);
        }

        // Verify order total
        $this->assertGreaterThan(0, $order['total']);
        $expectedTotal = array_reduce(
            $order['orderedProducts'],
            fn($sum, $item) => $sum + $item['total'],
            0
        );
        $this->assertEquals($expectedTotal, $order['total']);
    }

    /**
     * Test creating a new product via GraphQL mutation.
     *
     * @return void
     */
    public function testCreateProductMutation(): void
    {
        $mutation = [
            'query' => '
            mutation {
                createProduct(
                    name: "Test Product"
                    category: "clothes"
                    brand: "Test Brand"
                    description: "Test Description"
                    inStock: true
                    attributes: [
                        {
                            name: "Size"
                            items: [
                                { value: "S", displayValue: "Small" }
                            ]
                        }
                    ]
                    price: {
                        amount: 99.99
                        currency: {
                            label: "USD"
                            symbol: "$"
                        }
                    }
                ) {
                    id
                    name
                    brand
                    description
                    inStock
                }
            }
        '
        ];

        $responseData = $this->executeGraphQL($mutation);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('createProduct', $responseData['data']);

        $product = $responseData['data']['createProduct'];
        $this->assertNotNull($product['id']);
        $this->assertEquals('Test Product', $product['name']);
        $this->assertEquals('Test Brand', $product['brand']);
        $this->assertEquals('Test Description', $product['description']);
        $this->assertTrue($product['inStock']);
    }

    /**
     * Test updating an existing product via GraphQL mutation.
     *
     * @return void
     */
    public function testUpdateProductMutation(): void
    {
        // First, obtain the ID of the product to be updated
        $query = [
            'query' => '
                query {
                    products {
                        id
                        name
                    }
                }
            '
        ];

        $queryResponse = $this->executeGraphQL($query);
        $this->assertArrayHasKey('data', $queryResponse);
        $this->assertArrayHasKey('products', $queryResponse['data']);
        $this->assertNotEmpty($queryResponse['data']['products'], 'No products found to update.');

        $productId = $queryResponse['data']['products'][0]['id'];

        $mutation = [
            'query' => '
                mutation {
                    updateProduct(
                        id: ' . $productId . '
                        name: "Updated Product Test"
                        description: "Updated Description"
                    ) {
                        id
                        name
                        description
                    }
                }
            '
        ];

        $responseData = $this->executeGraphQL($mutation);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('updateProduct', $responseData['data']);

        $product = $responseData['data']['updateProduct'];
        $this->assertEquals('Updated Product Test', $product['name']);
        $this->assertEquals('Updated Description', $product['description']);

        // Optional: Reset the product name and description to original state after the test
        $resetMutation = [
            'query' => '
                mutation {
                    updateProduct(
                        id: ' . $productId . '
                        name: "Original Product Name"
                        description: "Original Description"
                    ) {
                        id
                        name
                        description
                    }
                }
            '
        ];

        $this->executeGraphQL($resetMutation);
    }

    /**
     * Test deleting a product via GraphQL mutation.
     *
     * @return void
     */
    public function testDeleteProductMutation(): void
    {
        // Create a product to delete
        $createMutation = [
            'query' => '
                mutation {
                    createProduct(
                        name: "Product to Delete"
                        category: "tech"
                        brand: "Test Brand"
                        description: "Test Description"
                        inStock: true
                    ) {
                        id
                    }
                }
            '
        ];

        $createResponse = $this->executeGraphQL($createMutation);
        $this->assertArrayHasKey('data', $createResponse);
        $this->assertArrayHasKey('createProduct', $createResponse['data']);

        $productId = $createResponse['data']['createProduct']['id'];

        // Delete the product
        $deleteMutation = [
            'query' => "
                mutation {
                    deleteProduct(id: $productId)
                }
            "
        ];

        $responseData = $this->executeGraphQL($deleteMutation);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('deleteProduct', $responseData['data']);
        $this->assertTrue($responseData['data']['deleteProduct']);

        // Verify deletion
        $verifyQuery = [
            'query' => "
                query {
                    products {
                        id
                    }
                }
            "
        ];

        $verifyResponse = $this->executeGraphQL($verifyQuery);
        $remainingIds = array_column($verifyResponse['data']['products'], 'id');
        $this->assertNotContains($productId, $remainingIds);
    }

    /**
     * Test querying orders via GraphQL.
     *
     * @return void
     */
    public function testQueryOrders(): void
    {
        // First create an order
        $createOrderMutation = [
            'query' => '
                mutation {
                    createOrder(products: [
                        {
                            productId: 1,
                            quantity: 2,
                            selectedAttributes: [
                                { name: "Size", value: "40" }
                            ]
                        },
                        {
                            productId: 2,
                            quantity: 1,
                            selectedAttributes: [
                                { name: "Color", value: "Black" }
                            ]
                        }
                    ]) {
                        id
                        total
                    }
                }
            '
        ];

        $createResponse = $this->executeGraphQL($createOrderMutation);
        $this->assertArrayHasKey('data', $createResponse);
        $this->assertArrayHasKey('createOrder', $createResponse['data']);
        $newOrderId = $createResponse['data']['createOrder']['id'];

        // Query all orders
        $query = [
            'query' => '
                query {
                    orders {
                        id
                        orderedProducts {
                            product {
                                name
                            }
                            quantity
                            unitPrice
                            total
                            selectedAttributes {
                                name
                                value
                            }
                        }
                        total
                        createdAt
                    }
                }
            '
        ];

        $responseData = $this->executeGraphQL($query);

        // Verify response
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('orders', $responseData['data']);
        $this->assertNotEmpty($responseData['data']['orders']);

        // Find and verify the created order
        $found = false;
        foreach ($responseData['data']['orders'] as $order) {
            if ($order['id'] === $newOrderId) {
                $found = true;
                $this->assertNotEmpty($order['orderedProducts']);
                $this->assertGreaterThan(0, $order['total']);

                // Verify total matches sum of items
                $calculatedTotal = array_reduce(
                    $order['orderedProducts'],
                    fn($sum, $item) => $sum + $item['total'],
                    0
                );
                $this->assertEquals($calculatedTotal, $order['total']);
                break;
            }
        }

        $this->assertTrue($found, "Newly created order not found in query results");
    }
}
