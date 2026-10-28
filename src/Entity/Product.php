<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Prix avec deux chiffres après la virgule
    #[ORM\Column(type: Types::DECIMAL, precision: 7, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    // Stock disponible pour chaque taille
    #[ORM\Column]
    private ?int $stockXS = null;

    #[ORM\Column]
    private ?int $stockS = null;

    #[ORM\Column]
    private ?int $stockM = null;

    #[ORM\Column]
    private ?int $stockL = null;

    #[ORM\Column]
    private ?int $stockXL = null;
    // Indique si le sweatshirt est mis en avant sur la page d'accueil
    #[ORM\Column(options: ['default' => false])]

    private bool $isFeatured = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getStockXS(): ?int
    {
        return $this->stockXS;
    }

    public function setStockXS(int $stockXS): static
    {
        $this->stockXS = $stockXS;

        return $this;
    }

    public function getStockS(): ?int
    {
        return $this->stockS;
    }

    public function setStockS(int $stockS): static
    {
        $this->stockS = $stockS;

        return $this;
    }

    public function getStockM(): ?int
    {
        return $this->stockM;
    }

    public function setStockM(int $stockM): static
    {
        $this->stockM = $stockM;

        return $this;
    }

    public function getStockL(): ?int
    {
        return $this->stockL;
    }

    public function setStockL(int $stockL): static
    {
        $this->stockL = $stockL;

        return $this;
    }

    public function getStockXL(): ?int
    {
        return $this->stockXL;
    }

    public function setStockXL(int $stockXL): static
    {
        $this->stockXL = $stockXL;

        return $this;
    }
    // Vérifie si le sweatshirt est mis en avant sur la page d'accueil
    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    // Permet de modifier la mise en avant du sweatshirt
    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }
    

}
