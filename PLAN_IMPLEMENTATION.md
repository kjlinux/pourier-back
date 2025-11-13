# PLAN D'IMPLÉMENTATION COMPLET - BACKEND Pourier/POUIRE

> Plan détaillé pour l'implémentation complète du backend Laravel 12 basé sur les spécifications BACKEND_SPECIFICATION.md et BACKEND_SPECIFICATION_PART2.md

---

## 📋 VUE D'ENSEMBLE DU PROJET

### Objectif

Développer un backend Laravel 12 complet pour une plateforme de vente de photos africaines avec :

-   **Authentification JWT** sécurisée (tymon/jwt-auth)
-   **Stockage AWS S3** pour toutes les images
-   **Paiements CinetPay** : Mobile Money (Orange, MTN, Moov, Wave) + Cartes bancaires
-   **Système de revenus** avec période de sécurité 30 jours avant retrait
-   **Upload & traitement automatique** : watermarks, thumbnails, extraction EXIF
-   **Modération** : validation photos et profils photographes
-   **Notifications** : in-app et emails transactionnels
-   **API REST complète** : ~70 endpoints

### Acteurs

-   **Buyers** : Achètent des photos
-   **Photographers** : Uploadent et vendent des photos (commission 80%)
-   **Admins** : Modèrent photos, valident photographes, gèrent retraits

### Modèle Économique

-   **Commission plateforme** : 20% sur chaque vente
-   **Revenu photographe** : 80% du prix de vente
-   **Période de sécurité** : 30 jours avant disponibilité retrait
-   **Retrait minimum** : 5000 FCFA
-   **Prix minimum photo** : 500 FCFA
-   **Prix extended** : minimum 2x le prix standard
-   **Devise** : Franc CFA (XOF) - montants en **integer** (pas de décimales)

---

## 🏗️ PHASE 1 : SETUP & INFRASTRUCTURE

**Durée estimée : 3-5 jours**

### 1.1 Configuration Projet Laravel

```bash
# Créer projet Laravel 12
composer create-project laravel/laravel Pourier-backend "^12.0"
cd Pourier-backend

# Installer packages requis
composer require tymon/jwt-auth:"^2.1"
composer require intervention/image:"^3.0"
composer require league/flysystem-aws-s3-v3:"^3.0"
composer require spatie/laravel-permission:"^6.0"
composer require barryvdh/laravel-dompdf:"^3.0"
composer require guzzlehttp/guzzle:"^7.8"

# Dev dependencies
composer require --dev laravel/telescope:"^5.0"
composer require --dev laravel/pint:"^1.13"
```

### 1.2 Configuration Base de Données PostgreSQL

**Fichier : `config/database.php`**

```php
'connections' => [
    'pgsql' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => env('DB_DATABASE', 'pourier_db'),
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
        'sslmode' => 'prefer',
    ],
],
```

**Fichier : `.env`**

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pourier_db
DB_USERNAME=postgres
DB_PASSWORD=
```

### 1.3 Configuration Redis (Cache & Queues)

**Fichier : `.env`**

```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 1.4 Migrations Base de Données (11 tables)

**Ordre de création :**

1. **`users`** - Table principale utilisateurs
2. **`photographer_profiles`** - Profils photographes
3. **`categories`** - Catégories photos (hiérarchiques)
4. **`photos`** - Photos avec métadonnées complètes
5. **`orders`** - Commandes
6. **`order_items`** - Lignes de commandes
7. **`withdrawals`** - Demandes de retrait
8. **`notifications`** - Notifications in-app
9. **`favorites`** - Photos favorites (pivot)
10. **`follows`** - Suivis photographes (pivot)
11. **`revenues`** - Revenus mensuels photographes

**Commandes à exécuter :**

```bash
# Créer les migrations
php artisan make:migration create_users_table
php artisan make:migration create_photographer_profiles_table
php artisan make:migration create_categories_table
php artisan make:migration create_photos_table
php artisan make:migration create_orders_table
php artisan make:migration create_order_items_table
php artisan make:migration create_withdrawals_table
php artisan make:migration create_notifications_table
php artisan make:migration create_favorites_table
php artisan make:migration create_follows_table
php artisan make:migration create_revenues_table

# Exécuter les migrations
php artisan migrate
```

**Détails des migrations : voir BACKEND_SPECIFICATION.md section 5**

### 1.5 Configuration AWS S3

**Fichier : `config/filesystems.php`**

```php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'visibility' => 'public',
    ],
],
```

**Fichier : `.env`**

```env
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=Pourier-photos
AWS_URL=https://pourier-photos.s3.amazonaws.com

# Optionnel: CloudFront CDN
AWS_CLOUDFRONT_URL=https://d1234abcd.cloudfront.net
```

**Structure buckets S3 :**

```
Pourier-photos/
├── photos/
│   └── {photographer_id}/
│       ├── originals/
│       ├── previews/ (avec watermark)
│       └── thumbnails/
├── avatars/
│   └── {user_id}/
├── covers/
│   └── {photographer_id}/
└── invoices/
```

### 1.6 Configuration Services Externes

**CinetPay (Paiements)**

**Fichier : `config/services.php`**

```php
'cinetpay' => [
    'api_url' => env('CINETPAY_API_URL', 'https://api-checkout.cinetpay.com/v2'),
    'site_id' => env('CINETPAY_SITE_ID'),
    'api_key' => env('CINETPAY_API_KEY'),
    'secret_key' => env('CINETPAY_SECRET_KEY'),
    'notify_url' => env('CINETPAY_NOTIFY_URL'),
    'return_url' => env('CINETPAY_RETURN_URL'),
],
```

**SendGrid/Mailgun (Emails)**

**Fichier : `.env`**

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@Pourier.com
MAIL_FROM_NAME="Pourier"
```

**Sentry (Monitoring)**

```env
SENTRY_LARAVEL_DSN=your-sentry-dsn
```

### 1.7 Seeders de Base

**Créer CategorySeeder :**

```bash
php artisan make:seeder CategorySeeder
```

**Catégories initiales :**

-   Portrait
-   Paysage
-   Nature
-   Événements
-   Street Photography
-   Architecture
-   Lifestyle
-   Culture Africaine

---

## 🔐 PHASE 2 : AUTHENTIFICATION & UTILISATEURS

**Durée estimée : 4-6 jours**

### 2.1 Modèles Eloquent

**Créer les modèles :**

```bash
php artisan make:model User
php artisan make:model PhotographerProfile
```

**`app/Models/User.php`**

Fonctionnalités :

-   ✅ Implémente `JWTSubject` (tymon/jwt-auth)
-   ✅ Traits : `HasUuids`, `SoftDeletes`, `Notifiable`, `HasRoles`
-   ✅ Relations : photographerProfile, photos, orders, withdrawals, favorites, following/followers
-   ✅ Scopes : active, verified, photographers, buyers, admins
-   ✅ Méthodes : isPhotographer(), isAdmin(), isBuyer()

**`app/Models/PhotographerProfile.php`**

Fonctionnalités :

-   ✅ Relations : user, approvedBy
-   ✅ Scopes : pending, approved, rejected, suspended
-   ✅ Méthodes : approve(), reject(), suspend()
-   ✅ Statuts : pending, approved, rejected, suspended

### 2.2 Configuration JWT

```bash
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

**Fichier : `config/jwt.php`**

```php
'ttl' => env('JWT_TTL', 60), // 60 minutes
'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 14 jours
'blacklist_enabled' => true,
```

**Fichier : `config/auth.php`**

```php
'defaults' => [
    'guard' => 'api',
],

'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

### 2.3 AuthService

**Créer le service :**

```bash
php artisan make:service AuthService
```

**Méthodes :**

-   `register(array $data): array` - Inscription utilisateur

    -   Création User
    -   Si photographer : création PhotographerProfile automatique
    -   Génération username unique
    -   Retour : user + token JWT

-   `login(string $email, string $password, bool $rememberMe): array`

    -   Vérification credentials
    -   Vérification compte actif
    -   Mise à jour last_login
    -   Retour : user + token JWT

-   `logout(): void` - Invalidation token JWT
-   `refresh(): string` - Rafraîchissement token
-   `me(): User` - Utilisateur authentifié

### 2.4 Form Requests (Validation)

```bash
php artisan make:request Auth/LoginRequest
php artisan make:request Auth/RegisterRequest
```

**`LoginRequest`**

-   email : required, email
-   password : required, string, min:6
-   remember_me : boolean

**`RegisterRequest`**

-   first_name : required, string, min:2, max:50
-   last_name : required, string, min:2, max:50
-   email : required, email, unique:users,email
-   password : required, confirmed, Password::min(8)->letters()->numbers()->symbols()
-   account_type : required, in:buyer,photographer
-   phone : nullable, regex:/^\\+226\\s?\\d{2}\\s?\\d{2}\\s?\\d{2}\\s?\\d{2}$/

### 2.5 Controllers Auth

```bash
php artisan make:controller Api/Auth/AuthController
php artisan make:controller Api/Auth/PasswordController
php artisan make:controller Api/Auth/VerificationController
```

**`AuthController`**

-   register() : POST /api/auth/register
-   login() : POST /api/auth/login
-   logout() : POST /api/auth/logout (protected)
-   refresh() : POST /api/auth/refresh (protected)
-   me() : GET /api/auth/me (protected)

**`PasswordController`**

-   forgotPassword() : POST /api/auth/forgot-password
-   resetPassword() : POST /api/auth/reset-password
-   changePassword() : POST /api/auth/change-password (protected)

**`VerificationController`**

-   verify() : GET /api/auth/verify-email/{token}
-   resend() : POST /api/auth/resend-verification (protected)

### 2.6 API Resources

```bash
php artisan make:resource UserResource
```

**Transformation données User :**

```php
return [
    'id' => $this->id,
    'email' => $this->email,
    'full_name' => $this->full_name,
    'avatar_url' => $this->avatar_url,
    'account_type' => $this->account_type,
    'is_verified' => $this->is_verified,
    'photographer_profile' => new PhotographerProfileResource($this->whenLoaded('photographerProfile')),
];
```

### 2.7 Routes API

**Fichier : `routes/api.php`**

```php
// Public
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordController::class, 'resetPassword']);
});

