<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Currency
{
    #[ORM\Column(type: 'string', length: 3)]
    private string $label;

    #[ORM\Column(type: 'string', length: 1)]
    private string $symbol;

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }
}
