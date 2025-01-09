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
     * @var EntityManager The Doctrine EntityManager used for database interactions.
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
     * Creates and persists an order instance.
     *
     * @param int $productId The ID of the product being ordered.
     * @param int $quantity The quantity of the product ordered.
     * @param array $selectedAttributes An array of selected attributes for the product.
     * @return Order The created and persisted order instance.
     * @throws \InvalidArgumentException If the product does not exist, quantity is invalid, or the product lacks a price.
     * @throws \Throwable If any other error occurs during order creation.
     */
    public function createOrder(int $productId, int $quantity, array $selectedAttributes = []): Order
    {
        try {
            $this->entityManager->beginTransaction();

            $product = $this->entityManager->find(Product::class, $productId);
            if (!$product) {
                throw new \InvalidArgumentException("Product not found: {$productId}");
            }

            if ($quantity <= 0) {
                throw new \InvalidArgumentException("Quantity must be positive");
            }

            if (!$product->getPrice()) {
                throw new \InvalidArgumentException("Product has no price");
            }

            // Validate selected attributes against product attributes
            $productAttributes = $product->getAttributes();
            foreach ($selectedAttributes as $attr) {
                $found = false;
                foreach ($productAttributes as $productAttr) {
                    if ($productAttr->getName() === $attr['name']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    throw new \InvalidArgumentException("Invalid attribute: {$attr['name']}");
                }
            }

            $order = new StandardOrder($product, $quantity, $selectedAttributes);
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