// Protected
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [PasswordController::class, 'changePassword']);
});
```

---

## 📸 PHASE 3 : PHOTOS & CATÉGORIES

**Durée estimée : 5-7 jours**

### 3.1 Modèles Eloquent

```bash
php artisan make:model Photo
php artisan make:model Category
```

**`Photo` Model**

Relations :

-   belongsTo : photographer, category, moderator
-   hasMany : orderItems
-   belongsToMany : favoritedBy (users via favorites)

Scopes :

-   public(), approved(), pending(), rejected(), featured()
-   search($term) - Full-text PostgreSQL
-   filterByCategory($ids)
-   filterByPhotographer($id)
-   filterByPrice($min, $max)
-   filterByOrientation($orientation)
-   sortBy($sortBy)

Méthodes :

-   approve(User $moderator)
-   reject(User $moderator, string $reason)
-   incrementViews(), incrementSales(), incrementDownloads()

**`Category` Model**

Relations :

-   belongsTo : parent
-   hasMany : children, photos

Méthodes :

-   updatePhotoCount() - Met à jour photo_count

### 3.2 Services Traitement Images

**`StorageService`**

```bash
php artisan make:service StorageService
```

Méthodes :

-   `storePhoto(UploadedFile $file, string $photographerId, string $type): string`

    -   Types : original, preview, thumbnail
    -   Retourne : URL S3

-   `storeAvatar(UploadedFile $file, string $userId): string`
-   `storeCoverPhoto(UploadedFile $file, string $photographerId): string`
-   `storeInvoice(string $content, string $orderNumber): string`
-   `generateSignedUrl(string $path, int $expirationMinutes = 1440): string`
-   `delete(string $url): bool`

**`ImageProcessingService`**

```bash
php artisan make:service ImageProcessingService
```

Méthodes :

-   `processUploadedPhoto(string $tempPath, string $photographerId): array`

    -   Génère thumbnail (300x300)
    -   Génère preview avec watermark (1200px max)
    -   Upload vers S3 (original, preview, thumbnail)
    -   Extraction métadonnées : width, height, file_size, format, color_palette
    -   Retourne : URLs + métadonnées

-   `generateThumbnail($image, string $originalPath): string`
-   `generatePreviewWithWatermark($image, string $originalPath): string`
-   `applyWatermark($image)` - Pattern diagonal "Pouire"
-   `extractMetadata($image, string $path): array`
-   `extractColorPalette($image): array`
-   `parseExifData(array $exif): array`

### 3.3 Jobs Asynchrones

```bash
php artisan make:job ProcessPhotoUpload
php artisan make:job ExtractExifData
php artisan make:job GenerateWatermark
```

**`ProcessPhotoUpload`**

-   Timeout : 300 secondes (5 minutes)
-   Tries : 3
-   Process :
    1. Appeler ImageProcessingService::processUploadedPhoto()
    2. Mettre à jour Photo avec URLs et métadonnées
    3. Dispatcher ExtractExifData
    4. Nettoyer fichiers temporaires

**`ExtractExifData`**

-   Télécharger temporairement image depuis S3
-   Extraire EXIF : camera, lens, ISO, aperture, shutter_speed, focal_length, taken_at
-   Mettre à jour Photo
-   Nettoyer fichier temporaire

### 3.4 Form Requests

```bash
php artisan make:request Photo/StorePhotoRequest
php artisan make:request Photo/UpdatePhotoRequest
php artisan make:request Photo/SearchPhotoRequest
```

**`StorePhotoRequest`**

Validation :

-   photos : required, array, min:1
-   photos.\* : required, image, mimes:jpeg,jpg,png, max:51200 (50MB)
-   title : required, string, min:5, max:200
-   description : nullable, string, max:2000
-   category_id : required, exists:categories,id
-   tags : required, string, validation custom (min 3 tags, max 20)
-   price_standard : required, integer, min:500 (FCFA)
-   price_extended : required, integer, gte:price_standard, min 2x price_standard
-   location : nullable, string, max:100

Authorization :

```php
public function authorize(): bool
{
    return $this->user()->isPhotographer();
}
```

**`UpdatePhotoRequest`**

-   Même validation avec 'sometimes' au lieu de 'required'

**`SearchPhotoRequest`**

-   query : nullable, string, max:200
-   categories : nullable, array
-   photographer_id : nullable, exists:users,id
-   min_price, max_price : nullable, integer
-   orientation : nullable, in:landscape,portrait,square
-   sort_by : nullable, in:popularity,date,price_asc,price_desc
-   per_page : nullable, integer, min:1, max:100

### 3.5 Controllers

```bash
php artisan make:controller Api/PhotoController
php artisan make:controller Api/SearchController
php artisan make:controller Api/CategoryController
php artisan make:controller Api/Photographer/PhotoController
```

**`PhotoController` (Public)**

Méthodes :

-   index() : GET /api/photos - Liste paginée photos publiques approuvées
-   show(Photo $photo) : GET /api/photos/{photo} - Détail photo
-   featured() : GET /api/photos/featured - Photos en vedette
-   recent() : GET /api/photos/recent - Photos récentes (limit 12)
-   popular() : GET /api/photos/popular - Photos populaires (par views_count)
-   similar(Photo $photo) : GET /api/photos/{photo}/similar - Photos similaires (même catégorie/photographe)
-   incrementView(Photo $photo) : POST /api/photos/{photo}/view - Incrémenter vues

**`SearchController`**

Méthodes :

-   search(SearchPhotoRequest $request) : GET /api/photos/search
    -   Filtres : query, categories, photographer_id, min_price, max_price, orientation
    -   Tri : popularity, date, price_asc, price_desc
    -   Pagination

**`CategoryController`**

Méthodes :

-   index() : GET /api/categories - Liste catégories actives
-   show(Category $category) : GET /api/categories/{category}
-   photos(Category $category) : GET /api/categories/{category}/photos

**`Photographer/PhotoController`**

Méthodes :

-   index() : GET /api/photographer/photos - Mes photos
-   store(StorePhotoRequest) : POST /api/photographer/photos - Upload photos
-   show(Photo $photo) : GET /api/photographer/photos/{photo}
-   update(UpdatePhotoRequest, Photo $photo) : PUT /api/photographer/photos/{photo}
-   destroy(Photo $photo) : DELETE /api/photographer/photos/{photo}

Middleware : `photographer` (CheckPhotographer)

### 3.6 API Resources

```bash
php artisan make:resource PhotoResource
php artisan make:resource CategoryResource
```

**PhotoResource :**

```php
return [
    'id' => $this->id,
    'title' => $this->title,
    'description' => $this->description,
    'preview_url' => $this->preview_url,
    'thumbnail_url' => $this->thumbnail_url,
    'price_standard' => $this->price_standard,
    'price_extended' => $this->price_extended,
    'category' => new CategoryResource($this->whenLoaded('category')),
    'photographer' => new UserResource($this->whenLoaded('photographer')),
    'stats' => [
        'views' => $this->views_count,
        'sales' => $this->sales_count,
        'favorites' => $this->favorites_count,
    ],
];
```

### 3.7 Policies

```bash
php artisan make:policy PhotoPolicy --model=Photo
```

**Méthodes :**

-   viewAny() : Tous
-   view(User $user, Photo $photo) : Owner ou photo publique
-   create(User $user) : Photographers approuvés
-   update(User $user, Photo $photo) : Owner
-   delete(User $user, Photo $photo) : Owner

### 3.8 Routes

```php
// Public
Route::prefix('photos')->group(function () {
    Route::get('/', [PhotoController::class, 'index']);
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/featured', [PhotoController::class, 'featured']);
    Route::get('/recent', [PhotoController::class, 'recent']);
    Route::get('/popular', [PhotoController::class, 'popular']);
    Route::get('/{photo}', [PhotoController::class, 'show']);
    Route::get('/{photo}/similar', [PhotoController::class, 'similar']);
    Route::post('/{photo}/view', [PhotoController::class, 'incrementView']);
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}', [CategoryController::class, 'show']);
    Route::get('/{category}/photos', [CategoryController::class, 'photos']);
});

// Photographer (protected + middleware photographer)
Route::middleware(['auth:api', 'photographer'])->prefix('photographer/photos')->group(function () {
    Route::get('/', [PhotographerPhotoController::class, 'index']);
    Route::post('/', [PhotographerPhotoController::class, 'store']);
    Route::get('/{photo}', [PhotographerPhotoController::class, 'show']);
    Route::put('/{photo}', [PhotographerPhotoController::class, 'update']);
    Route::delete('/{photo}', [PhotographerPhotoController::class, 'destroy']);
});
```

---

## 🛒 PHASE 4 : PANIER & COMMANDES

**Durée estimée : 3-4 jours**

### 4.1 Modèles Eloquent

```bash
php artisan make:model Order
php artisan make:model OrderItem
```

**`Order` Model**

Relations :

-   belongsTo : user
-   hasMany : items (OrderItem)

Scopes :

-   pending(), completed(), failed(), refunded()

Méthodes :

-   markAsCompleted(string $paymentId)
-   markAsFailed()
-   static generateOrderNumber(): string - Format: `ORD-YYYYMMDD-ABC123`

**`OrderItem` Model**

Relations :

-   belongsTo : order, photo, photographer

Méthodes :

-   generateDownloadUrl(): string - URL signée valide 24h
-   incrementDownloadCount()
-   canDownload(): bool - Vérifier order completed et URL non expirée

### 4.2 CartController (Session/Redis)

```bash
php artisan make:controller Api/CartController
```

Stockage panier : Session ou Redis

Méthodes :

-   index() : GET /api/cart - Afficher panier
-   addItem() : POST /api/cart/items - Ajouter photo au panier

    -   Paramètres : photo_id, license_type (standard/extended)
    -   Calculer prix selon license_type

-   updateItem() : PUT /api/cart/items/{cartItem} - Modifier license_type
-   removeItem() : DELETE /api/cart/items/{cartItem}
-   clear() : DELETE /api/cart - Vider panier

Structure panier :

```json
{
    "items": [
        {
            "photo_id": "uuid",
            "license_type": "standard",
            "price": 5000
        }
    ],
    "subtotal": 5000,
    "total": 5000
}
```

### 4.3 OrderController

```bash
php artisan make:controller Api/OrderController
```

Méthodes :

-   index() : GET /api/orders - Mes commandes
-   store(CreateOrderRequest) : POST /api/orders - Créer commande

    -   Créer Order
    -   Créer OrderItems avec :
        -   Snapshots : photo_title, photo_preview, photographer_name
        -   Calcul commission (20%)
        -   commission_amount = price \* commission_rate
        -   photographer_amount = price - commission_amount
    -   Retourner Order avec items

-   show(Order $order) : GET /api/orders/{order}
-   processPayment(PayOrderRequest, Order $order) : POST /api/orders/{order}/payment
-   downloadInvoice(Order $order) : GET /api/orders/{order}/invoice

### 4.4 Form Requests

```bash
php artisan make:request Order/CreateOrderRequest
php artisan make:request Order/PayOrderRequest
```

**`CreateOrderRequest`**

Validation :

-   items : required, array, min:1
-   items.\*.photo_id : required, exists:photos,id
-   items.\*.license_type : required, in:standard,extended
-   subtotal : required, integer, min:0 (FCFA)
-   tax : nullable, integer, min:0 (FCFA)
-   discount : nullable, integer, min:0 (FCFA)
-   total : required, integer, min:0 (FCFA)
-   payment_method : required, in:mobile_money,card
-   billing_email : required, email
-   billing_first_name : required, string
-   billing_last_name : required, string
-   billing_phone : required, string, regex:/^\\+226\\s?\\d{2}\\s?\\d{2}\\s?\\d{2}\\s?\\d{2}$/

**`PayOrderRequest`**

Validation :

-   payment_method : required, in:mobile_money,card
-   payment_provider : nullable, in:ORANGE,MTN,MOOV,WAVE (si mobile_money)
-   phone : nullable, regex phone (si mobile_money)

Authorization :

```php
public function authorize(): bool
{
    return $this->user()->id === $this->route('order')->user_id;
}
```

### 4.5 API Resources

```bash
php artisan make:resource OrderResource
php artisan make:resource OrderItemResource
```

### 4.6 Routes

```php
Route::middleware('auth:api')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/items', [CartController::class, 'addItem']);
    Route::put('/items/{cartItem}', [CartController::class, 'updateItem']);
    Route::delete('/items/{cartItem}', [CartController::class, 'removeItem']);
    Route::delete('/', [CartController::class, 'clear']);
});

