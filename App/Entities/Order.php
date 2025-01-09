<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Abstract class representing a generic Order.
 * This class allows an order to contain multiple products without requiring an additional entity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
abstract class Order
{
    /**
     * Unique identifier for the order.
     *
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * List of products included in the order.
     * Each product is represented as an associative array containing:
     * - product_id: ID of the product.
     * - quantity: Quantity ordered.
     * - unit_price: Price per unit at the time of order.
     * - total: Total price for the quantity ordered.
     * - selected_attributes: Array of selected attributes for the product.
     *
     * @var array
     */
    #[ORM\Column(type: 'json')]
    protected array $orderedProducts = [];

    /**
     * Total cost of the order.
     *
     * @var float
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    protected float $total = 0.0;

    /**
     * Timestamp indicating when the order was created.
     *
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime')]
    protected \DateTime $created_at;

    /**
     * Constructor initializes the creation timestamp.
     */
    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    /**
     * Adds a product to the order.
     *
     * @param Product $product            The product to add.
     * @param int     $quantity           The quantity of the product.
     * @param array   $selectedAttributes Optional array of selected attributes for the product.
     *
     * @return self Returns the current instance for method chaining.
     *
     * @throws \InvalidArgumentException If the product has no price.
     */
    public function addProduct(Product $product, int $quantity, array $selectedAttributes = []): self
    {
        // Retrieve the unit price of the product. Defaults to 0.0 if no price is set.
        $unitPrice = $product->getPrice() ? $product->getPrice()->getAmount() : 0.0;

        // Ensure that the product has a valid price.
        if ($unitPrice <= 0) {
            throw new \InvalidArgumentException("Product ID {$product->getId()} has an invalid price.");
        }

        // Add the product details to the orderedProducts array.
        $this->orderedProducts[] = [
            'product_id'         => $product->getId(),
            'quantity'           => $quantity,
            'unit_price'         => $unitPrice,
            'total'              => $unitPrice * $quantity,
            'selected_attributes' => $selectedAttributes
        ];

        // Update the total cost of the order.
        $this->updateTotal();

        return $this;
    }

    /**
     * Retrieves the list of ordered products.
     *
     * @return array The array of ordered products.
     */
    public function getOrderedProducts(): array
    {
        return $this->orderedProducts;
    }

    /**
     * Gets the unique identifier of the order.
     *
     * @return int The order ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the total cost of the order.
     *
     * @return float The total price.
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * Gets the creation timestamp of the order.
     *
     * @return \DateTime The creation date and time.
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    /**
     * Recalculates and updates the total cost of the order.
     */
    protected function updateTotal(): void
    {
        $this->total = $this->calculateTotal();
    }

    /**
     * Abstract method to calculate the total cost of the order.
     * Must be implemented by subclasses to define specific calculation logic.
     *
     * @return float The calculated total.
     */
    abstract protected function calculateTotal(): float;

    /**
     * Removes a product from the order based on product ID.
     *
     * @param int $productId The ID of the product to remove.
     *
     * @return self Returns the current instance for method chaining.
     *
     * @throws \InvalidArgumentException If the product is not found in the order.
     */
    public function removeProduct(int $productId): self
    {
        foreach ($this->orderedProducts as $index => $product) {
            if ($product['product_id'] === $productId) {
                unset($this->orderedProducts[$index]);
                // Reindex the array to maintain consistent indexing.
                $this->orderedProducts = array_values($this->orderedProducts);
                $this->updateTotal();
                return $this;
            }
        }

        throw new \InvalidArgumentException("Product ID {$productId} not found in the order.");
    }

    /**
     * Updates the quantity of a specific product in the order.
     *
     * @param int $productId The ID of the product to update.
     * @param int $quantity  The new quantity.
     *
     * @return self Returns the current instance for method chaining.
     *
     * @throws \InvalidArgumentException If the product is not found or quantity is invalid.
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
     * Clears all products from the order.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function clearProducts(): self
    {
        $this->orderedProducts = [];
        $this->updateTotal();
        return $this;
    }
}
