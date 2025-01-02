<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity class representing a product in the "Clothes" category.
 */
#[ORM\Entity]
class Clothes extends Product
{
    /**
     * Add an attribute to the product, validating it against allowed attributes for the "Clothes" category.
     *
     * @param string $name The name of the attribute.
     * @param array $items The items associated with the attribute.
     * @return bool True if the attribute was successfully added, false otherwise.
     */
    public function addAttribute(string $name, array $items): bool
    {
        // Validate the attribute before adding
        if ($this->validateAttribute($name)) {
            return parent::addAttribute($name, $items); // Call the parent method to add the attribute
        }
        return false; // Return false if validation fails
    }

    /**
     * Validate if an attribute name is allowed for the "Clothes" category.
     *
     * @param string $name The name of the attribute to validate.
     * @return bool True if the attribute is valid, false otherwise.
     */
    protected function validateAttribute(string $name): bool
    {
        // List of allowed attributes for the "Clothes" category
        $allowedAttributes = [
            'Size',
            'Color',
        ];

        // Check if the attribute is in the allowed list
        return in_array($name, $allowedAttributes, true);
    }
}
