<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Currency
{
    #[ORM\Column(length: 3)]
    private string $label;

    #[ORM\Column(length: 1)]
    private string $symbol;

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }
}
