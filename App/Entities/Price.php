<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity class representing the price of a product.
 */
#[ORM\Entity]
#[ORM\Table(name: 'prices')]
class Price
{
    /**
     * The unique identifier of the price entity.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * The monetary amount of the price.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $amount;

    /**
     * The product associated with this price.
     */
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'price')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false)]
    private Product $product;

    /**
     * The currency associated with this price.
     * Embedded object representing the currency details.
     */
    #[ORM\Embedded(class: Currency::class)]
    private Currency $currency;

    /**
     * Get the unique identifier of the price.
     *
     * @return int The price ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the monetary amount of the price.
     *
     * @return float The monetary amount.
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the currency associated with the price.
     *
     * @return Currency The currency details.
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Get the product associated with this price.
     *
     * @return Product The associated product.
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * Set the monetary amount of the price.
     *
     * @param float $amount The amount to set.
     * @return self
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Associate a product with this price.
     *
     * @param Product $product The product to associate.
     * @return self
     */
    public function setProduct(Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    /**
     * Set the currency for this price.
     *
     * @param Currency $currency The currency to set.
     * @return self
     */
    public function setCurrency(Currency $currency): self
    {
        $this->currency = $currency;
        return $this;
    }
}
