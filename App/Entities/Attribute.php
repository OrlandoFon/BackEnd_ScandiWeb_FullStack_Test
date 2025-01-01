<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity class representing an attribute of a product.
 */
#[ORM\Entity]
#[ORM\Table(name: 'attributes')]
class Attribute
{
    /**
     * The unique identifier of the attribute.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * The name of the attribute (e.g., "Size", "Color").
     */
    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * The items associated with the attribute, stored as JSON.
     */
    #[ORM\Column(type: 'json')]
    private array $items = [];

    /**
     * The product associated with this attribute.
     */

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'attributes')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false)]
    private Product $product;

    /**
     * Get the unique identifier of the attribute.
     *
     * @return int The attribute ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the name of the attribute.
     *
     * @return string The attribute name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the items associated with the attribute.
     *
     * @return array The attribute items.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Set the name of the attribute.
     *
     * @param string $name The attribute name.
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the items associated with the attribute.
     *
     * @param array $items The attribute items.
     * @return self
     */
    public function setItems(array $items): self
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Associate a product with this attribute.
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
     * Get the associated product.
     */
    public function getProduct(): Product
    {
        return $this->product;
    }
}
