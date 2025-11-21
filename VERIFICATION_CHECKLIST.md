# ✅ CHECKLIST DE VÉRIFICATION - PHASES 3, 4, 5

## 📋 Vérifications à effectuer avant mise en production

### 1. ✅ Vérifier les dépendances

```bash
composer show | grep intervention/image
composer show | grep aws/aws-sdk-php
composer show | grep guzzlehttp/guzzle
```

**Résultat attendu** : Les 3 packages doivent être listés

---

### 2. ✅ Vérifier les migrations

```bash
php artisan migrate:status
```

**Résultat attendu** : Toutes les migrations doivent être "Ran"

-   `create_categories_table`
-   `create_photos_table`
-   `create_orders_table`
-   `create_order_items_table`

---

### 3. ✅ Vérifier les routes

```bash
php artisan route:list --path=api
```

**Résultat attendu** : Doit afficher ~26 routes incluant :

-   `api/photos` (GET)
-   `api/cart` (GET, POST, PUT, DELETE)
-   `api/orders` (GET, POST)
-   `api/webhooks/cinetpay` (POST)

---

### 4. ✅ Vérifier les fichiers créés

#### Models

```bash
ls -la app/Models/Photo.php
ls -la app/Models/Category.php
ls -la app/Models/Order.php
ls -la app/Models/OrderItem.php
```

#### Services

```bash
ls -la app/Services/StorageService.php
ls -la app/Services/ImageProcessingService.php
ls -la app/Services/PaymentService.php
```

#### Controllers

```bash
ls -la app/Http/Controllers/Api/PhotoController.php
ls -la app/Http/Controllers/Api/SearchController.php
ls -la app/Http/Controllers/Api/CategoryController.php
ls -la app/Http/Controllers/Api/CartController.php
ls -la app/Http/Controllers/Api/OrderController.php
ls -la app/Http/Controllers/Api/WebhookController.php
ls -la app/Http/Controllers/Api/Photographer/PhotoController.php
```

#### Jobs

```bash
ls -la app/Jobs/ProcessPhotoUpload.php
ls -la app/Jobs/ExtractExifData.php
```

---

### 5. ✅ Vérifier la configuration

#### Vérifier .env

```bash
# Vérifier que ces variables existent
grep CINETPAY_SITE_ID .env
grep AWS_BUCKET .env
grep QUEUE_CONNECTION .env
```

#### Vérifier config

```bash
php artisan config:show services.cinetpay
php artisan config:show filesystems.disks.s3
```

---

### 6. ✅ Test Base de données

```bash
php artisan tinker
```

Puis dans tinker :

```php
// Vérifier connexion
DB::connection()->getPdo();

// Compter les tables
DB::select('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()');

// Tester modèles
App\Models\Photo::count();
App\Models\Category::count();
App\Models\Order::count();
```

---

### 7. ✅ Test AWS S3

```bash
php artisan tinker
```

```php
// Test connexion S3
Storage::disk('s3')->put('test.txt', 'Hello Pouire');
Storage::disk('s3')->exists('test.txt'); // Doit retourner true
Storage::disk('s3')->get('test.txt'); // Doit retourner "Hello Pouire"
Storage::disk('s3')->delete('test.txt');
```

---

### 8. ✅ Test Queue/Jobs

```bash
# Terminal 1 : Démarrer worker
php artisan queue:work redis --tries=3

# Terminal 2 : Dispatcher un test
php artisan tinker
```

Dans tinker :

```php
// Créer un job test
dispatch(function () {
    info('Test job executed!');
});

// Vérifier dans storage/logs/laravel.log
```

---

### 9. ✅ Test API Endpoints

#### Health Check

```bash
curl http://localhost:8000/api/health
```

**Attendu** : `{"success":true,"message":"Pouire API is running!"}`

#### Photos (public)

```bash
curl http://localhost:8000/api/photos
```

**Attendu** : JSON avec structure pagination

#### Categories (public)

```bash
curl http://localhost:8000/api/categories
```

**Attendu** : JSON liste catégories

---

### 10. ✅ Test Authentification

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }'
```

**Attendu** : Token JWT

---

### 11. ✅ Test Upload Photo (Photographe)

```bash
# Avec token JWT obtenu ci-dessus
curl -X POST http://localhost:8000/api/photographer/photos \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "photos[]=@/path/to/photo.jpg" \
  -F "title=Test Photo" \
  -F "category_id=category-uuid" \
  -F "tags=test,photo,sample" \
  -F "price_standard=1000" \
  -F "price_extended=2500"
```

**Attendu** : `{"success":true,"message":"1 photo(s) uploadée(s)"}`

---

### 12. ✅ Test Panier

```bash
# Ajouter au panier
curl -X POST http://localhost:8000/api/cart/items \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "photo_id": "photo-uuid",
    "license_type": "standard"
  }'
```

**Attendu** : Panier avec items

```bash
# Voir panier
curl http://localhost:8000/api/cart \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

### 13. ✅ Test Commande

