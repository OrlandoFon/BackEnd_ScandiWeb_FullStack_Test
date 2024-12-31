<?php

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;

trait CategoryTrait
{
    #[ORM\Column(length: 255)]
    protected string $categoryName;

    public function setCategoryName(string $name): self
    {
        $this->categoryName = $name;
        return $this;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }
}
