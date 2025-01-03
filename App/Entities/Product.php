<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Abstract class representing a product.
 * This class is part of a single table inheritance structure, with subtypes like "Tech" and "Clothes".
 */
#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['standard' => 'StandardProduct'])]
abstract class Product
{
    /**
     * The unique identifier of the product.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    /**
     * The name of the product.
     */
    #[ORM\Column(length: 255)]
    protected string $name;

    /**
     * Whether the product is in stock.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool $inStock = false;

    /**
     * The gallery of images associated with the product.
     */
    #[ORM\Column(type: 'array')]
    protected array $gallery = [];

    /**
     * The description of the product.
     */
    #[ORM\Column(type: 'text')]
    protected string $description = '';

    /**
     * The brand of the product.
     */
    #[ORM\Column(length: 255)]
    protected string $brand = '';

    /**
     * The category associated with the product.
     */
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false)]
    private Category $category;

    /**
     * The attributes associated with the product.
     */
    #[ORM\OneToMany(targetEntity: Attribute::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
    private Collection $attributes;

    /**
     * The price associated with the product.
     */
    #[ORM\OneToOne(targetEntity: Price::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
    private ?Price $price = null;


    /**
     * Product constructor.
     * Initializes the attributes collection.
     */
    public function __construct()
    {
        $this->attributes = new ArrayCollection();
    }

    // === Relationships ===

    /**
     * Get the name of the associated category.
     *
     * @return string The category name.
     */
    public function getCategoryName(): string
    {
        return $this->category->getName();
    }

    /**
     * Get the associated category.
     *
     * @return Category The associated category.
     */
    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * Set the associated category.
     *
     * @param Category|null $category The category to associate.
     * @return self
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category ?? throw new \InvalidArgumentException('Category cannot be null');
        return $this;
    }

    /**
     * Get the collection of attributes associated with the product.
     *
     * @return Collection The attributes collection.
     */
    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    /**
     * Add an attribute to the product.
     *
     * @param string $name The name of the attribute.
     * @param array $items The items for the attribute.
     * @return bool True if the attribute was added successfully.
     */
    public function addAttribute(string $name, array $items): bool
    {
        $attribute = new Attribute();
        $attribute->setName($name)
            ->setItems($items)
            ->setProduct($this);
        $this->attributes->add($attribute);
        return true;
    }

    /**
     * Get the associated price.
     *
     * @return Price|null The associated price, or null if none.
     */
    public function getPrice(): ?Price
    {
        return $this->price;
    }

    /**
     * Set the price for the product.
     *
     * @param float $amount The price amount.
     * @param string $label The currency label (e.g., "USD").
     * @param string $symbol The currency symbol (e.g., "$").
     * @return self
     */
    public function setPrice(float $amount, string $label, string $symbol): self
    {
        $price = new Price();
        $price->setAmount($amount)
            ->setCurrency((new Currency())->setLabel($label)->setSymbol($symbol))
            ->setProduct($this);
        $this->price = $price;
        return $this;
    }

    // === Basic Getters and Setters ===

    /**
     * Get the unique identifier of the product.
     *
     * @return int The product ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the name of the product.
     *
     * @return string The product name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the product.
     *
     * @param string $name The product name.
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Check if the product is in stock.
     *
     * @return bool True if the product is in stock, false otherwise.
     */
    public function isInStock(): bool
    {
        return $this->inStock;
    }

    /**
     * Set the stock status of the product.
     *
     * @param bool $inStock The stock status.
     * @return self
     */
    public function setInStock(bool $inStock): self
    {
        $this->inStock = $inStock;
        return $this;
    }

    /**
     * Get the gallery of images associated with the product.
     *
     * @return array The gallery images.
     */
    public function getGallery(): array
    {
        return $this->gallery;
    }

    /**
     * Set the gallery of images for the product.
     *
     * @param array $gallery The gallery images.
     * @return self
     */
    public function setGallery(array $gallery): self
    {
        $this->gallery = $gallery;
        return $this;
    }

    /**
     * Get the description of the product.
     *
     * @return string The product description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the description of the product.
     *
     * @param string $description The product description.
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the brand of the product.
     *
     * @return string The product brand.
     */
    public function getBrand(): string
    {
        return $this->brand;
    }

    /**
     * Set the brand of the product.
     *
     * @param string $brand The product brand.
     * @return self
     */
    public function setBrand(string $brand): self
    {
        $this->brand = $brand;
        return $this;
    }
}
