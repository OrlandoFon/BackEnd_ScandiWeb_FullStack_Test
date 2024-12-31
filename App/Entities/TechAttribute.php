<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class TechAttribute extends AbstractAttribute
{
    #[ORM\ManyToOne(targetEntity: TechProduct::class, inversedBy: 'attributes')]
    private TechProduct $product;

    #[ORM\OneToMany(targetEntity: AbstractAttributeItem::class, mappedBy: 'attribute', cascade: ['persist'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getProduct(): TechProduct
    {
        return $this->product;
    }

    public function setProduct(TechProduct $product): self
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