Route::middleware('auth:api')->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{order}', [OrderController::class, 'show']);
    Route::post('/{order}/payment', [OrderController::class, 'processPayment']);
    Route::get('/{order}/invoice', [OrderController::class, 'downloadInvoice']);
});
```

---

## 💳 PHASE 5 : PAIEMENTS CINETPAY

**Durée estimée : 4-6 jours**

### 5.1 Configuration CinetPay

**Fichier : `config/services.php`**

```php
'cinetpay' => [
    'api_url' => env('CINETPAY_API_URL', 'https://api-checkout.cinetpay.com/v2'),
    'site_id' => env('CINETPAY_SITE_ID'),
    'api_key' => env('CINETPAY_API_KEY'),
    'secret_key' => env('CINETPAY_SECRET_KEY'),
    'notify_url' => env('CINETPAY_NOTIFY_URL'),
    'return_url' => env('CINETPAY_RETURN_URL'),
],
```

**Fichier : `.env`**

```env
CINETPAY_API_URL=https://api-checkout.cinetpay.com/v2
CINETPAY_SITE_ID=your-site-id
CINETPAY_API_KEY=your-api-key
CINETPAY_SECRET_KEY=your-secret-key
CINETPAY_NOTIFY_URL=https://api.Pourier.com/webhooks/cinetpay
CINETPAY_RETURN_URL=https://Pourier.com/payment/return
```

### 5.2 PaymentService

```bash
php artisan make:service PaymentService
```

**Constantes :**

```php
private const COMMISSION_RATE = 0.20; // 20%
```

**Méthodes :**

**`processPayment(Order $order, string $paymentMethod, ?string $paymentProvider, ?string $phone): array`**

Process :

1. Initialiser paiement via CinetPay API :

```php
POST https://api-checkout.cinetpay.com/v2/payment
{
    "apikey": config('services.cinetpay.api_key'),
    "site_id": config('services.cinetpay.site_id'),
    "transaction_id": $order->order_number,
    "amount": $order->total, // en FCFA
    "currency": "XOF",
    "description": "Achat photos Pourier - Commande {order_number}",
    "notify_url": route('webhooks.cinetpay'),
    "return_url": config('app.frontend_url') . '/orders/' . $order->id,
    "channels": $this->getCinetPayChannels($paymentMethod, $paymentProvider),
    "metadata": {
        "order_id": $order->id,
        "user_id": $order->user_id
    },
    "customer_phone_number": $phone // Si fourni
}
```

2. Si succès (code 201) :

    - Mettre à jour order.cinetpay_transaction_id
    - Retourner : payment_url, payment_token

3. Si échec :
    - Marquer order comme failed
    - Retourner erreur

**`getCinetPayChannels(string $paymentMethod, ?string $provider): string`**

Mapping :

-   ORANGE → ORANGE_MONEY_BF
-   MTN → MTN_MONEY_BF
-   MOOV → MOOV_MONEY_BF
-   WAVE → WAVE_BF
-   card → CARD
-   Si null → ALL

**`checkPaymentStatus(Order $order): array`**

```php
POST https://api-checkout.cinetpay.com/v2/check
{
    "apikey": config('services.cinetpay.api_key'),
    "site_id": config('services.cinetpay.site_id'),
    "transaction_id": $order->order_number
}
```

Retourner : status, data

**`completeOrder(Order $order, string $transactionId): void`**

Process (DB transaction) :

1. Marquer order comme completed
2. Générer download_url pour chaque OrderItem :

    - URL signée Laravel (route named)
    - Expiration : 24 heures
    - item->update(['download_url' => $url, 'download_expires_at' => now()->addHours(24)])

3. Mettre à jour stats photos :

    - photo->incrementSales()

4. Enregistrer revenus photographes :

    - Appeler RevenueService::recordSales($order)

5. Notifier photographes :

    - NewSaleNotification (avec montant gagné)

6. Dispatcher jobs :
    - GenerateInvoicePdf::dispatch($order)
    - SendOrderConfirmationEmail::dispatch($order)

### 5.3 WebhookController

```bash
php artisan make:controller Api/WebhookController
```

**`handleCinetPayWebhook(Request $request)`**

Process :

1. Logger webhook : Log::info('CinetPay Webhook', $request->all())

2. Récupérer paramètres :

    - cpm_trans_id (token)
    - cpm_custom (transaction_id = order_number)
    - cpm_amount
    - cpm_result (status)
    - signature

3. Vérifier signature :

```php
$signature = $request->input('signature');
$expectedSignature = hash('sha256', $siteId . $transactionId . $apiKey);

if ($signature !== $expectedSignature) {
    Log::warning('CinetPay webhook signature mismatch');
    return response()->json(['error' => 'Invalid signature'], 400);
}
```

4. Trouver commande :

```php
$order = Order::where('order_number', $transactionId)->first();
if (!$order) {
    return response()->json(['error' => 'Order not found'], 404);
}
```

5. Traiter selon statut :

    - Si `cpm_result === '00'` ET `order->isPending()` :
        - Mettre à jour order : payment_status = 'completed', payment_id, paid_at
        - Appeler PaymentService::completeOrder($order, $token)
        - Log success
    - Si `cpm_result !== '00'` :
        - order->markAsFailed()
        - Log warning

6. Retourner : `{'status': 'success'}`

**`handleCinetPayReturn(Request $request, string $orderId)`**

Process :

1. Trouver order : Order::findOrFail($orderId)
2. Vérifier statut auprès CinetPay : PaymentService::checkPaymentStatus($order)
3. Si ACCEPTED : Rediriger vers frontend success
4. Sinon : Rediriger vers frontend failed

### 5.4 Routes Webhooks

```php
// Public (sans auth)
Route::post('/webhooks/cinetpay', [WebhookController::class, 'handleCinetPayWebhook'])
    ->name('webhooks.cinetpay');
Route::get('/webhooks/cinetpay/return/{order}', [WebhookController::class, 'handleCinetPayReturn']);
```

### 5.5 Testing Paiements

**Mode Test CinetPay :**

-   Utiliser credentials test
-   Numéros test : +226 00 00 00 00
-   Cartes test : 4111111111111111

---

## 💰 PHASE 6 : REVENUS & RETRAITS

**Durée estimée : 5-7 jours**

### 6.1 Modèle Revenue

```bash
php artisan make:model Revenue
```

**Champs :**

-   photographer_id (FK users)
-   month (date YYYY-MM-01)
-   total_sales (integer FCFA) - Montant total ventes
-   commission (integer FCFA) - Commission plateforme (20%)
-   net_revenue (integer FCFA) - Revenu net photographe (80%)
-   available_balance (integer FCFA) - Solde disponible pour retrait
-   pending_balance (integer FCFA) - Solde en période sécurité (< 30j)
-   withdrawn (integer FCFA) - Montant déjà retiré
-   sales_count (integer) - Nombre de ventes
-   photos_sold (integer) - Nombre de photos vendues

**Contrainte unique :** (photographer_id, month)

### 6.2 RevenueService

```bash
php artisan make:service RevenueService
```

**Constantes :**

```php
private const SECURITY_PERIOD_DAYS = 30;
private const COMMISSION_RATE = 0.20;
```

**Méthodes :**

**`recordSales(Order $order): void`**

Process :

```php
$month = Carbon::parse($order->paid_at)->startOfMonth();

foreach ($order->items as $item) {
    $revenue = Revenue::firstOrCreate([
        'photographer_id' => $item->photographer_id,
        'month' => $month,
    ], [/* defaults */]);

    $commission = (int) round($item->price * self::COMMISSION_RATE);
    $photographerAmount = $item->price - $commission;

    $revenue->increment('total_sales', $item->price);
    $revenue->increment('commission', $commission);
    $revenue->increment('net_revenue', $photographerAmount);
    $revenue->increment('pending_balance', $photographerAmount);
    $revenue->increment('sales_count');
    $revenue->increment('photos_sold');
}
```

**`calculateAvailableBalance(string $photographerId): int`**

```php
$securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

return (int) Revenue::where('photographer_id', $photographerId)
    ->where('month', '<=', $securityDate->startOfMonth())
    ->sum('pending_balance');
```

**`getPendingBalance(string $photographerId): int`**

```php
$securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

