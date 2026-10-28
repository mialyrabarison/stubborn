<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérifier si le formulaire d'ajout a été envoyé
        if ($request->isMethod('POST')) {

            // Vérifier le jeton de sécurité du formulaire
            if (!$this->isCsrfTokenValid(
                'admin_add_product',
                (string) $request->request->get('_token', '')
            )) {
                throw $this->createAccessDeniedException(
                    'Formulaire invalide.'
                );
            }

            // Récupérer le nom du nouveau sweatshirt
            $name = trim((string) $request->request->get('name', ''));

            // Récupérer le prix du nouveau sweatshirt
            $price = (string) $request->request->get('price', '');

            // Récupérer les stocks disponibles pour chaque taille
            $stockXS = $request->request->get('stockXS');
            $stockS = $request->request->get('stockS');
            $stockM = $request->request->get('stockM');
            $stockL = $request->request->get('stockL');
            $stockXL = $request->request->get('stockXL');

            // Récupérer le fichier image envoyé depuis le formulaire
            $imageFile = $request->files->get('image');

            // Vérifier que le nom et le prix sont valides
            $priceValid = preg_match(
                '/^\d{1,5}(?:\.\d{1,2})?$/',
                $price
            ) === 1 && (float) $price > 0;

            // Vérifier que les cinq stocks sont des entiers positifs ou nuls
            $stocks = [
                $stockXS,
                $stockS,
                $stockM,
                $stockL,
                $stockXL,
            ];

            $stocksValid = true;

            foreach ($stocks as $stock) {
                if (
                    filter_var(
                        $stock,
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 0]]
                    ) === false
                ) {
                    $stocksValid = false;
                    break;
                }
            }

            // Vérifier que l'image est un fichier accepté
            $imageValid = $imageFile instanceof UploadedFile
                && $imageFile->isValid()
                && $imageFile->getSize() <= 5 * 1024 * 1024
                && in_array(
                    $imageFile->getMimeType(),
                    ['image/jpeg', 'image/png', 'image/webp'],
                    true
                );

            if (
                $name === ''
                || mb_strlen($name) > 255
                || !$priceValid
                || !$stocksValid
                || !$imageValid
            ) {
                $this->addFlash(
                    'error',
                    'Veuillez vérifier le nom, le prix, les stocks et l’image (JPG, PNG ou WebP, 5 Mo maximum).'
                );

                return $this->redirectToRoute('app_admin');
            }

            // Préparer un nom unique pour l'image
            $extension = match ($imageFile->getMimeType()) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            };

            $imageName = bin2hex(random_bytes(16)) . '.' . $extension;

            // Enregistrer l'image dans le dossier public/images
            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/images',
                    $imageName
                );
            } catch (\Exception $exception) {
                $this->addFlash(
                    'error',
                    'Impossible d’enregistrer l’image du sweatshirt.'
                );

                return $this->redirectToRoute('app_admin');
            }

            // Créer un nouveau sweatshirt
            $product = new Product();

            $product->setName($name);
            $product->setPrice($price);
            $product->setImage($imageName);

            // Enregistrer les stocks des cinq tailles
            $product->setStockXS((int) $stockXS);
            $product->setStockS((int) $stockS);
            $product->setStockM((int) $stockM);
            $product->setStockL((int) $stockL);
            $product->setStockXL((int) $stockXL);

            // Le nouveau sweatshirt n'est pas mis en avant par défaut
            $product->setIsFeatured(false);

            // Enregistrer le sweatshirt dans la base de données
            $entityManager->persist($product);
            $entityManager->flush();

            // Afficher un message de confirmation
            $this->addFlash(
                'success',
                'Le sweatshirt a été ajouté avec succès.'
            );

            // Revenir au back-office
            return $this->redirectToRoute('app_admin');
        }

        // Récupérer tous les sweatshirts enregistrés dans la base de données
        $products = $productRepository->findAll();

        // Transmettre les produits à la page du back-office
        return $this->render('admin/index.html.twig', [
            'products' => $products,
        ]);
    }

    // MODIFIER UN SWEATSHIRT EXISTANT
    #[Route(
        '/admin/product/{id}/edit',
        name: 'app_admin_product_edit',
        methods: ['POST'],
        requirements: ['id' => '\d+']
    )]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérifier le jeton de sécurité du formulaire de modification
        if (!$this->isCsrfTokenValid(
            'admin_edit_product_' . $product->getId(),
            (string) $request->request->get('_token', '')
        )) {
            throw $this->createAccessDeniedException(
                'Formulaire de modification invalide.'
            );
        }

        // Récupérer le nom et le prix modifiés
        $name = trim((string) $request->request->get('name', ''));
        $price = (string) $request->request->get('price', '');

        // Récupérer les stocks modifiés pour chaque taille
        $stockXS = $request->request->get('stockXS');
        $stockS = $request->request->get('stockS');
        $stockM = $request->request->get('stockM');
        $stockL = $request->request->get('stockL');
        $stockXL = $request->request->get('stockXL');

        // Vérifier que le nom et le prix sont valides
        $priceValid = preg_match(
            '/^\d{1,5}(?:\.\d{1,2})?$/',
            $price
        ) === 1 && (float) $price > 0;

        // Vérifier que tous les stocks sont des entiers positifs ou nuls
        $stocks = [
            $stockXS,
            $stockS,
            $stockM,
            $stockL,
            $stockXL,
        ];

        $stocksValid = true;

        foreach ($stocks as $stock) {
            if (
                filter_var(
                    $stock,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 0]]
                ) === false
            ) {
                $stocksValid = false;
                break;
            }
        }

        // Récupérer une nouvelle image si l'administrateur en choisit une
        $imageFile = $request->files->get('image');

        // L'image est facultative lors d'une modification
        $imageValid = $imageFile === null || (
            $imageFile instanceof UploadedFile
            && $imageFile->isValid()
            && $imageFile->getSize() <= 5 * 1024 * 1024
            && in_array(
                $imageFile->getMimeType(),
                ['image/jpeg', 'image/png', 'image/webp'],
                true
            )
        );

        // Refuser les informations incorrectes
        if (
            $name === ''
            || mb_strlen($name) > 255
            || !$priceValid
            || !$stocksValid
            || !$imageValid
        ) {
            $this->addFlash(
                'error',
                'Modification impossible : vérifiez le nom, le prix, les stocks et l’image.'
            );

            return $this->redirectToRoute('app_admin');
        }

        // Enregistrer une nouvelle image uniquement si elle a été sélectionnée
        if ($imageFile instanceof UploadedFile) {

            // Déterminer l'extension à partir du type du fichier
            $extension = match ($imageFile->getMimeType()) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            };

            // Générer un nom unique pour éviter d'écraser une autre image
            $imageName = bin2hex(random_bytes(16)) . '.' . $extension;

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/images',
                    $imageName
                );
            } catch (\Exception $exception) {
                $this->addFlash(
                    'error',
                    'Impossible d’enregistrer la nouvelle image.'
                );

                return $this->redirectToRoute('app_admin');
            }

            // Remplacer l'image associée au sweatshirt
            $product->setImage($imageName);
        }

        // Mettre à jour le nom et le prix
        $product->setName($name);
        $product->setPrice($price);

        // Mettre à jour les stocks pour les cinq tailles
        $product->setStockXS((int) $stockXS);
        $product->setStockS((int) $stockS);
        $product->setStockM((int) $stockM);
        $product->setStockL((int) $stockL);
        $product->setStockXL((int) $stockXL);

        // Mettre à jour la mise en avant sur la page d'accueil
        $product->setIsFeatured(
            $request->request->get('isFeatured') === '1'
        );

        // Enregistrer les modifications dans la base de données
        $entityManager->flush();

        // Afficher un message de confirmation
        $this->addFlash(
            'success',
            'Le sweatshirt a été modifié avec succès.'
        );

        // Revenir à la liste des sweatshirts
        return $this->redirectToRoute('app_admin');
    }
    

    // SUPPRIMER UN SWEATSHIRT EXISTANT
    #[Route(
        '/admin/product/{id}/delete',
        name: 'app_admin_product_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+']
    )]
    public function delete(
        Product $product,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Vérifier le jeton de sécurité du formulaire de suppression
        if (!$this->isCsrfTokenValid(
            'admin_delete_product_' . $product->getId(),
            (string) $request->request->get('_token', '')
        )) {
            throw $this->createAccessDeniedException(
                'Formulaire de suppression invalide.'
            );
        }

        // Conserver le nom pour le message de confirmation
        $productName = $product->getName();

        // Supprimer le sweatshirt de la base de données
        $entityManager->remove($product);
        $entityManager->flush();

        // Afficher un message de confirmation
        $this->addFlash(
            'success',
            'Le sweatshirt "' . $productName . '" a été supprimé avec succès.'
        );

        // Revenir au Back-Office
        return $this->redirectToRoute('app_admin');
    }

}
