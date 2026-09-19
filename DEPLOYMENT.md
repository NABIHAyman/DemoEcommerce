# Guide de déploiement — Minimalist Store

Mise en ligne sur un serveur Linux (Debian 12 / Ubuntu 22.04 LTS).
Chaque commande est à exécuter dans l'ordre.

> **Rappel.** Ce projet est un travail académique. Ce guide décrit une mise en
> ligne correcte, pas une infrastructure de production critique : il n'y a ni
> haute disponibilité, ni supervision, ni plan de reprise.

---

## Sommaire

1. [Préparation du serveur](#1-préparation-du-serveur)
2. [Récupération du code](#2-récupération-du-code)
3. [Variables d'environnement](#3-variables-denvironnement)
4. [Base de données](#4-base-de-données)
5. [Build de production](#5-build-de-production)
6. [Reverse proxy Nginx](#6-reverse-proxy-nginx)
7. [HTTPS](#7-https)
8. [Permissions](#8-permissions)
9. [Vérification](#9-vérification)
10. [Mise à jour](#10-mise-à-jour)
11. [Sauvegardes](#11-sauvegardes)
12. [Dépannage](#12-dépannage)

---

## 1. Préparation du serveur

### Prérequis

| Composant | Version minimale |
|---|---|
| Debian / Ubuntu | Debian 12 ou Ubuntu 22.04 LTS |
| Docker Engine | 24.0 |
| Docker Compose | v2 |
| RAM | 2 Go (4 Go recommandés) |
| Disque | 10 Go libres |

### Installation de Docker

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y ca-certificates curl git
```

```bash
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
```

```bash
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

```bash
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

Vérifier :

```bash
docker --version && docker compose version
```

### Utilisateur de service

Ne pas faire tourner l'application en `root` :

```bash
sudo adduser --disabled-password --gecos "" deploy
sudo usermod -aG docker deploy
sudo su - deploy
```

### Pare-feu

```bash
sudo apt install -y ufw
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

> Les ports **33060** (MySQL) et **9002** (phpMyAdmin) ne doivent **jamais** être
> ouverts vers l'extérieur.

---

## 2. Récupération du code

```bash
cd /home/deploy
git clone https://github.com/NABIHAyman/DemoEcommerce.git
cd DemoEcommerce
```

---

## 3. Variables d'environnement

Créer le fichier `.env.local`. **Il ne doit jamais être versionné.**

```bash
cp .env .env.local
```

Générer une clé applicative unique :

```bash
openssl rand -hex 32
```

Éditer `.env.local` et renseigner les variables suivantes.
Les valeurs sont volontairement absentes de ce guide :

| Variable | Valeur attendue |
|---|---|
| `APP_ENV` | `prod` |
| `APP_DEBUG` | `0` |
| `APP_SECRET` | La clé générée ci-dessus |
| `DATABASE_URL` | `mysql://UTILISATEUR:MOT_DE_PASSE@database:3306/NOM_BASE?serverVersion=8.0` |
| `MAILER_DSN` | DSN du fournisseur SMTP |
| `MESSENGER_TRANSPORT_DSN` | Transport Messenger |
| `CORS_ALLOW_ORIGIN` | Expression régulière du domaine public |

Restreindre les droits du fichier :

```bash
chmod 600 .env.local
```

### Durcir `compose.yaml` pour la production

Avant de démarrer, apporter trois modifications :

1. **Supprimer le service `phpmyadmin`** — il n'a rien à faire en ligne.
2. **Retirer la publication du port MySQL** : supprimer le bloc
   `ports: - "33060:3306"` du service `database`. Le service reste joignable par
   les autres conteneurs via le réseau `internal_network`.
3. **Remplacer le mot de passe MySQL** : le fichier versionné contient
   `MYSQL_ROOT_PASSWORD: root`, valeur de développement. Définir un mot de passe
   fort, et de préférence créer un utilisateur applicatif dédié plutôt
   qu'utiliser `root`.

---

## 4. Base de données

Démarrer d'abord la base seule :

```bash
docker compose up -d database
```

Attendre qu'elle accepte les connexions :

```bash
docker compose exec database mysqladmin ping -h localhost --silent && echo "MySQL prêt"
```

Créer l'utilisateur applicatif :

```bash
docker compose exec database mysql -u root -p
```

```sql
CREATE DATABASE IF NOT EXISTS nom_base CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'utilisateur_app'@'%' IDENTIFIED BY 'mot_de_passe_fort';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON nom_base.* TO 'utilisateur_app'@'%';
FLUSH PRIVILEGES;
EXIT;
```

Reporter ces valeurs dans `DATABASE_URL` (`.env.local`).

---

## 5. Build de production

```bash
docker compose build --no-cache
docker compose up -d
```

Installer les dépendances **sans les paquets de développement** :

```bash
docker compose exec php composer install --no-dev --optimize-autoloader --no-interaction
```

Appliquer les migrations :

```bash
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

Garantir la cohérence des statistiques produit :

```bash
docker compose exec php php bin/console app:ensure-product-stats
```

Compiler les assets et préchauffer le cache :

```bash
docker compose exec php php bin/console asset-map:compile
docker compose exec php php bin/console cache:clear --env=prod --no-debug
docker compose exec php php bin/console cache:warmup --env=prod
```

> **Ne pas charger les fixtures en production.** La commande
> `doctrine:fixtures:load` efface la base et crée des comptes de démonstration
> aux mots de passe publics.

---

## 6. Reverse proxy Nginx

Le `compose.yaml` embarque déjà un Nginx qui écoute sur les ports 80 et 443, avec
son vhost dans `infra/nginx/conf.d/nginx.conf`. Deux approches :

### Option A — utiliser le Nginx du Compose

Éditer `infra/nginx/conf.d/nginx.conf` pour y renseigner le nom de domaine réel
dans la directive `server_name`, puis :

```bash
docker compose restart nginx_webserver
```

### Option B — Nginx sur l'hôte, en frontal

Utile si le serveur héberge plusieurs applications. Il faut alors remapper le
Nginx du Compose sur un port interne (par exemple `8080:80`) et retirer `443`.

```bash
sudo apt install -y nginx
sudo nano /etc/nginx/sites-available/minimalist-store
```

```nginx
server {
    listen 80;
    server_name exemple.tld www.exemple.tld;

    client_max_body_size 8M;

    location / {
        proxy_pass         http://127.0.0.1:8080;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/minimalist-store /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

> Avec cette option, déclarer le proxy comme fiable dans Symfony
> (`framework.trusted_proxies`), sinon les URL générées et la détection HTTPS
> seront fausses.

---

## 7. HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d exemple.tld -d www.exemple.tld
```

Vérifier le renouvellement automatique :

```bash
sudo systemctl status certbot.timer
sudo certbot renew --dry-run
```

---

## 8. Permissions

Symfony doit pouvoir écrire dans `var/` (cache et logs), et nulle part ailleurs :

```bash
docker compose exec php chown -R www-data:www-data var
docker compose exec php chmod -R 775 var
```

Vérifier que `.env.local` reste illisible par les autres utilisateurs :

```bash
ls -l .env.local   # attendu : -rw-------
```

---

## 9. Vérification

```bash
docker compose ps
```

Les quatre — ou trois, si `phpmyadmin` a été retiré — conteneurs doivent être `Up`.

```bash
curl -I http://localhost
```

Vérifier que l'environnement est bien en production :

```bash
docker compose exec php php bin/console about | grep -iE "environment|debug"
```

Attendu : `Environment: prod`, `Debug: false`.

Consulter les journaux :

```bash
docker compose logs -f --tail=100 php
docker compose logs -f --tail=100 nginx_webserver
```

### Points de contrôle

- [ ] Le site répond en HTTPS sur le domaine public
- [ ] `APP_ENV=prod` et `APP_DEBUG=0`
- [ ] La barre de debug Symfony n'apparaît pas
- [ ] Les ports 33060 et 9002 ne sont pas joignables depuis l'extérieur
- [ ] Le mot de passe MySQL n'est plus la valeur par défaut
- [ ] Les fixtures n'ont pas été chargées
- [ ] `.env.local` est en `chmod 600` et absent de Git

---

## 10. Mise à jour

```bash
cd /home/deploy/DemoEcommerce
git pull origin main
docker compose build
docker compose up -d
docker compose exec php composer install --no-dev --optimize-autoloader --no-interaction
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console asset-map:compile
docker compose exec php php bin/console cache:clear --env=prod --no-debug
```

---

## 11. Sauvegardes

Sauvegarde de la base :

```bash
docker compose exec -T database mysqldump -u root -p nom_base > sauvegarde-$(date +%F).sql
```

Automatiser via `cron` :

```bash
crontab -e
```

```cron
0 3 * * * cd /home/deploy/DemoEcommerce && docker compose exec -T database mysqldump -u root -pMOT_DE_PASSE nom_base | gzip > /home/deploy/backups/db-$(date +\%F).sql.gz
```

> Écrire un mot de passe dans une crontab n'est acceptable que si le fichier est
> en `chmod 600`. Préférer un fichier d'options MySQL (`~/.my.cnf`, `chmod 600`).

Restauration :

```bash
cat sauvegarde-2026-09-19.sql | docker compose exec -T database mysql -u root -p nom_base
```

---

## 12. Dépannage

| Symptôme | Cause probable | Correction |
|---|---|---|
| Erreur 500 sans détail | Cache construit en `dev`, ou `var/` non inscriptible | `cache:clear --env=prod` puis rétablir les permissions (§8) |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL pas encore prêt, ou mauvais hôte dans `DATABASE_URL` | L'hôte doit être `database`, pas `localhost` |
| `Access denied for user` | Identifiants incohérents entre `.env.local` et MySQL | Rejouer le §4 et resynchroniser |
| Page blanche, CSS absent | Assets non compilés | `php bin/console asset-map:compile` |
| La barre de debug s'affiche | `APP_ENV` toujours sur `dev` | Corriger `.env.local`, vider le cache |
| `Permission denied` sur `var/log` | Propriétaire incorrect | `chown -R www-data:www-data var` |
| Mauvaises URL générées derrière un proxy | `trusted_proxies` non configuré | Déclarer le proxy dans `config/packages/framework.yaml` |

---

## Auteur

**Ayman NABIH**
[github.com/NABIHAyman](https://github.com/NABIHAyman) ·
[linkedin.com/in/nabihayman](https://linkedin.com/in/nabihayman)
