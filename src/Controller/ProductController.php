<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    // Page Boutique : liste de tous les sweatshirts
    #[Route('/products', name: 'app_products')]
    public function index(
        ProductRepository $productRepository,
        Request $request
    ): Response {
        // Récupération des produits depuis MariaDB
        $products = $productRepository->findBy([], ['id' => 'ASC']);

        // Récupération de la fourchette de prix sélectionnée
        $prixSelectionne = $request->query->get('prix', '');

        // Filtrage des sweatshirts selon leur prix
        if (in_array($prixSelectionne, ['10-29', '29-35', '35-50'], true)) {

            [$minimum, $maximum] = array_map(
                'intval',
                explode('-', $prixSelectionne)
            );

            $products = array_filter(
                $products,
                function ($product) use ($minimum, $maximum) {
                    $prixProduit = (float) $product->getPrice();

                    return $prixProduit >= $minimum
                        && $prixProduit <= $maximum;
                }
            );
        }

        // Transmission des produits et du filtre à Twig
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'prixSelectionne' => $prixSelectionne,
        ]);
    }

    // Fiche individuelle d'un sweatshirt selon son identifiant
    #[Route('/product/{id}', name: 'app_product_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        ProductRepository $productRepository
    ): Response {
        // Recherche du sweatshirt dans MariaDB
        $product = $productRepository->find($id);

        // Produit inexistant : erreur 404
        if (!$product) {
            throw $this->createNotFoundException(
                'Ce sweatshirt est introuvable.'
            );
        }

        // Transmission du sweatshirt à sa fiche individuelle
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
