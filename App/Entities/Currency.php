<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Embeddable class representing a currency.
 * Used to embed currency details in other entities.
 */
#[ORM\Embeddable]
class Currency
{
    /**
     * The label of the currency (e.g., "USD", "EUR").
     */
    #[ORM\Column(length: 3)]
    private string $label;

    /**
     * The symbol of the currency (e.g., "$", "€").
     */
    #[ORM\Column(length: 1)]
    private string $symbol;

    /**
     * Get the label of the currency.
     *
     * @return string The currency label.
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get the symbol of the currency.
     *
     * @return string The currency symbol.
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * Set the label of the currency.
     *
     * @param string $label The currency label (e.g., "USD").
     * @return self
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Set the symbol of the currency.
     *
     * @param string $symbol The currency symbol (e.g., "$").
     * @return self
     */
    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }
}
