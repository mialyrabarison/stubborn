<?php
namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    // Page Panier : affichage des articles ajoutés
    #[Route('/cart', name: 'app_cart')]
    public function index(
        Request $request,
        ProductRepository $productRepository
    ): Response {
        // Récupération du panier enregistré dans la session Symfony
        $panier = $request->getSession()->get('panier', []);

        // Préparation des articles à afficher dans le panier
        $articles = [];
        $total = 0;

        // Recherche des informations de chaque sweatshirt dans MariaDB
        foreach ($panier as $cle => $article) {
            $product = $productRepository->find($article['product_id']);

            // Ignorer un produit qui n'existe plus dans la base de données
            if (!$product) {
                continue;
            }

            // Calcul du sous-total selon le prix et la quantité
            $sousTotal = (float) $product->getPrice() * $article['quantite'];

            // Préparation des informations pour la page Twig
            $articles[] = [
                'cle' => $cle,
                'product' => $product,
                'taille' => $article['taille'],
                'quantite' => $article['quantite'],
                'sous_total' => $sousTotal,
            ];

            // Calcul du montant total du panier
            $total += $sousTotal;
        }

        // Transmission des articles et du total à la page Twig
        return $this->render('cart/index.html.twig', [
            'panier' => $panier,
            'articles' => $articles,
            'total' => $total,
        ]);
    }

    // Ajout d'un sweatshirt au panier avec la taille sélectionnée
    #[Route('/cart/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        ProductRepository $productRepository
    ): Response {
        // Vérification du jeton de sécurité du formulaire
        if (!$this->isCsrfTokenValid(
            'cart_add_' . $id,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Formulaire invalide.');
        }

        // Recherche du sweatshirt dans MariaDB
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Ce sweatshirt est introuvable.');
        }

        // Récupération et vérification de la taille choisie
        $taille = $request->request->get('taille');

        if (!in_array($taille, ['XS', 'S', 'M', 'L', 'XL'], true)) {
            throw $this->createNotFoundException('Taille invalide.');
        }

        // Vérification du stock disponible pour la taille choisie
        $stock = match ($taille) {
            'XS' => $product->getStockXS(),
            'S'  => $product->getStockS(),
            'M'  => $product->getStockM(),
            'L'  => $product->getStockL(),
            'XL' => $product->getStockXL(),
        };

        // Récupération du panier existant
        $session = $request->getSession();
        $panier = $session->get('panier', []);

        // Identification d'un article par son produit et sa taille
        $cle = $id . '_' . $taille;

        // Quantité déjà présente dans le panier
        $quantite = $panier[$cle]['quantite'] ?? 0;

        if ($quantite >= $stock) {
            $this->addFlash('warning', 'Stock insuffisant pour cette taille.');

            return $this->redirectToRoute('app_product_show', [
                'id' => $id,
            ]);
        }

        // Ajout d'un article ou augmentation de sa quantité
        $panier[$cle] = [
            'product_id' => $id,
            'taille' => $taille,
            'quantite' => $quantite + 1,
        ];

        // Enregistrement du panier dans la session Symfony
        $session->set('panier', $panier);

        // Redirection vers la page Panier
        return $this->redirectToRoute('app_cart');
    }


    // Suppression d'un article du panier
    #[Route('/cart/remove/{cle}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(string $cle, Request $request): Response
    {
        // Vérification du jeton de sécurité du formulaire
        if (!$this->isCsrfTokenValid(
            'cart_remove_' . $cle,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Formulaire invalide.');
        }

        // Récupération du panier enregistré dans la session
        $session = $request->getSession();
        $panier = $session->get('panier', []);

        // Suppression de l'article correspondant au produit et à sa taille
        unset($panier[$cle]);

        // Enregistrement du panier après suppression
        $session->set('panier', $panier);

        // Retour à la page Panier pour actualiser les articles et le total
        return $this->redirectToRoute('app_cart');
    }


    // Création d'une session de paiement Stripe pour le panier
    #[Route('/cart/checkout', name: 'app_cart_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        ProductRepository $productRepository,
        StripeService $stripeService
    ): Response {
        // Vérification du jeton de sécurité du formulaire de paiement
        if (!$this->isCsrfTokenValid(
            'cart_checkout',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Formulaire invalide.');
        }

        // Récupération du panier enregistré dans la session Symfony
        $panier = $request->getSession()->get('panier', []);

        // Préparation des articles à transmettre au service Stripe
        $articles = [];

        foreach ($panier as $article) {
            // Recherche du sweatshirt dans la base de données
            $product = $productRepository->find($article['product_id']);

            // Ignorer un produit supprimé de la base de données
            if (!$product) {
                continue;
            }

            // Vérification de la taille et de la quantité
            $taille = $article['taille'] ?? null;
            $quantite = $article['quantite'] ?? 0;

            if (
                !in_array($taille, ['XS', 'S', 'M', 'L', 'XL'], true)
                || !is_int($quantite)
                || $quantite < 1
            ) {
                continue;
            }

            // Vérification du stock disponible
            $stock = match ($taille) {
                'XS' => $product->getStockXS(),
                'S'  => $product->getStockS(),
                'M'  => $product->getStockM(),
                'L'  => $product->getStockL(),
                'XL' => $product->getStockXL(),
            };

            if ($quantite > $stock) {
                $this->addFlash('warning', 'Stock insuffisant pour ' . $product->getName());

                return $this->redirectToRoute('app_cart');
            }

            // Préparation des informations pour le paiement
            $articles[] = [
                'product' => $product,
                'taille' => $taille,
                'quantite' => $quantite,
            ];
        }

       
        // Empêcher le paiement si aucun article valide n'est présent
        if (empty($articles)) {
            $this->addFlash('warning', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart');
        }

        // Création de la session Stripe Checkout
        $checkoutSession = $stripeService->createCheckoutSession(
            $articles,

            // URL de retour après le paiement avec l'identifiant de session Stripe
                $this->generateUrl(
                'app_cart_success',
                [],
                \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
            ) . '?session_id={CHECKOUT_SESSION_ID}',

            // URL de retour si le client annule le paiement
            $this->generateUrl(
                'app_cart',
                ['payment' => 'cancel'],
                \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
            )
        );

        // Redirection vers la page sécurisée de paiement Stripe
        return $this->redirect($checkoutSession->url, 303);
    }

    // Vérification du paiement Stripe après le retour du client
#[Route('/cart/success', name: 'app_cart_success', methods: ['GET'])]
public function success(
    Request $request,
    StripeService $stripeService
): Response {
    // Récupération de l'identifiant de session Stripe
    $sessionId = $request->query->get('session_id');

    // Refuser une confirmation sans identifiant de paiement
    if (!is_string($sessionId) || $sessionId === '') {
        throw $this->createNotFoundException('Session de paiement introuvable.');
    }

    // Vérification du paiement directement auprès de Stripe
    if (!$stripeService->verifierPaiement($sessionId)) {
        $this->addFlash('warning', 'Le paiement n’a pas été confirmé.');

        return $this->redirectToRoute('app_cart');
    }

    // Paiement confirmé : suppression des articles du panier
    $request->getSession()->remove('panier');

    // Affichage de la confirmation
    return new Response('Votre paiement a été effectué avec succès !');
}

}
