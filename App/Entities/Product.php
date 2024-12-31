<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'boolean')]
    private bool $inStock;

    #[ORM\Column(type: 'array')]
    private array $gallery;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(length: 255)]
    private string $brand;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    private Category $category;

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setInStock(bool $inStock): self
    {
        $this->inStock = $inStock;
        return $this;
    }

    public function setGallery(array $gallery): self
    {
        $this->gallery = $gallery;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;
        return $this;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Attribute::class, mappedBy: 'product')]
    private Collection $attributes;

    public function __construct()
    {
        $this->attributes = new ArrayCollection();
    }

    public function addAttribute(Attribute $attribute): self
    {
        if (!$this->attributes->contains($attribute)) {
            $this->attributes->add($attribute);
            $attribute->setProduct($this);
        }
        return $this;
    }




    #[ORM\OneToOne(targetEntity: Price::class, mappedBy: 'product', cascade: ['persist'])]
    private Price $price;

    public function setPrice(Price $price): self
    {
        $this->price = $price;
        if ($price->getProduct() !== $this) {
            $price->setProduct($this);
        }
        return $this;
    }
}
