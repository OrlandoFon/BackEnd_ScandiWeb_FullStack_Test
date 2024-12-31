<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use App\Traits\CategoryTrait;

#[ORM\Entity]
class Tech extends Product
{
    use CategoryTrait;

    private const VALID_ATTRIBUTES = ['Capacity', 'USB', 'Color'];

    public function addAttribute(string $name, array $items): bool
    {
        if (!$this->validateAttribute($name)) {
            return false;
        }

        $this->attributes[] = [
            'name' => $name,
            'items' => $items
        ];
        return true;
    }

    public function addPrice(float $amount, string $label, string $symbol): void
    {
        $this->prices[] = [
            'amount' => $amount,
            'currency' => [
                'label' => $label,
                'symbol' => $symbol
            ]
        ];
    }

    protected function validateAttribute(string $name): bool
    {
        return in_array($name, self::VALID_ATTRIBUTES);
    }
}