return (int) Revenue::where('photographer_id', $photographerId)
    ->where('month', '>', $securityDate->startOfMonth())
    ->sum('pending_balance');
```

**`processWithdrawal(string $photographerId, int $amount): void`**

Process (DB transaction avec lock) :

```php
$securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

$revenues = Revenue::where('photographer_id', $photographerId)
    ->where('month', '<=', $securityDate->startOfMonth())
    ->where('pending_balance', '>', 0)
    ->orderBy('month') // FIFO
    ->lockForUpdate()
    ->get();

$remainingAmount = $amount;

foreach ($revenues as $revenue) {
    if ($remainingAmount <= 0) break;

    $deduction = min($remainingAmount, $revenue->pending_balance);

    $revenue->decrement('pending_balance', $deduction);
    $revenue->decrement('available_balance', $deduction);
    $revenue->increment('withdrawn', $deduction);

    $remainingAmount -= $deduction;
}
```

**`getMonthlyRevenues(string $photographerId, int $months = 12): array`**

Retourne historique des 12 derniers mois.

**`getSummary(string $photographerId): array`**

```php
return [
    'available_balance' => $this->getAvailableBalance($photographerId),
    'pending_balance' => $this->getPendingBalance($photographerId),
    'total_withdrawn' => Revenue::where('photographer_id', $photographerId)->sum('withdrawn'),
    'total_revenue' => Revenue::where('photographer_id', $photographerId)->sum('net_revenue'),
    'total_sales' => Revenue::where('photographer_id', $photographerId)->sum('sales_count'),
];
```

### 6.3 Modèle Withdrawal

```bash
php artisan make:model Withdrawal
```

**Statuts :**

-   pending : En attente validation admin
-   approved : Approuvé par admin, en cours de traitement
-   rejected : Rejeté par admin
-   completed : Traité et payé

**Méthodes :**

-   approve(User $admin, ?string $notes)
-   complete(User $admin, string $transactionId, ?string $notes)
-   reject(User $admin, string $reason)

### 6.4 Controllers

```bash
php artisan make:controller Api/Photographer/RevenueController
php artisan make:controller Api/Photographer/WithdrawalController
php artisan make:controller Api/Admin/WithdrawalController
```

**`Photographer/RevenueController`**

Méthodes :

-   summary() : GET /api/photographer/revenue/summary

    -   RevenueService::getSummary(auth()->id())

-   monthly() : GET /api/photographer/revenue/monthly

    -   RevenueService::getMonthlyRevenues(auth()->id(), 12)

-   transactions() : GET /api/photographer/revenue/transactions

    -   Liste OrderItems du photographe

-   stats() : GET /api/photographer/revenue/stats
    -   Statistiques agrégées

**`Photographer/WithdrawalController`**

Méthodes :

-   index() : GET /api/photographer/withdrawals

    -   Mes demandes de retrait

-   store(CreateWithdrawalRequest) : POST /api/photographer/withdrawals

    -   Créer demande retrait
    -   Vérifier available_balance >= amount
    -   Montant min : 5000 FCFA
    -   Appeler RevenueService::processWithdrawal()

-   show(Withdrawal $withdrawal) : GET /api/photographer/withdrawals/{withdrawal}

**`Admin/WithdrawalController`**

Méthodes :

-   index() : GET /api/admin/withdrawals - Toutes les demandes
-   pending() : GET /api/admin/withdrawals/pending - En attente
-   approve(Withdrawal $withdrawal) : POST /api/admin/withdrawals/{withdrawal}/approve
-   reject(Withdrawal $withdrawal, RejectWithdrawalRequest) : POST /api/admin/withdrawals/{withdrawal}/reject
-   complete(Withdrawal $withdrawal, CompleteWithdrawalRequest) : POST /api/admin/withdrawals/{withdrawal}/complete
    -   Paramètres : transaction_id, notes
    -   Notification photographe : WithdrawalApprovedNotification

### 6.5 Form Requests

```bash
php artisan make:request Withdrawal/CreateWithdrawalRequest
```

**`CreateWithdrawalRequest`**

Validation :

-   amount : required, integer, min:5000, validation custom (vérifier available_balance)

```php
'amount' => [
    'required',
    'integer',
    'min:5000',
    function ($attribute, $value, $fail) {
        $revenueService = app(RevenueService::class);
        $availableBalance = $revenueService->getAvailableBalance($this->user()->id);

        if ($value > $availableBalance) {
            $fail('Le montant demandé dépasse votre solde disponible');
        }
    },
],
```

-   payment_method : required, in:mobile_money,bank_transfer
-   payment_details : required, array

**Si mobile_money :**

-   payment_details.provider : required, in:ORANGE,MTN,MOOV,WAVE
-   payment_details.phone : required, regex phone
-   payment_details.name : required, string

**Si bank_transfer :**

-   payment_details.bank_name : required, string
-   payment_details.account_number : required, string
-   payment_details.account_name : required, string
-   payment_details.iban : nullable, string

Authorization :

```php
public function authorize(): bool
{
    return $this->user()->isPhotographer();
}
```

### 6.6 Jobs Planifiés

```bash
php artisan make:job CalculateMonthlyRevenue
```

**`CalculateMonthlyRevenue`**

Process :

```php
$securityDate = Carbon::now()->subDays(30);

Revenue::where('month', '<=', $securityDate->startOfMonth())
    ->where('pending_balance', '>', 0)
    ->each(function ($revenue) {
        $revenue->update([
            'available_balance' => $revenue->available_balance + $revenue->pending_balance,
            'pending_balance' => 0,
        ]);
    });
```

**Scheduler : `routes/console.php`**

```php
Schedule::command('revenues:calculate')->daily();
```

**Commande Artisan :**

```bash
php artisan make:command CalculateRevenuesCommand
```

```php
protected $signature = 'revenues:calculate';

public function handle(): int
{
    CalculateMonthlyRevenue::dispatch();
    $this->info('Job de calcul des revenus dispatché avec succès');
    return Command::SUCCESS;
}
```

### 6.7 Routes

```php
// Photographer
Route::middleware(['auth:api', 'photographer'])->prefix('photographer')->group(function () {
    Route::prefix('revenue')->group(function () {
        Route::get('/summary', [RevenueController::class, 'summary']);
        Route::get('/monthly', [RevenueController::class, 'monthly']);
        Route::get('/transactions', [RevenueController::class, 'transactions']);
        Route::get('/stats', [RevenueController::class, 'stats']);
    });

    Route::prefix('withdrawals')->group(function () {
        Route::get('/', [PhotographerWithdrawalController::class, 'index']);
        Route::post('/', [PhotographerWithdrawalController::class, 'store']);
        Route::get('/{withdrawal}', [PhotographerWithdrawalController::class, 'show']);
    });
});

// Admin
Route::middleware(['auth:api', 'admin'])->prefix('admin/withdrawals')->group(function () {
    Route::get('/', [AdminWithdrawalController::class, 'index']);
    Route::get('/pending', [AdminWithdrawalController::class, 'pending']);
    Route::post('/{withdrawal}/approve', [AdminWithdrawalController::class, 'approve']);
    Route::post('/{withdrawal}/reject', [AdminWithdrawalController::class, 'reject']);
    Route::post('/{withdrawal}/complete', [AdminWithdrawalController::class, 'complete']);
});
```

---

## 🔔 PHASE 7 : NOTIFICATIONS

**Durée estimée : 2-3 jours**

### 7.1 Modèle Notification

**Déjà créé en Phase 1**

Scopes :

-   unread(), read()

Méthodes :

-   markAsRead()

### 7.2 NotificationService

```bash
php artisan make:service NotificationService
```

**Méthodes :**

```php
public function createNotification(
    User $user,
    string $type,
    string $title,
    string $message,
    ?array $data = null
): Notification {
    return Notification::create([
        'user_id' => $user->id,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'data' => $data,
    ]);
}

public function markAsRead(Notification $notification): void
{
    $notification->markAsRead();
}

public function markAllAsRead(User $user): void
{
    Notification::where('user_id', $user->id)
        ->where('is_read', false)
        ->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
}

public function getUnreadCount(User $user): int
{
    return Notification::where('user_id', $user->id)
        ->where('is_read', false)
        ->count();
}

public function deleteOldNotifications(int $days = 90): int
{
    return Notification::where('created_at', '<', now()->subDays($days))
        ->delete();
}
```

### 7.3 Notifications Laravel

```bash
php artisan make:notification PhotoApprovedNotification
php artisan make:notification PhotoRejectedNotification
php artisan make:notification NewSaleNotification
php artisan make:notification WithdrawalApprovedNotification
```

**Tous via :** `['database', 'mail']`

**`PhotoApprovedNotification`**

```php
public function toDatabase($notifiable): array
{
    return [
        'type' => 'photo_approved',
        'title' => 'Photo approuvée',
        'message' => "Votre photo \"{$this->photo->title}\" a été approuvée et est maintenant visible publiquement.",
        'data' => [
            'photo_id' => $this->photo->id,
            'photo_title' => $this->photo->title,
            'photo_thumbnail' => $this->photo->thumbnail_url,
        ],
    ];
}

public function toMail($notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('Photo approuvée - Pourier')
        ->greeting('Bonjour ' . $notifiable->first_name . ',')
        ->line("Votre photo \"{$this->photo->title}\" a été approuvée!")
        ->line('Elle est maintenant visible par tous les utilisateurs de la plateforme.')
        ->action('Voir ma photo', url('/photographer/photos/' . $this->photo->id))
        ->line('Merci d\'utiliser Pourier!');
}
```

**`PhotoRejectedNotification`**

Inclure raison du rejet.

**`NewSaleNotification`**

```php
$totalEarned = collect($this->items)->sum('photographer_amount');
$photoCount = count($this->items);

