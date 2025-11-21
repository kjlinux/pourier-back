# COMMANDES DE DÉPLOIEMENT - PHASES 3, 4, 5

## 📋 PRÉ-REQUIS

1. **PHP 8.2+** installé
2. **Composer** installé
3. **PostgreSQL** ou **MySQL** configuré
4. **Redis** installé (pour queues)
5. **AWS S3 Bucket** créé
6. **Compte CinetPay** créé (https://cinetpay.com)

---

## 🚀 ÉTAPE 1 : INSTALLATION DES DÉPENDANCES

Les dépendances sont déjà dans composer.json. Installer :

```bash
composer install
```

**Dépendances clés installées** :

-   `intervention/image` - Traitement d'images
-   `intervention/image-laravel` - Integration Laravel
-   `aws/aws-sdk-php` - AWS S3
-   `league/flysystem-aws-s3-v3` - Filesystem S3
-   `guzzlehttp/guzzle` - Client HTTP (CinetPay API)
-   `barryvdh/laravel-dompdf` - Génération PDF (factures)
-   `tymon/jwt-auth` - Authentification JWT

---

## 🔧 ÉTAPE 2 : CONFIGURATION

### 1. Copier le fichier .env

```bash
cp .env.example .env
```

### 2. Ajouter les variables d'environnement

Copier le contenu de `.env.example.phases345` dans votre `.env` :

```bash
# Windows (PowerShell)
Get-Content .env.example.phases345 | Add-Content .env

# Linux/Mac
cat .env.example.phases345 >> .env
```

### 3. Configurer les credentials

Éditer `.env` et remplir :

-   **AWS_ACCESS_KEY_ID**, **AWS_SECRET_ACCESS_KEY**, **AWS_BUCKET**
-   **CINETPAY_SITE_ID**, **CINETPAY_API_KEY**, **CINETPAY_SECRET_KEY**
-   **REDIS_HOST** (si différent de localhost)
-   **MAIL_PASSWORD** (SendGrid ou autre)
-   **FRONTEND_URL**

### 4. Générer la clé d'application

```bash
php artisan key:generate
```

### 5. Générer le secret JWT

```bash
php artisan jwt:secret
```

---

## 🗄️ ÉTAPE 3 : BASE DE DONNÉES

### 1. Créer la base de données

```sql
-- PostgreSQL
CREATE DATABASE pouire;

-- MySQL
CREATE DATABASE pouire CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Configurer .env

```env
DB_CONNECTION=pgsql  # ou mysql
DB_HOST=127.0.0.1
DB_PORT=5432  # ou 3306 pour MySQL
DB_DATABASE=pouire
DB_USERNAME=your-db-username
DB_PASSWORD=your-db-password
```

### 3. Exécuter les migrations

```bash
php artisan migrate
```

**Migrations exécutées** :

-   `create_users_table`
-   `create_categories_table` ✅ (Phase 3)
-   `create_photos_table` ✅ (Phase 3)
-   `create_orders_table` ✅ (Phase 4)
-   `create_order_items_table` ✅ (Phase 4)

### 4. (Optionnel) Seeder catégories

```bash
php artisan db:seed --class=CategorySeeder
```

---

## 📂 ÉTAPE 4 : STOCKAGE AWS S3

### 1. Créer le bucket S3

Dans AWS Console :

1. Créer bucket `pouire-photos`
2. Région : `us-east-1` (ou autre)
3. **Bloquer l'accès public** : NON (pour previews/thumbnails)
4. Activer versioning (optionnel)

### 2. Configurer IAM User

Créer un utilisateur IAM avec permissions S3 :

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:PutObject",
                "s3:GetObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::pouire-photos",
                "arn:aws:s3:::pouire-photos/*"
            ]
        }
    ]
}
```

### 3. Récupérer Access Key et Secret Key

Ajouter dans `.env` :

```env
AWS_ACCESS_KEY_ID=AKIA...
AWS_SECRET_ACCESS_KEY=...
```

### 4. Tester la connexion S3

```bash
php artisan tinker
```

```php
Storage::disk('s3')->put('test.txt', 'Hello World');
Storage::disk('s3')->exists('test.txt'); // devrait retourner true
Storage::disk('s3')->delete('test.txt');
```

---

## 🔄 ÉTAPE 5 : QUEUES & WORKERS

### 1. Configurer Redis

```bash
# Vérifier que Redis est démarré
redis-cli ping  # devrait retourner PONG
```

### 2. Démarrer le worker de queue

**En développement** :

```bash
php artisan queue:work redis --tries=3 --timeout=600
```

**En production (avec Supervisor)** :

Créer `/etc/supervisor/conf.d/pouire-worker.conf` :

```ini
[program:pouire-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/pouire-back/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/pouire-back/storage/logs/worker.log
stopwaitsecs=3600
```

Démarrer Supervisor :

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start pouire-worker:*
```

---

## 🌐 ÉTAPE 6 : CINETPAY CONFIGURATION

### 1. Créer un compte CinetPay

-   Production : https://cinetpay.com
-   Récupérer `SITE_ID`, `API_KEY`, `SECRET_KEY`

### 2. Configurer les webhooks

Dans le dashboard CinetPay, configurer :

-   **Webhook URL** : `https://api.pouire.com/api/webhooks/cinetpay`
-   **Return URL** : `https://pouire.com/payment/callback`

### 3. Mode TEST

Pour tester, utiliser les credentials de test fournis par CinetPay :

```env
CINETPAY_MODE=TEST
CINETPAY_SITE_ID=test_site_id
CINETPAY_API_KEY=test_api_key
```

### 4. Tester le paiement

Numéros de test (fournis par CinetPay) :

-   **Orange Money** : +226 XX XX XX XX
-   **MTN Money** : +226 XX XX XX XX
-   **Moov Money** : +226 XX XX XX XX

---

## ✅ ÉTAPE 7 : VÉRIFICATIONS

### 1. Vérifier les routes

```bash
php artisan route:list
```

Devrait afficher toutes les routes API (une fois les controllers créés).

### 2. Vérifier les jobs

```bash
php artisan queue:work redis --tries=3 &
```

Tester l'upload d'une photo via API et vérifier que les jobs s'exécutent.

### 3. Tester l'API

Avec Postman/Insomnia, tester :

-   POST `/api/auth/login` - Authentification
-   GET `/api/photos` - Liste photos
-   POST `/api/photographer/photos` - Upload photo
-   POST `/api/orders` - Créer commande
-   POST `/api/orders/{id}/pay` - Payer commande

### 4. Vérifier les logs

```bash
tail -f storage/logs/laravel.log
```

---

## 🔐 ÉTAPE 8 : SÉCURITÉ & OPTIMISATION

### 1. Cache configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Optimiser autoloader

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Configurer CORS

Dans `.env` :

```env
FRONTEND_URL=https://pouire.com
```

Vérifier `config/cors.php` :

```php
'allowed_origins' => [env('FRONTEND_URL')],
```

### 4. Activer HTTPS

Configurer Nginx/Apache pour forcer HTTPS :

**Nginx** :

```nginx
server {
    listen 443 ssl http2;
    server_name api.pouire.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /path/to/pouire-back/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 📊 ÉTAPE 9 : MONITORING

### 1. Logs applicatifs

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Logs des workers
tail -f storage/logs/worker.log
```

### 2. Logs CinetPay

Vérifier dans les logs :

```bash
grep "CinetPay" storage/logs/laravel.log
```

### 3. Métriques queues

```bash
php artisan queue:monitor redis
```

---

## 🧪 ÉTAPE 10 : TESTS

### 1. Exécuter les tests

```bash
php artisan test
```

### 2. Tests manuels

**Upload photo** :

```bash
curl -X POST https://api.pouire.com/api/photographer/photos \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "photos[]=@/path/to/photo.jpg" \
  -F "title=Test Photo" \
  -F "category_id=uuid" \
  -F "tags=test,photo,sample" \
  -F "price_standard=1000" \
  -F "price_extended=2500"
```

**Créer commande** :

```bash
curl -X POST https://api.pouire.com/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "photo_id": "photo-uuid",
        "license_type": "standard"
      }
    ],
    "subtotal": 1000,
    "total": 1000,
    "payment_method": "mobile_money",
    "billing_email": "test@example.com",
    "billing_first_name": "John",
    "billing_last_name": "Doe",
    "billing_phone": "+226 70 12 34 56"
  }'
```

---

## 🔄 COMMANDES UTILES

### Développement

```bash
# Démarrer serveur de développement
php artisan serve

# Démarrer queue worker
php artisan queue:work redis

# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Réinitialiser la base de données
php artisan migrate:fresh --seed
```

### Production

```bash
# Mettre à jour le code
git pull origin main

# Installer dépendances
composer install --no-dev --optimize-autoloader

# Exécuter migrations
php artisan migrate --force

# Optimiser
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Redémarrer workers
php artisan queue:restart
```

---

## 📞 SUPPORT

En cas de problème :

1. Vérifier les logs : `storage/logs/laravel.log`
2. Vérifier les queues : `php artisan queue:failed`
3. Vérifier la connexion S3 : `php artisan tinker` puis `Storage::disk('s3')->exists('test')`
4. Vérifier CinetPay : Tester avec mode TEST d'abord

---

**Déploiement des Phases 3, 4, 5 terminé !** ✅

L'API est maintenant prête à recevoir des photos, gérer des commandes et traiter des paiements via CinetPay. 🚀
