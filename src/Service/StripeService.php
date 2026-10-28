<?php

namespace App\Service;

use Stripe\StripeClient;
use Stripe\Checkout\Session;

// Service permettant de gérer les paiements Stripe en mode test
class StripeService
{
    private StripeClient $stripe;

    // Initialisation du client Stripe avec la clé secrète
    public function __construct(string $stripeSecretKey)
    {
        $this->stripe = new StripeClient($stripeSecretKey);
    }

    // Création d'une session de paiement Stripe Checkout
    public function createCheckoutSession(
        array $articles,
        string $successUrl,
        string $cancelUrl
    ): Session {
        // Préparation des produits à transmettre à Stripe
        $lineItems = [];

        foreach ($articles as $article) {
            // Le prix est calculé en centimes pour Stripe
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $article['product']->getName()
                            . ' - Taille ' . $article['taille'],
                    ],
                    'unit_amount' => (int) round(
                        (float) $article['product']->getPrice() * 100
                    ),
                ],
                'quantity' => $article['quantite'],
            ];
        }

        // Création de la session de paiement en mode Checkout
        return $this->stripe->checkout->sessions->create([
            
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }

        // Vérification du statut d'une session de paiement Stripe
    public function verifierPaiement(string $sessionId): bool
    {
        // Récupération de la session directement auprès de Stripe
        $session = $this->stripe->checkout->sessions->retrieve($sessionId);

        // Le paiement est confirmé uniquement si Stripe indique "paid"
        return $session->payment_status === 'paid';
    }
}