```bash
# Créer commande
curl -X POST http://localhost:8000/api/orders \
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

**Attendu** : Commande créée avec `order_number` format `ORD-YYYYMMDD-ABC123`

---

### 14. ✅ Test Paiement (Sans traiter réellement)

```bash
# Initier paiement
curl -X POST http://localhost:8000/api/orders/{order_id}/pay \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "payment_method": "mobile_money",
    "payment_provider": "ORANGE",
    "phone": "+226 70 12 34 56"
  }'
```

**Attendu** : `payment_url` et `payment_token` CinetPay

---

### 15. ✅ Vérifier les logs

```bash
# Logs généraux
tail -f storage/logs/laravel.log

# Logs spécifiques
grep "CinetPay" storage/logs/laravel.log
grep "ProcessPhotoUpload" storage/logs/laravel.log
grep "ExtractExifData" storage/logs/laravel.log
```

---

### 16. ✅ Vérifier les Policies

```bash
php artisan tinker
```

```php
// Créer un utilisateur test
$user = App\Models\User::first();
$photo = App\Models\Photo::first();

// Tester policy
Gate::allows('view', $photo); // Doit retourner true si public
Gate::allows('update', $photo); // Doit vérifier ownership
```

---

### 17. ✅ Test Recherche

```bash
# Recherche simple
curl "http://localhost:8000/api/search/photos?query=nature"

# Recherche avec filtres
curl "http://localhost:8000/api/search/photos?min_price=500&max_price=2000&orientation=landscape&sort_by=popularity"
```

---

### 18. ✅ Vérifier les Resources

```bash
php artisan tinker
```

```php
// Tester PhotoResource
$photo = App\Models\Photo::with(['photographer', 'category'])->first();
$resource = new App\Http\Resources\PhotoResource($photo);
$resource->toArray(request()); // Doit retourner array avec toutes les clés
```

---

### 19. ✅ Performance Check

```bash
# Vérifier mémoire
php artisan queue:work --memory=512

# Vérifier temps réponse API
time curl http://localhost:8000/api/photos

# Vérifier nombre de requêtes DB
php artisan debugbar:clear # Si debugbar installé
```

---

### 20. ✅ Checklist Finale

| Composant        | Status | Vérification                                   |
| ---------------- | ------ | ---------------------------------------------- |
| ✅ Migrations    | ✓      | `php artisan migrate:status`                   |
| ✅ Models        | ✓      | Fichiers créés                                 |
| ✅ Services      | ✓      | 3 services (Storage, ImageProcessing, Payment) |
| ✅ Jobs          | ✓      | 2 jobs (ProcessPhotoUpload, ExtractExifData)   |
| ✅ Controllers   | ✓      | 7 controllers                                  |
| ✅ Form Requests | ✓      | 5 requests                                     |
| ✅ Resources     | ✓      | 4 resources                                    |
| ✅ Policies      | ✓      | 1 policy                                       |
| ✅ Routes        | ✓      | 26 routes API                                  |
| ✅ Configuration | ✓      | CinetPay + AWS S3                              |
| ✅ Tests manuels | ✓      | Photos, Cart, Orders, Payment                  |

---

## 🎯 CRITÈRES DE RÉUSSITE

### ✅ PHASE 3 : Photos & Catégories

-   [ ] Peut uploader une photo
-   [ ] Photo est traitée (preview, thumbnail, watermark)
-   [ ] EXIF extrait automatiquement
-   [ ] Photos s3 stockées sur AWS S3
-   [ ] Peut rechercher photos avec filtres
-   [ ] Peut voir photos featured/recent/popular

### ✅ PHASE 4 : Panier & Commandes

-   [ ] Peut ajouter au panier
-   [ ] Peut modifier licence (standard/extended)
-   [ ] Peut créer commande
-   [ ] Commissions calculées (20%/80%)
-   [ ] Order_number généré automatiquement

### ✅ PHASE 5 : Paiements CinetPay

-   [ ] Peut initier paiement CinetPay
-   [ ] Reçoit payment_url
-   [ ] Webhook CinetPay fonctionne
-   [ ] Order marquée completed après paiement
-   [ ] URL téléchargement générée (24h)

---

## 🚨 PROBLÈMES COURANTS

### Erreur : "Class 'Intervention\Image\ImageManager' not found"

```bash
composer require intervention/image intervention/image-laravel
```

### Erreur : AWS S3 - "InvalidAccessKeyId"

```bash
# Vérifier .env
AWS_ACCESS_KEY_ID=correct-key-id
AWS_SECRET_ACCESS_KEY=correct-secret-key
```

### Erreur : Queue - "No default queue connection defined"

```bash
# Vérifier .env
QUEUE_CONNECTION=redis

# Vérifier Redis
redis-cli ping # Doit retourner PONG
```

### Erreur : CinetPay - "Invalid signature"

```bash
# Vérifier que les credentials sont corrects
# Vérifier config/services.php
```

---

## 📞 SUPPORT

Si tous les tests passent : **✅ L'API est prête pour la production !**

Si des tests échouent :

1. Vérifier logs : `storage/logs/laravel.log`
2. Vérifier configuration : `.env`
3. Vérifier dépendances : `composer install`
4. Vérifier migrations : `php artisan migrate`
5. Vérifier queues : `php artisan queue:work`

---

**Checklist créée le :** 2025-11-13
**Version API :** 1.0.0
**Phases complétées :** 3, 4, 5 (95%)
