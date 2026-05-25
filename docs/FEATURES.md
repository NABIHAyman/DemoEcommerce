# Dual Product Ranking — `isTop` & Popularity Score

Ce projet implémente deux logiques de mise en avant des produits, **indépendantes l'une de l'autre**.

---

## 1. Le flag `isTop` — Boost manuel

### Principe

`Product::$isTop` est un booléen géré par l'administrateur depuis le back-office.
Il représente un **boost éditorial** : l'admin décide manuellement qu'un produit mérite d'être mis en avant (produit sponsorisé, coup de cœur, promotion…).

Ce flag n'a aucun rapport avec les performances réelles du produit.

### Où ça se passe

| Fichier | Rôle |
|---|---|
| `src/Entity/Product.php` | Propriété `$isTop` (bool, défaut `false`) |
| `src/Form/ProductType.php` | Champ checkbox dans le formulaire admin |
| `src/Controller/Admin/ProductController.php` | CRUD admin qui persiste le flag |
| `templates/product/index.html.twig` | Affichage conditionnel du badge « Top » |
---

## 2. Le score de popularité — Calcul automatique

### Principe

`ProductStats` (relation OneToOne avec `Product`) accumule trois compteurs en base de données, mis à jour automatiquement à chaque interaction utilisateur.

Le score est calculé avec des **poids différenciés** selon l'engagement :

| Événement | Poids | Déclencheur |
|---|---|---|
| Vue produit (`viewCount`) | × 1 | `ProductController::show()` |
| Ajout au panier (`addToCartCount`) | × 3 | `CartController::add()` |
| Achat (`purchaseCount`) | × 10 | `CheckoutController::process()` |

**Score = (views × 1) + (addToCart × 3) + (purchases × 10)**

Un produit peu vu mais souvent acheté remonte plus haut qu'un produit très vu mais jamais converti.

### Où ça se passe

| Fichier | Rôle |
|---|---|
| `src/Entity/ProductStats.php` | Entité avec les 3 compteurs + `updatedAt` |
| `src/Service/PopularityCalculator.php` | Calcul du score + expression DQL |
| `src/Service/ProductStatsManager.php` | Incrémentation des compteurs + flush |
| `src/Repository/ProductRepository.php` | `findByPopularity()` — tri via QueryBuilder |

### Options de tri disponibles

Le catalogue (`/products`) accepte un paramètre `?sort=` :

| Valeur | Tri |
|---|---|
| `popular` *(défaut)* | Score pondéré décroissant |
| `purchases` | Achats décroissants |
| `views` | Vues décroissantes |
| `price_asc` | Prix croissant |
| `price_desc` | Prix décroissant |
---

## 3. Indépendance des deux systèmes

Les deux logiques coexistent sans interférence :

- Un produit peut avoir `isTop = true` avec un score de popularité bas (nouveau produit boosté manuellement).
- Un produit peut avoir `isTop = false` mais dominer le classement de popularité (bestseller organique).
- Le tri par popularité s'applique à tous les produits, badge Top ou non.

Cette séparation respecte le **principe de responsabilité unique (SRP)** : l'éditorial ne pollue pas la donnée comportementale.
---