# Minimalist Store — Symfony 8

Plateforme e-commerce construite avec Symfony 8. Architecture orientée services, panier en session, classement produits par score de popularité.

---

## Stack

| Couche | Technologie |
|---|---|
| Framework | Symfony 8.0 (PHP 8.2+) |
| Base de données | MySQL 8 |
| ORM | Doctrine |
| Templates | Twig + Tailwind CSS (CDN) |
| Infra | Docker (Nginx + PHP-FPM + MySQL) |

---

## Fonctionnalités

- **Catalogue** — Filtrage par catégorie, tri dynamique (popularité, prix, achats, vues)
- **Popularité** — Score pondéré calculé automatiquement à partir des vues, ajouts au panier et achats (`ProductStats`)
- **Boost éditorial** — Flag `isTop` gérable depuis le back-office admin
- **Panier** — Géré en session via `CartHandler` (pattern Strategy, SOLID)
- **Commandes** — Snapshot du prix et du nom produit au moment de l'achat (historisation immuable)
- **Auth** — Inscription, connexion (`form_login`), hachage Argon2id
- **Profil** — Dashboard utilisateur, historique des commandes paginé

→ Détail des logiques de ranking : [`docs/FEATURES.md`](docs/FEATURES.md)

---

## Installation

### Avec Docker (recommandé)

```bash
# 1. Lancement de l'infrastructure et instanciation des conteneurs
docker compose up -d

# 2. Installation des dépendances
docker compose exec php composer install

# 3. Configuration de l'environnement
# Ajuster DATABASE_URL dans .env.local
cp .env .env.local

# 4. Base de données
[Optional]
docker compose exec php php bin/console doctrine:database:drop --force --if-exists
docker compose exec php php bin/console doctrine:database:create
[Required]
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction

# 5. Lancement du serveur Symfony
symfony server:start


# 6.Optional (if needed)
# Donner la propriété des dossiers var à l'utilisateur web (www-data)
docker compose exec php chown -R www-data:www-data var

# Donner les droits d'écriture/exécution nécessaires
docker compose exec php chmod -R 775 var

# Vider le cache pour repartir sur une base saine
docker compose exec php php bin/console cache:clear
```

L'application est accessible sur `http://localhost`.

### Sans Docker

```bash
# 1. Installation des dépendances
composer install

# 2. Configuration de l'environnement
# Ajuster DATABASE_URL dans .env.local
cp .env .env.local

# 3. Base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# 4. Lancement du serveur Symfony
symfony server:start
```

---

## Comptes de test (fixtures)

| Email | Mot de passe | Rôle |
|---|---|---|
| `admin@ecommerce.com` | `Admin123!` | ROLE_ADMIN |
| `client@test.com` | `Client123!` | ROLE_USER |
