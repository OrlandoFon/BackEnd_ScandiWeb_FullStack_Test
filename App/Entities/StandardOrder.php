<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class representing a standard order.
 * This is a concrete implementation of the abstract Order class, used for basic orders without additional customization.
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class StandardOrder extends Order
{
    /**
     * Constructor for StandardOrder.
     *
     * @param Product $product The product being ordered.
     * @param int $quantity The quantity of the product ordered.
     * @param array $selectedAttributes The selected attributes of the product ordered.
     */
    public function __construct(Product $product, int $quantity, array $selectedAttributes = [])
    {
        parent::__construct($product, $quantity, $selectedAttributes);
    }

    /**
     * Calculates the total cost of the order.
     * For standard orders, the total is simply the unit price multiplied by the quantity.
     *
     * @return float The calculated total.
     */
    protected function calculateTotal(): float
    {
        return $this->unit_price * $this->quantity;
    }
}
