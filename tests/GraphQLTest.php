<?php

use PHPUnit\Framework\TestCase;

class GraphQLTest extends TestCase
{
    // Base URL for the GraphQL endpoint
    private string $baseUrl;

    /**
     * Sets up the base URL for the GraphQL endpoint before each test.
     */
    protected function setUp(): void
    {
        // Base URL of the GraphQL endpoint
        $this->baseUrl = 'http://localhost:9000/graphql';
    }

    /**
     * Tests the "hello" query from the GraphQL endpoint.
     */
    public function testHelloQuery(): void
    {
        // Define the query to be sent to the endpoint
        $query = [
            'query' => '{ hello }'
        ];

        // Initialize a cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl); // Set the URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
        curl_setopt($ch, CURLOPT_POST, true); // Use POST method
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json', // Set the content type as JSON
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query)); // Send the GraphQL query as JSON

        // Execute the request and get the response
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get the HTTP status code
        curl_close($ch); // Close the cURL session

        // Assert that the HTTP response code is 200 (OK)
        $this->assertEquals(200, $httpCode, 'Expected HTTP status code 200');

        // Decode the JSON response
        $responseData = json_decode($response, true);

        // Assert that the response contains the "data" key
        $this->assertArrayHasKey('data', $responseData);

        // Assert that the "hello" query returns the expected result
        $this->assertEquals('Hello, World!', $responseData['data']['hello']);
    }

    /**
     * Tests the "echo" query from the GraphQL endpoint with a message argument.
     */
    public function testEchoQuery(): void
    {
        // Define the query with the "echo" field and a message argument
        $query = [
            'query' => '{ echo(message: "Test") }'
        ];

        // Initialize a cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl); // Set the URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
        curl_setopt($ch, CURLOPT_POST, true); // Use POST method
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json', // Set the content type as JSON
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query)); // Send the GraphQL query as JSON

        // Execute the request and get the response
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get the HTTP status code
        curl_close($ch); // Close the cURL session

        // Assert that the HTTP response code is 200 (OK)
        $this->assertEquals(200, $httpCode, 'Expected HTTP status code 200');

        // Decode the JSON response
        $responseData = json_decode($response, true);

        // Assert that the response contains the "data" key
        $this->assertArrayHasKey('data', $responseData);

        // Assert that the "echo" query returns the expected result with the provided message
        $this->assertEquals('You said: Test', $responseData['data']['echo']);
    }
}
