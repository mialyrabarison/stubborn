<?php

namespace App\Controller; //Indique que la classe appartient à l'espace de noms des contrôleurs.

use App\Repository\ProductRepository; //Permet de récupérer les sweatshirts enregistrés dans la base de données.
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // AbstractController :Fournit des méthodes utiles aux contrôleurs Symfony, notamment render()
use Symfony\Component\HttpFoundation\Response; //Représente la réponse HTTP envoyée au navigateur
use Symfony\Component\Routing\Attribute\Route; //Permet d'associer une URL à une méthode du contrôleur.

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')] //Avant on a #[Route('/home', name: 'app_home')]
    // mais je veux mettre dans #[Route('/', name: 'app_home')]
    public function index(
        ProductRepository $productRepository
    ): Response //INDEX:Méthode exécutée lorsque la route correspondante est appelée.
    {
        // Récupérer uniquement les sweatshirts mis en avant par l'administrateur
        $featuredProducts = $productRepository->findBy([
            'isFeatured' => true,
        ]);

        return $this->render('home/index.html.twig', [ //render Méthode exécutée lorsque la route correspondante est appelée.
            'controller_name' => 'HomeController', //Classe PHP qui gère notre page.
            'featuredProducts' => $featuredProducts, //Transmettre les sweatshirts mis en avant à la page d'accueil.
        ]);
    }
}