return [
    'type' => 'new_sale',
    'title' => 'Nouvelle vente',
    'message' => "Vous avez vendu {$photoCount} photo(s) pour un montant de {$totalEarned} FCFA",
    'data' => [
        'order_id' => $this->order->id,
        'total_earned' => $totalEarned,
    ],
];
```

**`WithdrawalApprovedNotification`**

Message : "Votre demande de retrait de {amount} FCFA a été approuvée et sera traitée sous 24-48h."

### 7.4 NotificationController

```bash
php artisan make:controller Api/User/NotificationController
```

Méthodes :

-   index() : GET /api/notifications - Mes notifications paginées
-   unreadCount() : GET /api/notifications/unread/count
-   markAsRead(Notification $notification) : PATCH /api/notifications/{notification}/read
-   markAllAsRead() : PATCH /api/notifications/read-all
-   destroy(Notification $notification) : DELETE /api/notifications/{notification}

### 7.5 Routes

```php
Route::middleware('auth:api')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread/count', [NotificationController::class, 'unreadCount']);
    Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{notification}', [NotificationController::class, 'destroy']);
});
```

### 7.6 Utilisation

**Envoyer notification :**

```php
// Dans PhotoService::approvePhoto()
$photo->photographer->notify(
    new PhotoApprovedNotification($photo)
);

// Dans PaymentService::completeOrder()
foreach ($photographerSales as $sale) {
    $sale['photographer']->notify(
        new NewSaleNotification($order, $sale['items'])
    );
}
```

---

## 📧 PHASE 8 : EMAILS

**Durée estimée : 2-3 jours**

### 8.1 Mailables

```bash
php artisan make:mail WelcomeMail
php artisan make:mail OrderConfirmationMail
php artisan make:mail MonthlySummaryMail
```

**`WelcomeMail`**

```php
public function __construct(public User $user) {}

public function envelope(): Envelope
{
    return new Envelope(subject: 'Bienvenue sur Pourier');
}

public function content(): Content
{
    return new Content(view: 'emails.welcome');
}
```

**`OrderConfirmationMail`**

```php
public function __construct(public Order $order) {}

public function content(): Content
{
    return new Content(
        view: 'emails.order-confirmation',
        with: ['order' => $this->order->load('items.photo')],
    );
}
```

**`MonthlySummaryMail`**

Résumé mensuel pour photographes :

-   Revenus du mois
-   Nombre de ventes
-   Photos les plus vendues
-   Solde disponible

### 8.2 Vues Blade

**`resources/views/emails/order-confirmation.blade.php`**

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9fafb; }
        .button { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Merci pour votre commande !</h1>
        </div>

        <div class="content">
            <p>Bonjour {{ $order->billing_first_name }},</p>
            <p>Votre commande a été confirmée et vos photos sont prêtes à être téléchargées.</p>

            <div class="order-info">
                <h3>Détails de la commande</h3>
                <p><strong>Numéro :</strong> {{ $order->order_number }}</p>
                <p><strong>Date :</strong> {{ $order->paid_at->format('d/m/Y H:i') }}</p>

                <h4>Photos achetées :</h4>
                @foreach($order->items as $item)
                    <div class="item">
                        <strong>{{ $item->photo_title }}</strong><br>
                        Licence: {{ ucfirst($item->license_type) }}<br>
                        Prix: {{ number_format($item->price, 0, ',', ' ') }} FCFA
                    </div>
                @endforeach

                <div class="total">
                    Total: {{ number_format($order->total, 0, ',', ' ') }} FCFA
                </div>
            </div>

            <center>
                <a href="{{ url('/orders/' . $order->id) }}" class="button">
                    Télécharger mes photos
                </a>
            </center>

            <p><small>Ce lien de téléchargement est valide pendant 24 heures.</small></p>
        </div>
    </div>
</body>
</html>
```

**`resources/views/emails/welcome.blade.php`**

Message de bienvenue avec :

-   Instructions pour compléter profil
-   Si photographe : étapes validation profil
-   Liens utiles

### 8.3 Jobs Email

```bash
php artisan make:job SendOrderConfirmationEmail
```

**`SendOrderConfirmationEmail`**

```php
public function __construct(private Order $order) {}

public function handle(): void
{
    try {
        Mail::to($this->order->billing_email)
            ->send(new OrderConfirmationMail($this->order));
    } catch (\Exception $e) {
        Log::error('Erreur envoi email confirmation: ' . $e->getMessage());
    }
}
```

**Dispatcher depuis PaymentService::completeOrder() :**

```php
SendOrderConfirmationEmail::dispatch($order);
```

### 8.4 Configuration Mail

**Fichier : `.env`**

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@Pourier.com
MAIL_FROM_NAME="Pourier"
```

### 8.5 Commande Test

```bash
php artisan tinker
>>> Mail::to('test@example.com')->send(new App\Mail\WelcomeMail(App\Models\User::first()));
```

---

## 👮 PHASE 9 : MODÉRATION & ADMIN

**Durée estimée : 4-5 jours**

### 9.1 Middlewares

```bash
php artisan make:middleware CheckRole
php artisan make:middleware CheckPhotographer
php artisan make:middleware CheckAdmin
php artisan make:middleware TrackPhotoView
```

**`CheckRole`**

```php
public function handle(Request $request, Closure $next, string $role): Response
{
    if (!auth()->check()) {
        return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
    }

    if (auth()->user()->account_type !== $role) {
        return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
    }

    return $next($request);
}
```

**`CheckPhotographer`**

```php
public function handle(Request $request, Closure $next): Response
{
    if (!auth()->check() || !auth()->user()->isPhotographer()) {
        return response()->json(['success' => false, 'message' => 'Accès réservé aux photographes'], 403);
    }

    // Vérifier profil approuvé
    $profile = auth()->user()->photographerProfile;
    if (!$profile || !$profile->isApproved()) {
        return response()->json(['success' => false, 'message' => 'Votre profil photographe doit être validé'], 403);
    }

    return $next($request);
}
```

**`CheckAdmin`**

```php
public function handle(Request $request, Closure $next): Response
{
    if (!auth()->check() || !auth()->user()->isAdmin()) {
        return response()->json(['success' => false, 'message' => 'Accès réservé aux administrateurs'], 403);
    }

    return $next($request);
}
```

**`TrackPhotoView`**

```php
public function handle(Request $request, Closure $next): Response
{
    $response = $next($request);

    // Incrémenter vues uniquement si visite unique (cache 24h par IP)
    if ($request->route('photo')) {
        $photo = $request->route('photo');
        $cacheKey = 'photo_view_' . $photo->id . '_' . $request->ip();

        if (!Cache::has($cacheKey)) {
            $photo->incrementViews();
            Cache::put($cacheKey, true, now()->addHours(24));
        }
    }

    return $response;
}
```

**Enregistrement : `bootstrap/app.php`**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'photographer' => \App\Http\Middleware\CheckPhotographer::class,
        'admin' => \App\Http\Middleware\CheckAdmin::class,
        'track.view' => \App\Http\Middleware\TrackPhotoView::class,
    ]);
})
```

### 9.2 PhotoService

```bash
php artisan make:service PhotoService
```

**Méthodes :**

```php
public function approvePhoto(Photo $photo, User $moderator): Photo
{
    DB::transaction(function () use ($photo, $moderator) {
        $photo->approve($moderator);

        // Mettre à jour compteur catégorie
        $photo->category->updatePhotoCount();

        // Notifier photographe
        $photo->photographer->notify(new PhotoApprovedNotification($photo));
    });

    return $photo->fresh();
}

public function rejectPhoto(Photo $photo, User $moderator, string $reason): Photo
{
    DB::transaction(function () use ($photo, $moderator, $reason) {
        $photo->reject($moderator, $reason);

        // Notifier photographe
        $photo->photographer->notify(new PhotoRejectedNotification($photo, $reason));
    });

    return $photo->fresh();
}

public function featurePhoto(Photo $photo, ?\DateTime $untilDate = null): Photo
{
    $photo->update([
        'featured' => true,
        'featured_until' => $untilDate,
    ]);

    return $photo;
}

public function getSimilarPhotos(Photo $photo, int $limit = 6): array
{
    return Photo::query()
        ->approved()
        ->public()
        ->where('id', '!=', $photo->id)
        ->where(function ($query) use ($photo) {
            $query->where('category_id', $photo->category_id)
                ->orWhere('photographer_id', $photo->photographer_id);
        })
        ->inRandomOrder()
        ->limit($limit)
        ->get()
        ->toArray();
}
```

### 9.3 Controllers Admin

```bash
php artisan make:controller Api/Admin/DashboardController
php artisan make:controller Api/Admin/UserController
php artisan make:controller Api/Admin/PhotoModerationController
php artisan make:controller Api/Admin/PhotographerController
php artisan make:controller Api/Admin/AnalyticsController
```

**`PhotoModerationController`**

Méthodes :

-   pending() : GET /api/admin/photos/pending

    -   Liste photos en attente modération

-   approve(Photo $photo) : POST /api/admin/photos/{photo}/approve

    -   Appeler PhotoService::approvePhoto()

-   reject(Photo $photo, RejectPhotoRequest) : POST /api/admin/photos/{photo}/reject

    -   Paramètres : reason (required)
    -   Appeler PhotoService::rejectPhoto()

-   feature(Photo $photo, FeaturePhotoRequest) : PATCH /api/admin/photos/{photo}/feature
    -   Paramètres : featured (boolean), featured_until (nullable, date)
    -   Appeler PhotoService::featurePhoto()

**`PhotographerController` (Admin)**

Méthodes :

-   pending() : GET /api/admin/photographers/pending

    -   PhotographerProfile::pending()->with('user')->get()

-   approve(PhotographerProfile $photographer) : POST /api/admin/photographers/{photographer}/approve

    -   $photographer->approve(auth()->user())
    -   Notification photographe

-   reject(PhotographerProfile $photographer, RejectPhotographerRequest) : POST /api/admin/photographers/{photographer}/reject
    -   $photographer->reject()
    -   Notification photographe avec raison

**`UserController` (Admin)**

Méthodes :

-   index() : GET /api/admin/users - Liste utilisateurs paginée
-   show(User $user) : GET /api/admin/users/{user}
-   update(User $user, UpdateUserRequest) : PUT /api/admin/users/{user}
-   destroy(User $user) : DELETE /api/admin/users/{user} - Soft delete
-   suspend(User $user) : PATCH /api/admin/users/{user}/suspend
    -   $user->update(['is_active' => false])
-   activate(User $user) : PATCH /api/admin/users/{user}/activate
    -   $user->update(['is_active' => true])

**`DashboardController` (Admin)**

