<?php

namespace App\Tests;

use App\Controller\CartController;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\StripeService;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CheckoutTest extends TestCase
{
    // Vérifier qu'un achat est refusé lorsque le panier est vide
    public function testAchatAvecPanierVide(): void
    {
        // Création d'une session de test avec un panier vide
        $session = new Session(new MockArraySessionStorage());
        $session->set('panier', []);

        // Simulation de la demande de paiement
        $request = Request::create('/cart/checkout', 'POST', [
            '_token' => 'token-test',
        ]);
        $request->setSession($session);

        // Simulation de la validation du jeton CSRF
        $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        // Simulation de la génération de l'URL du panier
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/cart');

        // Simulation des dépendances pour éviter la base de données et Stripe
        $repository = $this->createMock(ProductRepository::class);
        $stripeService = $this->createMock(StripeService::class);

        // Stripe ne doit jamais être appelé si le panier est vide
        $stripeService->expects($this->never())
            ->method('createCheckoutSession');

        // Préparation du contrôleur Symfony
        $controller = new CartController();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = new ContainerBuilder();
        $container->set('security.csrf.token_manager', $csrfManager);
        $container->set('router', $urlGenerator);
        $container->set('request_stack', $requestStack);

        $controller->setContainer($container);

        // Exécution de la véritable méthode checkout()
        $response = $controller->checkout(
            $request,
            $repository,
            $stripeService
        );

        // Vérification : le client est renvoyé vers le panier
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/cart', $response->getTargetUrl());

        // Vérification : le panier reste vide
        $this->assertSame([], $session->get('panier'));
    }

        // Vérifier la préparation d'un achat avec un sweatshirt valide
        public function testAchatAvecProduitValide(): void
    {
        // Création d'un sweatshirt fictif pour le test
        $product = new Product();
        $product->setName('Sweatshirt TEST');
        $product->setPrice(29);
        $product->setStockM(5);

        // Création d'une session contenant deux sweatshirts de taille M
        $session = new Session(new MockArraySessionStorage());
        $session->set('panier', [
            '1_M' => [
                'product_id' => 1,
                'taille' => 'M',
                'quantite' => 2,
            ],
        ]);

        // Simulation de la demande de paiement
        $request = Request::create('/cart/checkout', 'POST', [
            '_token' => 'token-test',
        ]);
        $request->setSession($session);

        // Simulation de la validation du jeton CSRF
        $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        // Simulation de la recherche du sweatshirt dans la base de données
        $repository = $this->createMock(ProductRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        // Simulation des URL générées par Symfony
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $urlGenerator->method('generate')
            ->willReturnCallback(
                function (string $route, array $parameters = []): string {
                    if ($route === 'app_cart_success') {
                        return 'http://localhost:8000/cart/success';
                    }

                    if ($route === 'app_cart') {
                        return 'http://localhost:8000/cart';
                    }

                    return '/';
                }
            );

        // Simulation du service Stripe sans véritable paiement
        $stripeService = $this->createMock(StripeService::class);

        // Vérifier que Stripe reçoit les bons articles
        $stripeService->expects($this->once())
            ->method('createCheckoutSession')
            ->with(
                $this->callback(
                    function (array $articles) use ($product): bool {
                        return count($articles) === 1
                            && $articles[0]['product'] === $product
                            && $articles[0]['taille'] === 'M'
                            && $articles[0]['quantite'] === 2;
                    }
                ),
                $this->stringContains('/cart/success'),
                $this->stringContains('/cart')
            )
            ->willReturn(
                StripeCheckoutSession::constructFrom([
                    'id' => 'cs_test_simulation',
                    'url' => 'https://checkout.stripe.com/test-simulation',
                ])
            );

        // Préparation du contrôleur Symfony
        $controller = new CartController();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = new ContainerBuilder();
        $container->set('security.csrf.token_manager', $csrfManager);
        $container->set('router', $urlGenerator);
        $container->set('request_stack', $requestStack);

        $controller->setContainer($container);

        // Exécution de la véritable méthode checkout()
        $response = $controller->checkout(
            $request,
            $repository,
            $stripeService
        );

        // Vérification de la redirection vers Stripe
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame(
            'https://checkout.stripe.com/test-simulation',
            $response->getTargetUrl()
        );

        // Le panier reste présent avant la confirmation du paiement
        $this->assertSame(2, $session->get('panier')['1_M']['quantite']);
    }
}
