<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['tech' => 'Tech', 'clothes' => 'Clothes'])]
abstract class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    #[ORM\Column(length: 255)]
    protected string $name;

    #[ORM\Column(type: 'boolean')]
    protected bool $inStock;

    #[ORM\Column(type: 'array')]
    protected array $gallery;

    #[ORM\Column(type: 'text')]
    protected string $description;

    #[ORM\Column(length: 255)]
    protected string $brand;

    #[ORM\Column(type: 'json')]
    protected array $attributes = [];

    #[ORM\Column(type: 'json')]
    protected array $prices = [];

    abstract public function addAttribute(string $name, array $items): bool;
    abstract public function addPrice(float $amount, string $label, string $symbol): void;
    abstract protected function validateAttribute(string $name): bool;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function isInStock(): bool
    {
        return $this->inStock;
    }

    public function setInStock(bool $inStock): self
    {
        $this->inStock = $inStock;
        return $this;
    }

    public function getGallery(): array
    {
        return $this->gallery;
    }

    public function setGallery(array $gallery): self
    {
        $this->gallery = $gallery;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getPrices(): array
    {
        return $this->prices;
    }
}