```php
public function index(): JsonResponse
{
    $stats = [
        'users' => [
            'total' => User::count(),
            'buyers' => User::buyers()->count(),
            'photographers' => User::photographers()->count(),
            'active_today' => User::where('last_login', '>=', now()->subDay())->count(),
        ],
        'photos' => [
            'total' => Photo::count(),
            'pending' => Photo::pending()->count(),
            'approved' => Photo::approved()->count(),
            'featured' => Photo::featured()->count(),
        ],
        'orders' => [
            'total' => Order::count(),
            'today' => Order::whereDate('created_at', today())->count(),
            'pending' => Order::pending()->count(),
            'completed' => Order::completed()->count(),
            'total_revenue' => Order::completed()->sum('total'),
        ],
        'withdrawals' => [
            'pending' => Withdrawal::pending()->count(),
            'pending_amount' => Withdrawal::pending()->sum('amount'),
        ],
    ];

    return response()->json(['success' => true, 'data' => $stats]);
}
```

**`AnalyticsController` (Admin)**

Méthodes :

-   overview() : Statistiques globales
-   users() : Analytics utilisateurs (inscriptions, activité)
-   photos() : Analytics photos (uploads, approvals, rejections)
-   revenue() : Analytics revenus (par jour/mois, commissions)
-   photographers() : Top photographes (par ventes, revenus)

### 9.4 Routes Admin

```php
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // Users
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/{user}', [AdminUserController::class, 'show']);
        Route::put('/{user}', [AdminUserController::class, 'update']);
        Route::delete('/{user}', [AdminUserController::class, 'destroy']);
        Route::patch('/{user}/suspend', [AdminUserController::class, 'suspend']);
        Route::patch('/{user}/activate', [AdminUserController::class, 'activate']);
    });

    // Photo Moderation
    Route::prefix('photos')->group(function () {
        Route::get('/pending', [PhotoModerationController::class, 'pending']);
        Route::post('/{photo}/approve', [PhotoModerationController::class, 'approve']);
        Route::post('/{photo}/reject', [PhotoModerationController::class, 'reject']);
        Route::patch('/{photo}/feature', [PhotoModerationController::class, 'feature']);
    });

    // Photographer Validation
    Route::prefix('photographers')->group(function () {
        Route::get('/pending', [AdminPhotographerController::class, 'pending']);
        Route::post('/{photographer}/approve', [AdminPhotographerController::class, 'approve']);
        Route::post('/{photographer}/reject', [AdminPhotographerController::class, 'reject']);
    });

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/overview', [AdminAnalyticsController::class, 'overview']);
        Route::get('/users', [AdminAnalyticsController::class, 'users']);
        Route::get('/photos', [AdminAnalyticsController::class, 'photos']);
        Route::get('/revenue', [AdminAnalyticsController::class, 'revenue']);
        Route::get('/photographers', [AdminAnalyticsController::class, 'photographers']);
    });
});
```

---

## 👤 PHASE 10 : PROFILS & INTERACTIONS

**Durée estimée : 3-4 jours**

### 10.1 ProfileController

```bash
php artisan make:controller Api/User/ProfileController
```

Méthodes :

-   show() : GET /api/user/profile
-   update(UpdateProfileRequest) : PUT /api/user/profile

    -   Champs : first_name, last_name, bio, phone
    -   Si photographer : display_name, location, website, instagram, specialties[]

-   updateAvatar() : POST /api/user/avatar
    -   Upload image
    -   Validation : image, max:5MB
    -   StorageService::storeAvatar()
    -   Mise à jour user.avatar_url
    -   Suppression ancien avatar

### 10.2 FavoriteController

```bash
php artisan make:controller Api/User/FavoriteController
```

Méthodes :

-   index() : GET /api/favorites

    -   auth()->user()->favorites()->with('photographer')->paginate()

-   store(StoreFavoriteRequest) : POST /api/favorites

    -   Paramètres : photo_id
    -   Toggle favorite :

    ```php
    $user->favorites()->toggle($request->photo_id);
    Photo::find($request->photo_id)->increment('favorites_count');
    ```

-   destroy(Photo $photo) : DELETE /api/favorites/{photo}
    -   auth()->user()->favorites()->detach($photo)
    -   $photo->decrement('favorites_count')

### 10.3 Follow Photographers

**Routes :**

-   POST /api/photographers/{photographer}/follow
-   DELETE /api/photographers/{photographer}/unfollow

**Méthodes (dans PhotographerController public) :**

```php
public function follow(User $photographer): JsonResponse
{
    if (!$photographer->isPhotographer()) {
        return response()->json(['success' => false, 'message' => 'Utilisateur non photographe'], 400);
    }

    auth()->user()->following()->attach($photographer);
    $photographer->photographerProfile->increment('followers_count');

    // Optionnel: Notification
    $photographer->notify(new NewFollowerNotification(auth()->user()));

    return response()->json(['success' => true, 'message' => 'Photographe suivi avec succès']);
}

public function unfollow(User $photographer): JsonResponse
{
    auth()->user()->following()->detach($photographer);
    $photographer->photographerProfile->decrement('followers_count');

    return response()->json(['success' => true, 'message' => 'Photographe retiré des suivis']);
}
```

### 10.4 DownloadController

```bash
php artisan make:controller Api/DownloadController
```

**Méthode :**

```php
public function download(OrderItem $orderItem)
{
    // Vérifier autorisation
    if ($orderItem->order->user_id !== auth()->id()) {
        return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
    }

    // Vérifier order completed
    if (!$orderItem->order->isCompleted()) {
        return response()->json(['success' => false, 'message' => 'Commande non complétée'], 400);
    }

    // Vérifier expiration download_url
    if ($orderItem->download_expires_at && $orderItem->download_expires_at->isPast()) {
        return response()->json(['success' => false, 'message' => 'Lien de téléchargement expiré'], 410);
    }

    // Incrémenter download_count
    $orderItem->incrementDownloadCount();

    // Générer URL S3 signée temporaire (5 minutes)
    $photo = $orderItem->photo;
    $originalPath = StorageService::getOriginalPath($photo->original_url);
    $downloadUrl = Storage::disk('s3')->temporaryUrl($originalPath, now()->addMinutes(5));

    // Rediriger vers S3
    return redirect($downloadUrl);
}
```

**Route (signed) :**

```php
Route::get('/downloads/{orderItem}', [DownloadController::class, 'download'])
    ->name('downloads.photo')
    ->middleware('auth:api');
```

**Génération lien depuis OrderItem :**

```php
public function generateDownloadUrl(): string
{
    $expires = now()->addHours(24);

    $this->update(['download_expires_at' => $expires]);

    return \URL::temporarySignedRoute(
        'downloads.photo',
        $expires,
        ['orderItem' => $this->id]
    );
}
```

### 10.5 Photographer Dashboard

```bash
php artisan make:controller Api/Photographer/DashboardController
php artisan make:controller Api/Photographer/AnalyticsController
```

**`DashboardController`**

```php
public function index(): JsonResponse
{
    $photographer = auth()->user();
    $profile = $photographer->photographerProfile;

    $stats = [
        'photos' => [
            'total' => $photographer->photos()->count(),
            'approved' => $photographer->photos()->approved()->count(),
            'pending' => $photographer->photos()->pending()->count(),
        ],
        'revenue' => app(RevenueService::class)->getSummary($photographer->id),
        'sales' => [
            'total' => OrderItem::where('photographer_id', $photographer->id)->count(),
            'this_month' => OrderItem::where('photographer_id', $photographer->id)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ],
        'followers' => $profile->followers_count,
        'views_total' => $photographer->photos()->sum('views_count'),
    ];

    return response()->json(['success' => true, 'data' => $stats]);
}
```

**`AnalyticsController` (Photographer)**

Méthodes :

-   overview() : Vue d'ensemble 30 derniers jours
-   photos() : Analytics par photo (vues, ventes, revenus)
-   sales() : Graphique ventes par jour/mois
-   revenue() : Évolution revenus mensuels

### 10.6 Routes

```php
// User Profile
Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/avatar', [ProfileController::class, 'updateAvatar']);
});

// Favorites
Route::middleware('auth:api')->prefix('favorites')->group(function () {
    Route::get('/', [FavoriteController::class, 'index']);
    Route::post('/', [FavoriteController::class, 'store']);
    Route::delete('/{photo}', [FavoriteController::class, 'destroy']);
});

// Follow
Route::middleware('auth:api')->group(function () {
    Route::post('/photographers/{photographer}/follow', [PhotographerController::class, 'follow']);
    Route::delete('/photographers/{photographer}/unfollow', [PhotographerController::class, 'unfollow']);
});

// Photographer Dashboard
Route::middleware(['auth:api', 'photographer'])->prefix('photographer')->group(function () {
    Route::get('/dashboard', [PhotographerDashboardController::class, 'index']);

    Route::prefix('analytics')->group(function () {
        Route::get('/overview', [PhotographerAnalyticsController::class, 'overview']);
        Route::get('/photos', [PhotographerAnalyticsController::class, 'photos']);
        Route::get('/sales', [PhotographerAnalyticsController::class, 'sales']);
        Route::get('/revenue', [PhotographerAnalyticsController::class, 'revenue']);
    });
});
```

---

## 📄 PHASE 11 : FACTURES & DOCUMENTS

**Durée estimée : 2-3 jours**

### 11.1 InvoiceService

```bash
php artisan make:service InvoiceService
```

```php
public function __construct(private StorageService $storageService) {}

public function generateInvoice(Order $order): string
{
    $pdf = Pdf::loadView('invoices.order', [
        'order' => $order->load('items.photo', 'user'),
    ]);

    $content = $pdf->output();

    $invoiceUrl = $this->storageService->storeInvoice(
        $content,
        $order->order_number
    );

    $order->update(['invoice_url' => $invoiceUrl]);

    return $invoiceUrl;
}
```

### 11.2 GenerateInvoicePdf Job

```bash
php artisan make:job GenerateInvoicePdf
```

```php
public function __construct(private Order $order) {}

public function handle(InvoiceService $invoiceService): void
{
    try {
        $invoiceService->generateInvoice($this->order);
    } catch (\Exception $e) {
        Log::error('Erreur génération facture: ' . $e->getMessage());
        throw $e;
    }
}
```

**Dispatcher depuis PaymentService::completeOrder() :**

```php
GenerateInvoicePdf::dispatch($order);
```

### 11.3 Vue Blade Invoice

**`resources/views/invoices/order.blade.php`**

Template PDF avec :

-   Logo Pourier
-   Informations société
-   Détails commande : order*number, date, billing*\*
-   Table items : photo, licence, prix
-   Total TTC
-   Footer avec mentions légales

