<?php

namespace App\Factories;

use App\Entities\Order;
use App\Entities\StandardOrder;
use App\Entities\Product;
use Doctrine\ORM\EntityManager;

/**
 * Factory class for creating order instances.
 * This class handles the creation and persistence of orders, ensuring that all business rules are enforced.
 */
class OrderFactory
{
    /**
     * Doctrine's EntityManager for interacting with the database.
     *
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * Constructor for OrderFactory.
     *
     * @param EntityManager $entityManager The Doctrine EntityManager instance.
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Creates and persists an Order with multiple products.
     *
     * @param array $products An array of products to be included in the order.
     *                        Each product should be an associative array with keys:
     *                        - 'productId' (int): The ID of the product.
     *                        - 'quantity' (int): The quantity of the product.
     *                        - 'selectedAttributes' (array, optional): An array of selected attributes for the product.
     *
     * @return Order The created and persisted Order instance.
     *
     * @throws \InvalidArgumentException If a product is not found, quantity is invalid, or product has no price.
     * @throws \Throwable For any other exceptions that occur during order creation.
     */
    public function createOrder(array $products): Order
    {
        try {
            $this->entityManager->beginTransaction();

            $order = new StandardOrder();

            foreach ($products as $item) {
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

                $order->addProduct($product, $item['quantity'], $item['selectedAttributes'] ?? []);
            }

            $this->entityManager->persist($order);

            $this->entityManager->flush();

            $this->entityManager->commit();

            return $order;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();

            throw $e;
        }
    }
}
