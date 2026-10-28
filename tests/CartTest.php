<?php

namespace App\Tests;

use App\Controller\CartController;
use App\Entity\Product;
use App\Repository\ProductRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CartTest extends TestCase
{
    // Vérifier l'ajout d'un deuxième sweatshirt identique au panier
    public function testAjoutDeuxSweatshirtsMemeTaille(): void
    {
        // Création d'un sweatshirt fictif avec un stock suffisant
        $product = new Product();
        $product->setStockM(5);

        // Simulation de la recherche du produit dans la base de données
        $repository = $this->createMock(ProductRepository::class);
        $repository->method('find')->with(1)->willReturn($product);

        // Création d'une session de test avec un sweatshirt déjà présent
        $session = new Session(new MockArraySessionStorage());
        $session->set('panier', [
            '1_M' => [
                'product_id' => 1,
                'taille' => 'M',
                'quantite' => 1,
            ],
        ]);

        // Simulation de l'envoi du formulaire d'ajout au panier
        $request = Request::create('/cart/add/1', 'POST', [
            '_token' => 'token-test',
            'taille' => 'M',
        ]);
        $request->setSession($session);

        // Simulation de la validation du jeton CSRF
        $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        // Simulation de la génération de l'URL du panier
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/cart');

        // Préparation du contrôleur Symfony
        $controller = new CartController();
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->set('security.csrf.token_manager', $csrfManager);
        $container->set('router', $urlGenerator);
        $controller->setContainer($container);

        // Exécution de la véritable méthode d'ajout au panier
        $response = $controller->add(1, $request, $repository);

        // Vérification de la quantité et de la redirection
        $this->assertSame(2, $session->get('panier')['1_M']['quantite']);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/cart', $response->getTargetUrl());
    }
}
