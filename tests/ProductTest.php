<?php

namespace App\Tests;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    // Vérifier le prix d'un sweatshirt utilisé pour un achat
    public function testCalculPrixDeuxSweatshirts(): void
    {
        // Création d'un sweatshirt pour le test
        $product = new Product();
        $product->setPrice(29);

        // Simulation d'une quantité de 2 sweatshirts
        $quantite = 2;

        // Calcul du montant total
        $total = (float) $product->getPrice() * $quantite;

        // Vérification : 29 € x 2 = 58 €
        $this->assertEquals(58, $total);
    }
}
