<?php

namespace App\Factories;

use App\Entities\Order;
use App\Entities\StandardOrder;
use App\Entities\Product;
use Doctrine\ORM\EntityManager;

/**
 * Factory class responsible for creating and persisting Order entities.
 * Ensures all business rules are enforced during the creation of orders.
 */
class OrderFactory
{
    /**
     * Doctrine EntityManager used for database interactions.
     *
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * Initializes the factory with a Doctrine EntityManager instance.
     *
     * @param EntityManager $entityManager The EntityManager instance for database operations.
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Creates and persists an Order with multiple products.
     *
     * @param array $products An array of product details for the order.
     *                        Each product must include:
     *                        - 'productId' (int): The unique identifier of the product.
     *                        - 'quantity' (int): The number of units for the product.
     *                        - 'selectedAttributes' (array, optional): Attributes selected for the product.
     *
     * @return Order The created and persisted Order instance.
     *
     * @throws \InvalidArgumentException If:
     *         - A product is not found.
     *         - The quantity is less than or equal to zero.
     *         - A product has no price.
     * @throws \Throwable For any other errors during the creation process.
     */
    public function createOrder(array $products): Order
    {
        try {
            // Start a database transaction to ensure atomicity.
            $this->entityManager->beginTransaction();

            // Create a new instance of the Order.
            $order = new StandardOrder();

            foreach ($products as $item) {
                // Find the product entity by its ID.
                $product = $this->entityManager->find(Product::class, $item['productId']);

                if (!$product) {
                    throw new \InvalidArgumentException("Product not found: {$item['productId']}");
                }

                if ($item['quantity'] <= 0) {
                    throw new \InvalidArgumentException("Quantity must be positive");
                }

                if (!$product->getPrice()) {
                    throw new \InvalidArgumentException("Product has no price");
                }

                // Add the product to the order.
                $order->addProduct($product, $item['quantity'], $item['selectedAttributes'] ?? []);
            }

            // Persist the order in the database.
            $this->entityManager->persist($order);

            // Finalize the transaction by saving changes.
            $this->entityManager->flush();

            $this->entityManager->commit();

            return $order;
        } catch (\Throwable $e) {
            // Rollback the transaction in case of an error.
            $this->entityManager->rollback();

            throw $e;
        }
    }
}
