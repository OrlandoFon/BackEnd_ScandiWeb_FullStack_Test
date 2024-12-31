<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AbstractAttributeItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $displayValue;

    #[ORM\Column(type: 'string')]
    private string $value;

    #[ORM\ManyToOne(targetEntity: AbstractAttribute::class, inversedBy: 'items')]
    private AbstractAttribute $attribute;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDisplayValue(): string
    {
        return $this->displayValue;
    }

    public function setDisplayValue(string $displayValue): self
    {
        $this->displayValue = $displayValue;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getAttribute(): AbstractAttribute
    {
        return $this->attribute;
    }

    public function setAttribute(AbstractAttribute $attribute): self
    {
        $this->attribute = $attribute;
        return $this;
    }
}
