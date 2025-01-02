<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GraphQL API.
 */
class GraphQLTest extends TestCase
{
    /**
     * Base URL for GraphQL API.
     */
    private string $baseUrl;

    /**
     * Data extracted from data.json for test verification.
     */
    private array $jsonData = [];

    /**
     * Setup method to initialize test environment.
     */
    protected function setUp(): void
    {
        $this->baseUrl = 'http://localhost:9000/graphql';

        // Load and parse data.json to use as expected test data
        $dataFile = __DIR__ . '/../data/data.json';
        $jsonContent = json_decode(file_get_contents($dataFile), true);
        $this->jsonData = $jsonContent['data'] ?? [];
    }

    /**
     * Test querying all seeded data (categories and products) from the GraphQL API.
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

        // Ensure data key exists in the response
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
     */ public function testCreateOrderMutation(): void
    {
        $mutation = [
            'query' => '
            mutation {
                createOrder(
                    productId: 1,
                    quantity: 2
                ) {
                    id
                    product {
                        name
                    }
                    quantity
                    unit_price
                    total
                }
            }
        '
        ];

        $responseData = $this->executeGraphQL($mutation);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('createOrder', $responseData['data']);

        $order = $responseData['data']['createOrder'];
        $this->assertIsArray($order);
        $this->assertArrayHasKey('id', $order);
        $this->assertNotNull($order['id']);
    }

    /**
     * Executes a GraphQL query or mutation via cURL and returns the response as an array.
     *
     * @param array $payload The GraphQL query or mutation payload.
     * @return array The decoded JSON response from the API.
     */
    private function executeGraphQL(array $payload): array
    {
        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(200, $httpCode, "Expected HTTP 200, got $httpCode.");

        $decoded = json_decode($response, true);
        $this->assertIsArray($decoded, 'Failed to decode JSON response.');

        return $decoded;
    }
}
