<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['tech' => 'Tech', 'clothes' => 'Clothes'])]
abstract class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    #[ORM\Column(length: 255)]
    protected string $name;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category')]
    protected Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    abstract public function validateProduct(Product $product): bool;
}
