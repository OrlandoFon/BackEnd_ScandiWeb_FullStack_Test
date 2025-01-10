<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Abstract class representing an order.
 * This class allows an order to include multiple products, without requiring a separate entity for product details.
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
abstract class Order
{
    /**
     * Unique identifier of the order.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * List of products included in the order.
     * Each product is stored as an associative array with the following keys:
     * - product_id: ID of the product.
     * - quantity: Quantity of the product ordered.
     * - unit_price: Unit price of the product at the time of the order.
     * - total: Total cost for the product based on the quantity.
     * - selected_attributes: Array of attributes selected for the product.
     */
    #[ORM\Column(type: 'json')]
    protected array $orderedProducts = [];

    /**
     * Total cost of the order.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    protected float $total = 0.0;

    /**
     * Date and time when the order was created.
     */
    #[ORM\Column(type: 'datetime')]
    protected \DateTime $created_at;

    /**
     * Constructor to initialize the creation timestamp.
     */
    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    /**
     * Add a product to the order.
     *
     * @param Product $product            Product being added to the order.
     * @param int     $quantity           Quantity of the product.
     * @param array   $selectedAttributes Attributes selected for the product.
     *
     * @return self Returns the current order instance.
     *
     * @throws \InvalidArgumentException If the product has no valid price.
     */
    public function addProduct(Product $product, int $quantity, array $selectedAttributes = []): self
    {
        $unitPrice = $product->getPrice() ? $product->getPrice()->getAmount() : 0.0;

        if ($unitPrice <= 0) {
            throw new \InvalidArgumentException("Product ID {$product->getId()} has an invalid price.");
        }

        $this->orderedProducts[] = [
            'product_id'         => $product->getId(),
            'quantity'           => $quantity,
            'unit_price'         => $unitPrice,
            'total'              => $unitPrice * $quantity,
            'selected_attributes' => $selectedAttributes
        ];

        $this->updateTotal();

        return $this;
    }

    /**
     * Retrieve all products included in the order.
     *
     * @return array Array of products in the order.
     */
    public function getOrderedProducts(): array
    {
        return $this->orderedProducts;
    }

    /**
     * Get the unique identifier of the order.
     *
     * @return int ID of the order.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Retrieve the total cost of the order.
     *
     * @return float Total cost of the order.
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * Get the creation timestamp of the order.
     *
     * @return \DateTime Date and time the order was created.
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    /**
     * Update the total cost of the order.
     */
    protected function updateTotal(): void
    {
        $this->total = $this->calculateTotal();
    }

    /**
     * Abstract method to calculate the total cost of the order.
     * Subclasses must implement specific logic for calculating the total.
     *
     * @return float Calculated total cost.
     */
    abstract protected function calculateTotal(): float;

    /**
     * Remove a product from the order.
     *
     * @param int $productId ID of the product to remove.
     *
     * @return self Returns the current order instance.
     *
     * @throws \InvalidArgumentException If the product is not found in the order.
     */
    public function removeProduct(int $productId): self
    {
        foreach ($this->orderedProducts as $index => $product) {
            if ($product['product_id'] === $productId) {
                unset($this->orderedProducts[$index]);
                $this->orderedProducts = array_values($this->orderedProducts);
                $this->updateTotal();
                return $this;
            }
        }

        throw new \InvalidArgumentException("Product ID {$productId} not found in the order.");
    }

    /**
     * Update the quantity of a specific product in the order.
     *
     * @param int $productId ID of the product to update.
     * @param int $quantity  New quantity for the product.
     *
     * @return self Returns the current order instance.
     *
     * @throws \InvalidArgumentException If the product is not found or the quantity is invalid.
     */
    public function updateProductQuantity(int $productId, int $quantity): self
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantity must be positive.");
        }

        foreach ($this->orderedProducts as &$product) {
            if ($product['product_id'] === $productId) {
                $product['quantity'] = $quantity;
                $product['total'] = $product['unit_price'] * $quantity;
                $this->updateTotal();
                return $this;
            }
        }

        throw new \InvalidArgumentException("Product ID {$productId} not found in the order.");
    }

    /**
     * Clear all products from the order.
     *
     * @return self Returns the current order instance.
     */
    public function clearProducts(): self
    {
        $this->orderedProducts = [];
        $this->updateTotal();
        return $this;
    }
}
