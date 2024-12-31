<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class ClothesAttribute extends AbstractAttribute
{
    #[ORM\ManyToOne(targetEntity: ClothesProduct::class, inversedBy: 'attributes')]
    private ClothesProduct $product;

    #[ORM\OneToMany(targetEntity: AbstractAttributeItem::class, mappedBy: 'attribute', cascade: ['persist'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getProduct(): ClothesProduct
    {
        return $this->product;
    }

    public function setProduct(ClothesProduct $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(AbstractAttributeItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setAttribute($this);
        }
        return $this;
    }
}
