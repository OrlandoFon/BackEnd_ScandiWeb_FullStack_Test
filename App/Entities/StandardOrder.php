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
     * Initializes the StandardOrder by calling the parent constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Calculates the total cost of the order.
     *
     * This method overrides the abstract calculateTotal method from the parent Order class.
     * It uses array_reduce to iterate over the orderedProducts array and sum up the 'total'
     * value of each product to compute the overall total cost of the order.
     *
     * @return float The calculated total cost of the order.
     */
    protected function calculateTotal(): float
    {
        return array_reduce(
            $this->orderedProducts, // The array of ordered products.
            fn (float $sum, array $item) => $sum + $item['total'], // Callback to accumulate the total.
            0.0 // Initial value of the sum.
        );
    }
}
