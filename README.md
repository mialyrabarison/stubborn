
# STUBBORN – Site e-commerce de sweat-shirts

## 1. Présentation du projet

STUBBORN est un site e-commerce développé dans le cadre de la formation CEF.

L'application permet de consulter un catalogue de sweat-shirts, de filtrer les produits par prix, de consulter leurs détails et de les ajouter au panier.

Elle propose également la création de comptes utilisateurs, la connexion, la gestion des produits par un administrateur et le paiement en mode test avec Stripe.

## 2. Technologies utilisées

- PHP 8.2
- Symfony 7.4
- Twig
- HTML et CSS
- MariaDB (XAMPP)
- Doctrine ORM
- Stripe (mode test)
- PHPUnit


## 3. Installation du projet

### Prérequis

Pour utiliser STUBBORN, il faut disposer de :

- PHP 8.2
- Composer
- Symfony CLI
- XAMPP (MariaDB)
- Git

### Installation des dépendances

Après avoir récupéré le projet depuis GitHub, ouvrir un terminal dans le dossier `stubborn`, puis exécuter :

```bash
composer install
```

### Base de données

1. Démarrer MySQL depuis le panneau de contrôle XAMPP.
2. Configurer la variable `DATABASE_URL` dans le fichier `.env.local` avec les identifiants de la base de données locale.
3. Créer la base de données et appliquer les migrations :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Démarrage de l'application

Sous Windows, exécuter :

```powershell
.\start.bat
```

Ce fichier lance d'abord les tests PHPUnit, puis démarre le serveur Symfony si les tests réussissent.

L'application est ensuite accessible à l'adresse :

http://127.0.0.1:8000


## 4. Tests automatisés

Le projet STUBBORN utilise PHPUnit pour vérifier certaines fonctionnalités de l'application.

Les tests sont organisés dans les fichiers suivants :

- `tests/ProductTest.php` : vérification du calcul du prix des produits.
- `tests/CartTest.php` : vérification de l'ajout d'un produit au panier.
- `tests/CheckoutTest.php` : vérification du processus de commande et de l'appel à Stripe en mode simulé.

### Exécution des tests

Pour lancer les tests manuellement :

```bash
php bin/phpunit
```

Résultat obtenu lors de la vérification :

```text
OK (4 tests, 19 assertions)
```

### Exécution au démarrage

Sous Windows, le fichier `start.bat` permet de lancer les tests avant le démarrage du serveur Symfony.

Si un test échoue, le script interrompt le démarrage.

Les résultats des tests s'affichent directement dans le terminal.


## 5. Paiement avec Stripe

STUBBORN utilise Stripe en mode test pour simuler les paiements sans effectuer de transaction bancaire réelle.

### Configuration

Pour utiliser le paiement Stripe :

1. Disposer d'un compte Stripe avec le mode test activé.
2. Configurer les clés Stripe dans les variables d'environnement locales du projet.
3. Ne jamais publier les clés secrètes dans le dépôt GitHub.

### Tester un paiement

1. Démarrer l'application.
2. Se connecter avec un compte utilisateur.
3. Ajouter un sweat-shirt au panier.
4. Accéder au panier et lancer le paiement.
5. Utiliser les informations d'une carte de test Stripe pour simuler le paiement.

Les tests PHPUnit du processus de commande utilisent une simulation de Stripe et ne déclenchent aucun paiement réel.