### 11.4 Route Download Invoice

```php
public function downloadInvoice(Order $order)
{
    $this->authorize('view', $order);

    if (!$order->invoice_url) {
        return response()->json(['success' => false, 'message' => 'Facture non disponible'], 404);
    }

    return redirect($order->invoice_url);
}
```

---

## 🔍 PHASE 12 : RECHERCHE & FILTRES

**Durée estimée : 2-3 jours**

### 12.1 Full-Text Search PostgreSQL

**Migration index GIN :**

```php
DB::statement('CREATE INDEX photos_fulltext_idx ON photos USING GIN (to_tsvector(\'english\', title || \' \' || COALESCE(description, \'\')))');
```

### 12.2 Scope Search (Photo Model)

```php
public function scopeSearch($query, $searchTerm)
{
    if (empty($searchTerm)) {
        return $query;
    }

    return $query->where(function ($q) use ($searchTerm) {
        $q->where('title', 'ILIKE', "%{$searchTerm}%")
            ->orWhere('description', 'ILIKE', "%{$searchTerm}%")
            ->orWhereRaw('tags::text ILIKE ?', ["%{$searchTerm}%"]);
    });
}
```

### 12.3 Filtres Avancés (déjà implémentés)

-   filterByCategory($ids)
-   filterByPhotographer($id)
-   filterByPrice($min, $max)
-   filterByOrientation($orientation)
-   sortBy($sortBy)

### 12.4 SearchController (déjà fait en Phase 3)

Combinaison tous les filtres.

### 12.5 Optimisations

**Index PostgreSQL :**

```sql
CREATE INDEX photos_category_id_idx ON photos(category_id);
CREATE INDEX photos_photographer_id_idx ON photos(photographer_id);
CREATE INDEX photos_price_standard_idx ON photos(price_standard);
CREATE INDEX photos_status_is_public_idx ON photos(status, is_public);
CREATE INDEX photos_views_count_idx ON photos(views_count DESC);
CREATE INDEX photos_created_at_idx ON photos(created_at DESC);
```

**Eager Loading :**

```php
Photo::with(['photographer.photographerProfile', 'category'])->get();
```

---

## ⚙️ PHASE 13 : COMMANDES ARTISAN & SCHEDULER

**Durée estimée : 2 jours**

### 13.1 Commandes Artisan

```bash
php artisan make:command CalculateRevenuesCommand
php artisan make:command CleanExpiredDownloadsCommand
php artisan make:command SendMonthlySummariesCommand
php artisan make:command CleanOldNotificationsCommand
```

**`CleanExpiredDownloadsCommand`**

```php
protected $signature = 'downloads:clean-expired';
protected $description = 'Nettoyer les URLs de téléchargement expirées';

public function handle(): int
{
    $count = OrderItem::whereNotNull('download_expires_at')
        ->where('download_expires_at', '<', now())
        ->update([
            'download_url' => null,
            'download_expires_at' => null,
        ]);

    $this->info("{$count} téléchargement(s) expiré(s) nettoyé(s)");
    return Command::SUCCESS;
}
```

**`SendMonthlySummariesCommand`**

```php
public function __construct(private RevenueService $revenueService)
{
    parent::__construct();
}

public function handle(): int
{
    $photographers = User::photographers()
        ->whereHas('photographerProfile', fn($q) => $q->approved())
        ->get();

    $count = 0;
    foreach ($photographers as $photographer) {
        $summary = $this->revenueService->getSummary($photographer->id);
        Mail::to($photographer->email)->send(new MonthlySummaryMail($photographer, $summary));
        $count++;
    }

    $this->info("{$count} résumé(s) envoyé(s)");
    return Command::SUCCESS;
}
```

**`CleanOldNotificationsCommand`**

```php
protected $signature = 'notifications:clean-old {--days=90}';

public function handle(NotificationService $notificationService): int
{
    $days = $this->option('days');
    $count = $notificationService->deleteOldNotifications($days);
    $this->info("{$count} notification(s) supprimée(s)");
    return Command::SUCCESS;
}
```

### 13.2 Scheduler

**Fichier : `routes/console.php`**

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('revenues:calculate')->daily();
Schedule::command('downloads:clean-expired')->hourly();
Schedule::command('summaries:send-monthly')->monthlyOn(1, '08:00');
Schedule::command('notifications:clean-old')->weekly();
```

### 13.3 Cron Job (Production)

```bash
* * * * * cd /var/www/Pourier && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 PHASE 14 : TESTS

**Durée estimée : 3-5 jours**

### 14.1 Configuration Tests

**`phpunit.xml`**

```xml
<env name="DB_CONNECTION" value="pgsql"/>
<env name="DB_DATABASE" value="Pourier_test"/>
```

**Créer base test :**

```bash
createdb Pourier_test
```

### 14.2 Factories

```bash
php artisan make:factory UserFactory
php artisan make:factory PhotoFactory
php artisan make:factory OrderFactory
php artisan make:factory CategoryFactory
```

**`UserFactory`**

```php
public function definition(): array
{
    return [
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'account_type' => 'buyer',
        'is_active' => true,
    ];
}

public function photographer(): static
{
    return $this->state(fn (array $attributes) => [
        'account_type' => 'photographer',
    ])->afterCreating(function (User $user) {
        PhotographerProfile::factory()->create(['user_id' => $user->id]);
    });
}

public function admin(): static
{
    return $this->state(['account_type' => 'admin']);
}
```

**`PhotoFactory`**

```php
public function definition(): array
{
    return [
        'photographer_id' => User::factory()->photographer(),
        'category_id' => Category::factory(),
        'title' => fake()->sentence(),
        'description' => fake()->paragraph(),
        'tags' => ['tag1', 'tag2', 'tag3'],
        'original_url' => 'https://s3.amazonaws.com/originals/test.jpg',
        'preview_url' => 'https://s3.amazonaws.com/previews/test.jpg',
        'thumbnail_url' => 'https://s3.amazonaws.com/thumbnails/test.jpg',
        'width' => 1920,
        'height' => 1080,
        'file_size' => 5000000,
        'format' => 'jpg',
        'price_standard' => 5000,
        'price_extended' => 15000,
        'status' => 'pending',
        'is_public' => false,
    ];
}

public function approved(): static
{
    return $this->state([
        'status' => 'approved',
        'is_public' => true,
        'moderated_at' => now(),
    ]);
}

public function featured(): static
{
    return $this->state(['featured' => true]);
}
```

### 14.3 Tests Feature

```bash
php artisan make:test Auth/AuthenticationTest
php artisan make:test PhotoTest
php artisan make:test OrderTest
php artisan make:test WithdrawalTest
php artisan make:test ModerationTest
```

**`AuthenticationTest`**

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'account_type' => 'buyer',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['user', 'token']]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }
}
```

**`PhotoTest`**

```php
public function test_photographer_can_upload_photo(): void
{
    Storage::fake('s3');

    $photographer = User::factory()->photographer()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($photographer, 'api')
        ->postJson('/api/photographer/photos', [
            'photos' => [
                UploadedFile::fake()->image('photo.jpg', 1920, 1080)->size(5000),
            ],
            'title' => 'Test Photo',
            'description' => 'This is a test photo',
            'category_id' => $category->id,
            'tags' => 'test,photo,sample',
            'price_standard' => 5000,
            'price_extended' => 15000,
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('photos', ['title' => 'Test Photo']);
}

public function test_public_can_view_approved_photos(): void
{
    $photo = Photo::factory()->approved()->create();

    $response = $this->getJson("/api/photos/{$photo->id}");

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => ['photo' => ['id' => $photo->id]]]);
}

public function test_public_cannot_view_pending_photos(): void
{
    $photo = Photo::factory()->create(['status' => 'pending']);

    $response = $this->getJson("/api/photos/{$photo->id}");

    $response->assertStatus(404);
}
```

**`OrderTest`**

```php
public function test_user_can_create_order(): void
{
    $user = User::factory()->create();
    $photo = Photo::factory()->approved()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/orders', [
            'items' => [
                ['photo_id' => $photo->id, 'license_type' => 'standard'],
            ],
            'subtotal' => $photo->price_standard,
            'total' => $photo->price_standard,
            'payment_method' => 'mobile_money',
            'billing_email' => $user->email,
            'billing_first_name' => $user->first_name,
            'billing_last_name' => $user->last_name,
            'billing_phone' => '+226 70 12 34 56',
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
}
```

### 14.4 Tests Unit

```bash
php artisan make:test Unit/RevenueServiceTest --unit
php artisan make:test Unit/ImageProcessingServiceTest --unit
```

**`RevenueServiceTest`**

```php
public function test_calculates_available_balance_correctly(): void
{
    $photographer = User::factory()->photographer()->create();

    // Revenu il y a 35 jours (disponible)
    Revenue::factory()->create([
        'photographer_id' => $photographer->id,
        'month' => now()->subDays(35)->startOfMonth(),
        'pending_balance' => 50000,
    ]);

    // Revenu il y a 15 jours (non disponible)
    Revenue::factory()->create([
        'photographer_id' => $photographer->id,
        'month' => now()->subDays(15)->startOfMonth(),
        'pending_balance' => 30000,
    ]);

    $service = app(RevenueService::class);
    $availableBalance = $service->getAvailableBalance($photographer->id);

    $this->assertEquals(50000, $availableBalance);
}
```

### 14.5 Exécution Tests

```bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter AuthenticationTest

# Avec coverage
php artisan test --coverage
```

---

## 🚀 PHASE 15 : DÉPLOIEMENT & PRODUCTION

**Durée estimée : 3-4 jours**

### 15.1 Docker

**`Dockerfile`**

```dockerfile
FROM php:8.3-fpm

# Arguments
ARG user=laravel
ARG uid=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libpq-dev \
    zip unzip supervisor

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www

# Copy application
COPY --chown=$user:$user . /var/www

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Generate optimizations
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

USER $user

EXPOSE 9000
CMD ["php-fpm"]
```

**`docker-compose.yml`**

```yaml
version: "3.8"

