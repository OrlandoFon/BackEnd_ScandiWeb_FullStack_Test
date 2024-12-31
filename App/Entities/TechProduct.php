<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class TechProduct extends AbstractProduct
{
    #[ORM\OneToMany(targetEntity: TechAttribute::class, mappedBy: 'product')]
    private Collection $attributes;

    public function __construct()
    {
        parent::__construct();
        $this->attributes = new ArrayCollection();
    }

    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function addAttribute(TechAttribute $attribute): self
    {
        if (!$this->attributes->contains($attribute)) {
            $this->attributes->add($attribute);
            $attribute->setProduct($this);
        }
        return $this;
    }
}
