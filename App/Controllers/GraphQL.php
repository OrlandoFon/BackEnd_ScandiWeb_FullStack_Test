<?php

namespace App\Controllers;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use RuntimeException;
use Throwable;

class GraphQL
{
    /**
     * Handles GraphQL requests and returns the result.
     * 
     * @return string JSON-encoded GraphQL response
     */
    public static function handle()
    {
        try {
            // Define the "Query" type for the GraphQL schema
            $queryType = new ObjectType([
                'name' => 'Query', // Name of the type
                'fields' => [      // Define the available fields
                    'echo' => [
                        'type' => Type::string(), // The return type is a string
                        'args' => [
                            'message' => ['type' => Type::string()], // The "message" argument is a string
                        ],
                        'resolve' => static fn($rootValue, array $args): string => $rootValue['prefix'] . $args['message'],
                    ],
                    'hello' => [
                        'type' => Type::string(), // A simple query returning a string
                        'resolve' => static fn() => 'Hello, World!', // Returns "Hello, World!"
                    ],
                ],
            ]);

            // Define the "Mutation" type for the GraphQL schema
            $mutationType = new ObjectType([
                'name' => 'Mutation', // Name of the type
                'fields' => [         // Define the available fields
                    'sum' => [
                        'type' => Type::int(), // The return type is an integer
                        'args' => [
                            'x' => ['type' => Type::int()], // First integer argument
                            'y' => ['type' => Type::int()], // Second integer argument
                        ],
                        'resolve' => static fn($rootValue, array $args): int => $args['x'] + $args['y'],
                    ],
                ],
            ]);

            // Create the GraphQL schema with the query and mutation types
            $schema = new Schema(
                (new SchemaConfig())
                    ->setQuery($queryType instanceof ObjectType ? $queryType : null)
                    ->setMutation($mutationType instanceof ObjectType ? $mutationType : null)
            );

            // Retrieve the raw GraphQL query input
            $rawInput = file_get_contents('php://input');
            if ($rawInput === false) {
                throw new RuntimeException('Failed to get php://input');
            }

            // Decode the input JSON to retrieve the query and variables
            $input = json_decode($rawInput, true);
            $query = $input['query'];
            $variableValues = $input['variables'] ?? null;

            // Define a root value (useful for passing default values to resolvers)
            $rootValue = ['prefix' => 'You said: '];

            // Execute the GraphQL query
            $result = GraphQLBase::executeQuery($schema, $query, $rootValue, null, $variableValues);

            // Convert the result to an array and output it as JSON
            $output = $result->toArray();
        } catch (Throwable $e) {
            // Handle any errors that occur during execution
            $output = [
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ];
        }

        // Set the response header to indicate JSON content
        header('Content-Type: application/json; charset=UTF-8');
        return json_encode($output);
    }
}