services:
    app:
        build:
            context: .
            dockerfile: Dockerfile
        container_name: Pourier-app
        restart: unless-stopped
        working_dir: /var/www
        volumes:
            - ./:/var/www
        networks:
            - Pourier

    postgres:
        image: postgres:16
        container_name: Pourier-db
        restart: unless-stopped
        environment:
            POSTGRES_DB: ${DB_DATABASE}
            POSTGRES_USER: ${DB_USERNAME}
            POSTGRES_PASSWORD: ${DB_PASSWORD}
        volumes:
            - postgres-data:/var/lib/postgresql/data
        networks:
            - Pourier

    redis:
        image: redis:7-alpine
        container_name: Pourier-redis
        restart: unless-stopped
        networks:
            - Pourier

    nginx:
        image: nginx:alpine
        container_name: Pourier-nginx
        restart: unless-stopped
        ports:
            - "80:80"
            - "443:443"
        volumes:
            - ./:/var/www
            - ./nginx.conf:/etc/nginx/conf.d/default.conf
        networks:
            - Pourier

    queue-worker:
        build:
            context: .
            dockerfile: Dockerfile
        container_name: Pourier-queue
        restart: unless-stopped
        command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
        volumes:
            - ./:/var/www
        networks:
            - Pourier

networks:
    Pourier:
        driver: bridge

volumes:
    postgres-data:
```

### 15.2 Nginx Configuration

**`nginx.conf`**

```nginx
server {
    listen 80;
    server_name api.Pourier.com;
    root /var/www/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 15.3 Supervisor Configuration

**`/etc/supervisor/conf.d/Pourier-worker.conf`**

```ini
[program:Pourier-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/worker.log
stopwaitsecs=3600
```

### 15.4 Variables Environnement Production

**`.env.production`**

```env
APP_NAME=Pourier
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.Pourier.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Database
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=Pourier
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

# Redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# JWT
JWT_SECRET=your-jwt-secret-key
JWT_TTL=60
JWT_REFRESH_TTL=20160

# AWS S3
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=Pourier-photos
AWS_URL=https://Pourier-photos.s3.amazonaws.com

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@Pourier.com
MAIL_FROM_NAME="${APP_NAME}"

# CinetPay
CINETPAY_API_URL=https://api-checkout.cinetpay.com/v2
CINETPAY_SITE_ID=your-site-id
CINETPAY_API_KEY=your-api-key
CINETPAY_SECRET_KEY=your-secret-key
CINETPAY_NOTIFY_URL=https://api.Pourier.com/webhooks/cinetpay
CINETPAY_RETURN_URL=https://Pourier.com/payment/return

# Sentry
SENTRY_LARAVEL_DSN=your-sentry-dsn
```

### 15.5 Commandes Déploiement

**Installation initiale :**

```bash
# Cloner repo
git clone https://github.com/your-repo/Pourier-backend.git
cd Pourier-backend

# Installer dependencies
composer install --no-dev --optimize-autoloader

# Configuration
cp .env.production .env
php artisan key:generate
php artisan jwt:secret

# Migrations
php artisan migrate --force

# Seeders
php artisan db:seed --class=CategorySeeder

# Optimisations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Permissions
chmod -R 755 storage bootstrap/cache
```

**Mise à jour :**

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

### 15.6 Monitoring & Logs

**Sentry (Erreurs) :**

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your-sentry-dsn
```

**Laravel Telescope (Dev/Staging) :**

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Logs :**

-   `/storage/logs/laravel.log`
-   `/storage/logs/worker.log`

**Health Check :**

```php
Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});
```

---

## 📚 PHASE 16 : DOCUMENTATION

**Durée estimée : 2 jours**

### 16.1 README.md

```markdown
# Pourier Backend API

API REST Laravel 12 pour plateforme de vente de photos africaines.

## Prérequis

-   PHP 8.3+
-   PostgreSQL 16+
-   Redis 7+
-   Composer 2.x
-   AWS S3 (stockage)

## Installation Locale

\`\`\`bash

# Cloner repo

git clone https://github.com/your-repo/Pourier-backend.git
cd Pourier-backend

# Installer dependencies

composer install

# Configuration

cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# Créer base de données

createdb Pourier

# Migrations

php artisan migrate

# Seeders

php artisan db:seed

# Lancer serveur

php artisan serve
\`\`\`

## Configuration

### AWS S3

-   Créer bucket : `Pourier-photos`
-   Configurer IAM user avec permissions S3
-   Ajouter credentials dans `.env`

### CinetPay

-   Créer compte CinetPay
-   Obtenir : site_id, api_key, secret_key
-   Configurer webhook : `https://api.Pourier.com/webhooks/cinetpay`

## Commandes Utiles

\`\`\`bash

# Queue worker

php artisan queue:work

# Scheduler (cron)

php artisan schedule:run

# Tests

php artisan test

# Clear cache

php artisan config:clear
php artisan route:clear
php artisan view:clear
\`\`\`

## API Documentation

Base URL : `https://api.Pourier.com/api`

### Authentification

Utilise JWT (Bearer token).

**Headers requis :**
\`\`\`
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
\`\`\`

### Endpoints Principaux

-   `POST /auth/register` - Inscription
-   `POST /auth/login` - Connexion
-   `GET /photos` - Liste photos
-   `POST /orders` - Créer commande
-   `POST /photographer/photos` - Upload photo

Voir collection Postman complète : `/docs/postman_collection.json`

## Licence

Propriétaire - Pourier © 2024
```

### 16.2 Collection Postman

Créer collection avec :

-   Variables environnement (base_url, token)
-   Tous les endpoints (~70)
-   Exemples requêtes/réponses
-   Tests automatiques

**Exporter :**

```bash
/docs/postman_collection.json
```

### 16.3 Documentation API (OpenAPI/Swagger)

```bash
composer require darkaonline/l5-swagger
php artisan l5-swagger:generate
```

Accessible : `https://api.Pourier.com/api/documentation`

---

## 📊 RÉSUMÉ CHIFFRÉ

### Base de Données

-   **11 migrations** PostgreSQL
-   **9 modèles** Eloquent principaux (User, Photo, Order, etc.)
-   **11 tables** avec relations complexes
-   **15+ index** pour optimisation

### Backend

-   **8 services** métier (Auth, Payment, Revenue, Storage, etc.)
-   **~70 endpoints** API REST
-   **12 controllers** (Auth, Photo, Order, Admin, etc.)
-   **8 form requests** validation
-   **4 middlewares** personnalisés
-   **3 policies** autorisation

### Jobs & Queues

-   **6 jobs** asynchrones (ProcessPhotoUpload, GenerateInvoicePdf, etc.)
-   **4 commandes** Artisan planifiées
-   **Redis** pour cache et queues

### Notifications & Emails

-   **4 notifications** Laravel (database + mail)
-   **6 emails** transactionnels (Mailable)
-   **SendGrid/Mailgun** intégration

### Tests

-   **3+ tests** Feature minimum (Auth, Photo, Order)
-   **2+ tests** Unit (RevenueService, ImageProcessing)
-   **Factories** pour tous les modèles

### Infrastructure

-   **Docker** : 5 conteneurs (app, postgres, redis, nginx, queue)
-   **Supervisor** : Worker queue
-   **Nginx** : Reverse proxy
-   **AWS S3** : Stockage images
-   **CinetPay** : Paiements Mobile Money + Cartes

---

## ⏱️ ESTIMATION TOTALE

### Par Phase (1 développeur expérimenté)

| Phase | Description                     | Durée     |
| ----- | ------------------------------- | --------- |
| 1     | Setup & Infrastructure          | 3-5 jours |
| 2     | Authentification & Utilisateurs | 4-6 jours |
| 3     | Photos & Catégories             | 5-7 jours |
| 4     | Panier & Commandes              | 3-4 jours |
| 5     | Paiements CinetPay              | 4-6 jours |
| 6     | Revenus & Retraits              | 5-7 jours |
| 7     | Notifications                   | 2-3 jours |
| 8     | Emails                          | 2-3 jours |
| 9     | Modération & Admin              | 4-5 jours |
| 10    | Profils & Interactions          | 3-4 jours |
| 11    | Factures & Documents            | 2-3 jours |
| 12    | Recherche & Filtres             | 2-3 jours |
| 13    | Commandes Artisan               | 2 jours   |
| 14    | Tests                           | 3-5 jours |
| 15    | Déploiement                     | 3-4 jours |
| 16    | Documentation                   | 2 jours   |

**TOTAL : 40-60 jours de développement**

### Répartition

-   **Core Features** (Phases 1-6) : 24-35 jours (60%)
-   **Features Avancées** (Phases 7-12) : 15-23 jours (25%)
-   **Tests & Déploiement** (Phases 13-16) : 10-15 jours (15%)

---

## 🔒 SÉCURITÉ

### Mesures Implémentées

✅ **Validation stricte** : Form Requests pour tous les inputs
✅ **Protection CSRF** : Laravel par défaut
✅ **Rate limiting** : API throttling
✅ **JWT blacklist** : Invalidation tokens
✅ **Passwords hashés** : bcrypt
✅ **URLs signées** : Downloads, webhooks
✅ **Webhook signature** : CinetPay verification
✅ **SQL injection** : Eloquent ORM
✅ **XSS protection** : Blade escaping
✅ **Upload sanitization** : MIME type validation
✅ **Soft deletes** : Users, Photos
✅ **Authorization** : Policies sur toutes les resources

---

## 🚀 OPTIMISATIONS PERFORMANCES

### Implémentées

✅ **Eager loading** : Éviter N+1 queries
✅ **Cache Redis** : Stats, vues uniques
✅ **Queue Redis** : Jobs asynchrones
✅ **Index PostgreSQL** : Sur colonnes fréquentes
✅ **Pagination** : Toutes les listes
✅ **CDN CloudFront** : Distribution images
✅ **Image optimization** : Thumbnails, previews
✅ **Config/Route cache** : Production
✅ **Composer optimize** : Autoloader
✅ **OPcache** : PHP bytecode

---

## ✅ PRÊT À DÉMARRER ?

**Ordre recommandé :**

1. ✅ Setup projet + migrations (Phase 1)
2. ✅ Authentification JWT (Phase 2)
3. ✅ Photos & Upload (Phase 3)
4. ✅ Commandes (Phase 4)
5. ✅ Paiements CinetPay (Phase 5)
6. ✅ Revenus (Phase 6)
7. ✅ Notifications (Phase 7)
8. ✅ Admin & Modération (Phase 9)
9. ✅ Tests (Phase 14)
10. ✅ Déploiement (Phase 15)

**Bonne chance ! 🚀**
