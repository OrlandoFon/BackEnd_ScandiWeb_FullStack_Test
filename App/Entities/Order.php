<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Abstract class representing a generic order.
 * This class provides a blueprint for all order types, enforcing the implementation of custom logic for calculating the total.
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
abstract class Order
{
    /**
     * @var int Unique identifier for the order.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * @var Product The product associated with this order.
     */
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false)]
    protected Product $product;

    /**
     * @var int The quantity of the product ordered.
     */
    #[ORM\Column(type: 'integer')]
    protected int $quantity;

    /**
     * @var float The unit price of the product at the time of the order.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    protected float $unit_price;

    /**
     * @var float The total cost of the order.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    protected float $total;

    /**
     * @var array The selected attributes of the product ordered.
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private array $selectedAttributes = [];

    /**
     * @var \DateTime The creation timestamp of the order.
     */
    #[ORM\Column(type: 'datetime')]
    protected \DateTime $created_at;

    /**
     * Constructor for the Order class.
     *
     * @param Product $product The product being ordered.
     * @param int $quantity The quantity of the product ordered.
     */
    public function __construct(Product $product, int $quantity, array $selectedAttributes = [])
    {
        $this->product = $product;
        $this->quantity = $quantity;
        $this->unit_price = $product->getPrice()->getAmount();
        $this->selectedAttributes = $selectedAttributes;
        $this->total = $this->calculateTotal();
        $this->created_at = new \DateTime();
    }

    /**
     * Abstract method to calculate the total cost of the order.
     * Subclasses must implement specific logic for total calculation.
     *
     * @return float The calculated total.
     */
    abstract protected function calculateTotal(): float;

    /**
     * Gets the order ID.
     *
     * @return int The order ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the product associated with the order.
     *
     * @return Product The product instance.
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * Gets the quantity of the product in the order.
     *
     * @return int The quantity ordered.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Gets the unit price of the product in the order.
     *
     * @return float The unit price.
     */
    public function getUnitPrice(): float
    {
        return $this->unit_price;
    }

    /**
     * Gets the total cost of the order.
     *
     * @return float The total cost.
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * Sets the selected attributes of the order
     *
     * @param array $attributes Array of selected attributes
     * @return self Returns the current instance for method chaining
     */
    public function setSelectedAttributes(array $attributes): self
    {
        $this->selectedAttributes = $attributes;
        return $this;
    }

    /**
     * Gets the selected attributes of the order
     *
     * @return array Array of selected attributes
     */
    public function getSelectedAttributes(): array
    {
        return $this->selectedAttributes;
    }

    /**
     * Gets the creation timestamp of the order.
     *
     * @return \DateTime The creation timestamp.
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }
}
