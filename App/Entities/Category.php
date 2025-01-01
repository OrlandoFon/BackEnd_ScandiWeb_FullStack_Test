<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Entity class representing a product category.
 */
#[ORM\Entity]
#[ORM\Table(name: 'categories')]
class Category
{
    /**
     * The unique identifier of the category.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    /**
     * The name of the category.
     */
    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * The collection of products associated with this category.
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category', cascade: ['persist', 'remove'])]
    private Collection $products;

    /**
     * Category constructor.
     * Initializes the products collection.
     */
    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    /**
     * Get the unique identifier of the category.
     *
     * @return int The category ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the name of the category.
     *
     * @return string The category name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the category.
     *
     * @param string $name The category name.
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Add a product to the category.
     *
     * @param Product $product The product to add.
     * @return self
     */
    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategory($this); // Link the product back to this category
        }
        return $this;
    }

    /**
     * Remove a product from the category.
     *
     * @param Product $product The product to remove.
     * @return self
     */
    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            if ($product->getCategory() === $this) {
                $this->products->removeElement($product);
            }
        }
        return $this;
    }

    /**
     * Get all products associated with this category.
     *
     * @return Collection The collection of products.
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }
}
