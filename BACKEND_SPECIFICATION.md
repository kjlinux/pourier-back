# SPÉCIFICATION COMPLÈTE BACKEND LARAVEL 12 - AFROLENS/POUIRE

## 📋 TABLE DES MATIÈRES

1. [Vue d'ensemble du projet](#1-vue-densemble-du-projet)
2. [Stack technique](#2-stack-technique)
3. [Architecture & Structure](#3-architecture--structure)
4. [Base de données - Modèles de données](#4-base-de-données---modèles-de-données)
5. [Migrations PostgreSQL](#5-migrations-postgresql)
6. [Modèles Eloquent](#6-modèles-eloquent)
7. [Authentification JWT](#7-authentification-jwt)
8. [Stockage AWS S3](#8-stockage-aws-s3)
9. [Routes API](#9-routes-api)
10. [Contrôleurs](#10-contrôleurs)
11. [Requests (Validation)](#11-requests-validation)
12. [Middlewares](#12-middlewares)
13. [Services](#13-services)
14. [Jobs & Queues](#14-jobs--queues)
15. [Notifications](#15-notifications)
16. [Paiements](#16-paiements)
17. [Emails](#17-emails)
18. [Commandes Artisan](#18-commandes-artisan)
19. [Tests](#19-tests)
20. [Configuration & Déploiement](#20-configuration--déploiement)

---

## 1. VUE D'ENSEMBLE DU PROJET

### 1.1 Description

**AfroLens** (anciennement "Pouire") est une plateforme de vente de photos en ligne spécialisée dans la photographie africaine. La plateforme permet aux photographes de vendre leurs photos avec deux types de licences (Standard et Extended) et aux acheteurs d'acheter des photos de haute qualité.

### 1.2 Acteurs

- **Buyers (Acheteurs)** : Utilisateurs qui achètent des photos
- **Photographers (Photographes)** : Utilisateurs qui uploadent et vendent des photos
- **Admins** : Modèrent les photos, valident les photographes, gèrent les retraits

### 1.3 Fonctionnalités principales

#### Pour les Buyers
- Inscription/Connexion
- Recherche et filtrage de photos
- Ajout au panier
- Achat de photos (Mobile Money, Carte bancaire)
- Téléchargement des photos achetées
- Gestion des favoris
- Suivi de photographes

#### Pour les Photographers
- Tout ce que fait un Buyer +
- Upload de photos (avec métadonnées EXIF)
- Gestion de portfolio
- Suivi des revenus et statistiques
- Demandes de retrait (Mobile Money, Virement bancaire)
- Analytics avancées

#### Pour les Admins
- Modération des photos
- Validation des demandes de photographes
- Gestion des utilisateurs
- Traitement des retraits
- Vue d'ensemble des statistiques
- Mise en avant de photos (featured)

### 1.4 Modèle économique

- **Commission plateforme** : 20% sur chaque vente
- **Revenu photographe** : 80% du prix de vente
- **Période de sécurité** : 30 jours avant qu'un revenu soit disponible pour retrait
- **Retrait minimum** : 5000 FCFA (5000 XOF)
- **Prix minimum photo** : 500 FCFA (500 XOF)
- **Prix extended minimum** : 2x le prix standard
- **Devise** : Franc CFA (XOF) - montants en **integer** (pas de décimales)

---

## 2. STACK TECHNIQUE

### 2.1 Backend

```
- Laravel 12.x
- PHP 8.3+
- PostgreSQL 16+
- Redis 7+ (cache & queues)
- JWT Authentication (tymon/jwt-auth)
- AWS S3 (stockage images)
```

### 2.2 Packages Laravel requis

```json
{
  "require": {
    "php": "^8.3",
    "laravel/framework": "^12.0",
    "tymon/jwt-auth": "^2.1",
    "intervention/image": "^3.0",
    "league/flysystem-aws-s3-v3": "^3.0",
    "spatie/laravel-permission": "^6.0",
    "barryvdh/laravel-dompdf": "^3.0",
    "guzzlehttp/guzzle": "^7.8",
    "stripe/stripe-php": "^13.0"
  },
  "require-dev": {
    "laravel/telescope": "^5.0",
    "fakerphp/faker": "^1.23",
    "mockery/mockery": "^1.6",
    "phpunit/phpunit": "^11.0",
    "laravel/pint": "^1.13"
  }
}
```

### 2.3 Extensions PHP requises

```
- pdo_pgsql
- redis
- gd ou imagick
- exif
- fileinfo
- openssl
- mbstring
- curl
```

### 2.4 Services externes

- **AWS S3** : Stockage images
- **AWS CloudFront** (optionnel) : CDN
- **CinetPay** : Paiements (Mobile Money, Carte bancaire, etc.)
- **SendGrid ou Mailgun** : Envoi emails
- **Sentry** : Monitoring erreurs

---

## 3. ARCHITECTURE & STRUCTURE

### 3.1 Structure du projet Laravel

```
laravel-afrolens/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CalculateRevenuesCommand.php
│   │       ├── CleanExpiredDownloadsCommand.php
│   │       └── ProcessPendingPaymentsCommand.php
│   ├── Events/
│   │   ├── OrderCompleted.php
│   │   ├── PhotoApproved.php
│   │   ├── PhotoRejected.php
│   │   ├── WithdrawalRequested.php
│   │   └── NewSale.php
│   ├── Exceptions/
│   │   ├── InsufficientBalanceException.php
│   │   ├── PaymentFailedException.php
│   │   └── PhotoAlreadyModeratedExceptionphp
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── Auth/
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   ├── PasswordController.php
│   │   │   │   │   └── VerificationController.php
│   │   │   │   ├── Admin/
│   │   │   │   │   ├── DashboardController.php
│   │   │   │   │   ├── UserController.php
│   │   │   │   │   ├── PhotoModerationController.php
│   │   │   │   │   ├── PhotographerController.php
│   │   │   │   │   ├── WithdrawalController.php
│   │   │   │   │   └── AnalyticsController.php
│   │   │   │   ├── User/
│   │   │   │   │   ├── ProfileController.php
│   │   │   │   │   ├── NotificationController.php
│   │   │   │   │   └── FavoriteController.php
│   │   │   │   ├── Photographer/
│   │   │   │   │   ├── DashboardController.php
│   │   │   │   │   ├── PhotoController.php
│   │   │   │   │   ├── RevenueController.php
│   │   │   │   │   ├── WithdrawalController.php
│   │   │   │   │   └── AnalyticsController.php
│   │   │   │   ├── PhotoController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── CartController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── SearchController.php
│   │   │   │   └── DownloadController.php
│   │   ├── Middleware/
│   │   │   ├── CheckRole.php
│   │   │   ├── CheckPhotographer.php
│   │   │   ├── CheckAdmin.php
│   │   │   └── TrackPhotoView.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginRequest.php
│   │   │   │   └── RegisterRequest.php
│   │   │   ├── Photo/
│   │   │   │   ├── StorePhotoRequest.php
│   │   │   │   ├── UpdatePhotoRequest.php
│   │   │   │   └── SearchPhotoRequest.php
│   │   │   ├── Order/
│   │   │   │   ├── CreateOrderRequest.php
│   │   │   │   └── PayOrderRequest.php
│   │   │   └── Withdrawal/
│   │   │       └── CreateWithdrawalRequest.php
│   │   └── Resources/
│   │       ├── UserResource.php
│   │       ├── PhotoResource.php
│   │       ├── OrderResource.php
│   │       ├── WithdrawalResource.php
│   │       └── NotificationResource.php
│   ├── Jobs/
│   │   ├── ProcessPhotoUpload.php
│   │   ├── GenerateWatermark.php
│   │   ├── ExtractExifData.php
│   │   ├── GenerateInvoicePdf.php
│   │   ├── SendOrderConfirmationEmail.php
│   │   └── CalculateMonthlyRevenue.php
│   ├── Listeners/
│   │   ├── SendNewSaleNotification.php
│   │   ├── UpdatePhotographerStats.php
│   │   └── NotifyAdminOfPendingModeration.php
│   ├── Mail/
│   │   ├── WelcomeMail.php
│   │   ├── OrderConfirmationMail.php
│   │   ├── PhotoApprovedMail.php
│   │   ├── WithdrawalProcessedMail.php
│   │   └── MonthlySummaryMail.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── PhotographerProfile.php
│   │   ├── Photo.php
│   │   ├── Category.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Withdrawal.php
│   │   ├── Notification.php
│   │   ├── Favorite.php
│   │   ├── Follow.php
│   │   └── Revenue.php
│   ├── Notifications/
│   │   ├── NewSaleNotification.php
│   │   ├── PhotoApprovedNotification.php
│   │   ├── WithdrawalApprovedNotification.php
│   │   └── NewFollowerNotification.php
│   ├── Observers/
│   │   ├── PhotoObserver.php
│   │   └── OrderObserver.php
│   ├── Policies/
│   │   ├── PhotoPolicy.php
│   │   ├── OrderPolicy.php
│   │   └── WithdrawalPolicy.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── PhotoService.php
│   │   ├── ImageProcessingService.php
│   │   ├── PaymentService.php
│   │   ├── StorageService.php
│   │   ├── RevenueService.php
│   │   ├── NotificationService.php
│   │   └── InvoiceService.php
│   └── Traits/
│       ├── HasUuid.php
│       └── Searchable.php
├── bootstrap/
├── config/
│   ├── auth.php
│   ├── jwt.php
│   ├── filesystems.php
│   ├── services.php
│   └── afrolens.php (custom config)
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── CategorySeeder.php
│       ├── UserSeeder.php
│       └── PhotoSeeder.php
├── public/
├── resources/
│   └── views/
│       ├── emails/
│       └── invoices/
├── routes/
│   ├── api.php
│   └── web.php
├── storage/
│   ├── app/
│   │   ├── public/
│   │   └── temp/
│   ├── framework/
│   └── logs/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── composer.json
├── phpunit.xml
└── README.md
```

### 3.2 Principes architecturaux

1. **Repository Pattern** (optionnel, pour logique complexe)
2. **Service Layer** pour logique métier
3. **API Resources** pour transformation données
4. **Form Requests** pour validation
5. **Observers** pour événements modèles
6. **Jobs** pour tâches asynchrones
7. **Policies** pour autorisation
8. **Events & Listeners** pour découplage

---

## 4. BASE DE DONNÉES - MODÈLES DE DONNÉES

### 4.1 Diagramme ERD

```
┌─────────────┐         ┌──────────────────────┐         ┌──────────────┐
│    users    │◄───────┤ photographer_profiles │         │  categories  │
└──────┬──────┘         └──────────────────────┘         └───────┬──────┘
       │                                                          │
       │                                                          │
       │         ┌──────────────┐                                │
       └────────►│    photos    │◄───────────────────────────────┘
       │         └───────┬──────┘
       │                 │
       │                 │
       │         ┌───────┴──────┐
       │         │              │
       │    ┌────▼─────┐   ┌────▼────────┐
       │    │ favorites│   │   follows   │
       │    └──────────┘   └─────────────┘
       │
       │
       │    ┌──────────────┐         ┌─────────────────┐
       └───►│    orders    │◄───────┤   order_items   │
       │    └──────────────┘         └─────────────────┘
       │
       │    ┌───────────────┐
       └───►│  withdrawals  │
       │    └───────────────┘
       │
       │    ┌─────────────────┐
       └───►│ notifications   │
            └─────────────────┘
```

### 4.2 Tables & Entités

#### 4.2.1 users

Utilisateurs de la plateforme (buyers, photographers, admins).

```
Champs principaux:
- id: UUID (PK)
- email: string (unique)
- password: string (hashed)
- first_name: string
- last_name: string
- avatar_url: string (nullable)
- phone: string (nullable)
- bio: text (nullable)
- account_type: enum ('buyer', 'photographer', 'admin')
- is_verified: boolean
- is_active: boolean
- email_verified_at: timestamp (nullable)
- last_login: timestamp (nullable)
- remember_token: string (nullable)
- created_at, updated_at, deleted_at
```

#### 4.2.2 photographer_profiles

Profil étendu pour les photographes.

```
Champs:
- id: UUID (PK)
- user_id: UUID (FK → users)
- username: string (unique)
- display_name: string
- cover_photo_url: string (nullable)
- location: string (nullable)
- website: string (nullable)
- instagram: string (nullable)
- portfolio_url: string (nullable)
- specialties: json (nullable)
- status: enum ('pending', 'approved', 'rejected', 'suspended')
- commission_rate: decimal(5,4) [default: 0.2000]
- total_sales: integer [default: 0]
- total_revenue: decimal(12,2) [default: 0]
- followers_count: integer [default: 0]
- approved_at: timestamp (nullable)
- approved_by: UUID (FK → users, nullable)
- created_at, updated_at
```

#### 4.2.3 photos

Photos uploadées par les photographes.

```
Champs:
- id: UUID (PK)
- photographer_id: UUID (FK → users)
- category_id: UUID (FK → categories)
- title: string
- description: text (nullable)
- tags: json

URLs:
- original_url: string
- preview_url: string (avec watermark)
- thumbnail_url: string

Métadonnées:
- width: integer
- height: integer
- file_size: bigInteger
- format: string (jpg, png)
- color_palette: json (nullable)

EXIF:
- camera: string (nullable)
- lens: string (nullable)
- iso: integer (nullable)
- aperture: string (nullable)
- shutter_speed: string (nullable)
- focal_length: integer (nullable)
- taken_at: timestamp (nullable)
- location: string (nullable)

Prix (en Franc CFA - XOF):
- price_standard: unsignedBigInteger (en FCFA)
- price_extended: unsignedBigInteger (en FCFA)

Stats:
- views_count: integer [default: 0]
- downloads_count: integer [default: 0]
- favorites_count: integer [default: 0]
- sales_count: integer [default: 0]

Statut:
- is_public: boolean [default: false]
- status: enum ('pending', 'approved', 'rejected')
- rejection_reason: text (nullable)
- moderated_at: timestamp (nullable)
- moderated_by: UUID (FK → users, nullable)

Featured:
- featured: boolean [default: false]
- featured_until: timestamp (nullable)

Timestamps:
- created_at, updated_at, deleted_at
```

#### 4.2.4 categories

Catégories de photos (hiérarchiques).

```
Champs:
- id: UUID (PK)
- name: string
- slug: string (unique)
- description: text (nullable)
- icon_url: string (nullable)
- parent_id: UUID (FK → categories, nullable)
- display_order: integer [default: 0]
- is_active: boolean [default: true]
- photo_count: integer [default: 0] (calculé)
- created_at, updated_at
```

#### 4.2.5 orders

Commandes passées par les utilisateurs.

```
Champs:
- id: UUID (PK)
- order_number: string (unique, ex: ORD-20251113-ABC123)
- user_id: UUID (FK → users)

Montants (en Franc CFA - XOF):
- subtotal: unsignedBigInteger
- tax: unsignedBigInteger [default: 0]
- discount: unsignedBigInteger [default: 0]
- total: unsignedBigInteger

Paiement (via CinetPay):
- payment_method: enum ('mobile_money', 'card') (via CinetPay)
- payment_provider: string (nullable) ('ORANGE', 'MTN', 'MOOV', 'WAVE', 'CARD', etc.)
- payment_status: enum ('pending', 'completed', 'failed', 'refunded')
- payment_id: string (nullable) (ID transaction CinetPay)
- cinetpay_transaction_id: string (nullable)
- paid_at: timestamp (nullable)

Facturation:
- billing_email: string
- billing_first_name: string
- billing_last_name: string
- billing_phone: string

Documents:
- invoice_url: string (nullable)

Timestamps:
- created_at, updated_at
```

#### 4.2.6 order_items

Lignes de commande (photos dans une commande).

```
Champs:
- id: UUID (PK)
- order_id: UUID (FK → orders)
- photo_id: UUID (FK → photos)
- photographer_id: UUID (FK → users)

Snapshots:
- photo_title: string
- photo_preview: string
- photographer_name: string

Licence:
- license_type: enum ('standard', 'extended')
- price: unsignedBigInteger (en FCFA)

Commission:
- commission_rate: decimal(5,4)
- commission_amount: unsignedBigInteger (en FCFA)
- photographer_amount: unsignedBigInteger (en FCFA)

Download:
- download_url: string (nullable) (URL signée)
- download_count: integer [default: 0]
- download_expires_at: timestamp (nullable)

Timestamps:
- created_at
```

#### 4.2.7 withdrawals

Demandes de retrait des photographes.

```
Champs:
- id: UUID (PK)
- photographer_id: UUID (FK → users)

Montant (en Franc CFA - XOF):
- amount: unsignedBigInteger

Statut:
- status: enum ('pending', 'approved', 'rejected', 'completed')

Méthode (via CinetPay):
- payment_method: enum ('mobile_money', 'bank_transfer')
- payment_details: json
  /*
  Mobile Money (via CinetPay): {
    "provider": "ORANGE"|"MTN"|"MOOV"|"WAVE",
    "phone": "+226 XX XX XX XX",
    "name": "Nom du titulaire"
  }
  Bank Transfer: {
    "bank_name": "Nom de la banque",
    "account_number": "Numéro de compte",
    "account_name": "Nom du titulaire",
    "iban": "IBAN" (optionnel)
  }
  */

Traitement:
- requested_at: timestamp [default: now]
- processed_at: timestamp (nullable)
- processed_by: UUID (FK → users, nullable)
- notes: text (nullable) (notes admin)
- transaction_id: string (nullable) (ID transaction externe)

Timestamps:
- created_at, updated_at
```

#### 4.2.8 notifications

Notifications in-app pour les utilisateurs.

```
Champs:
- id: UUID (PK)
- user_id: UUID (FK → users)
- type: string (ex: 'new_sale', 'photo_approved')
- title: string
- message: text
- data: json (nullable) (données additionnelles)
- is_read: boolean [default: false]
- read_at: timestamp (nullable)
- created_at
```

#### 4.2.9 favorites (pivot table)

Photos favorites d'un utilisateur.

```
Champs:
- id: UUID (PK)
- user_id: UUID (FK → users)
- photo_id: UUID (FK → photos)
- created_at

Contraintes:
- unique(user_id, photo_id)
```

#### 4.2.10 follows (pivot table)

Utilisateurs qui suivent des photographes.

```
Champs:
- id: UUID (PK)
- user_id: UUID (FK → users) (follower)
- photographer_id: UUID (FK → users) (photographe)
- created_at

Contraintes:
- unique(user_id, photographer_id)
```

#### 4.2.11 revenues (table/vue calculée)

Revenus mensuels des photographes (peut être une vue matérialisée ou table calculée).

```
Champs:
- id: UUID (PK)
- photographer_id: UUID (FK → users)
- month: date (YYYY-MM-01)

Montants (en Franc CFA - XOF):
- total_sales: unsignedBigInteger
- commission: unsignedBigInteger
- net_revenue: unsignedBigInteger

Soldes (en Franc CFA - XOF):
- available_balance: unsignedBigInteger
- pending_balance: unsignedBigInteger
- withdrawn: unsignedBigInteger

Stats:
- sales_count: integer
- photos_sold: integer

Timestamps:
- updated_at
```

---

## 5. MIGRATIONS POSTGRESQL

### 5.1 Migration: create_users_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('avatar_url')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('bio')->nullable();
            $table->enum('account_type', ['buyer', 'photographer', 'admin'])->default('buyer');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['email', 'is_active']);
            $table->index('account_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### 5.2 Migration: create_photographer_profiles_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photographer_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('username', 50)->unique();
            $table->string('display_name');
            $table->string('cover_photo_url')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->string('instagram', 50)->nullable();
            $table->string('portfolio_url')->nullable();
            $table->json('specialties')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->decimal('commission_rate', 5, 4)->default(0.2000);
            $table->unsignedInteger('total_sales')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->unsignedInteger('followers_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users');
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('username');
            $table->index('status');
            $table->index('total_revenue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photographer_profiles');
    }
};
```

### 5.3 Migration: create_categories_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon_url')->nullable();
            $table->foreignUuid('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('photo_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('parent_id');
            $table->index(['is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

### 5.4 Migration: create_photos_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('photographer_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('category_id')->constrained('categories')->onDelete('restrict');

            // Informations de base
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('tags');

            // URLs
            $table->string('original_url');
            $table->string('preview_url');
            $table->string('thumbnail_url');

            // Métadonnées image
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('file_size');
            $table->string('format', 10);
            $table->json('color_palette')->nullable();

            // EXIF
            $table->string('camera')->nullable();
            $table->string('lens')->nullable();
            $table->unsignedInteger('iso')->nullable();
            $table->string('aperture', 20)->nullable();
            $table->string('shutter_speed', 20)->nullable();
            $table->unsignedInteger('focal_length')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->string('location')->nullable();

            // Prix (en Franc CFA - XOF)
            $table->unsignedBigInteger('price_standard');
            $table->unsignedBigInteger('price_extended');

            // Stats
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            $table->unsignedInteger('sales_count')->default(0);

            // Statut
            $table->boolean('is_public')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->foreignUuid('moderated_by')->nullable()->constrained('users');

            // Featured
            $table->boolean('featured')->default(false);
            $table->timestamp('featured_until')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['photographer_id', 'status']);
            $table->index(['category_id', 'is_public']);
            $table->index(['status', 'is_public']);
            $table->index('featured');
            $table->index('created_at');
            $table->index(['price_standard', 'price_extended']);
        });

        // Full-text search sur PostgreSQL
        DB::statement('CREATE INDEX photos_fulltext_idx ON photos USING GIN (to_tsvector(\'english\', title || \' \' || COALESCE(description, \'\')))');
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
```

### 5.5 Migration: create_orders_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');

            // Montants (en Franc CFA - XOF)
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total');

            // Paiement (via CinetPay)
            $table->enum('payment_method', ['mobile_money', 'card']);
            $table->string('payment_provider')->nullable(); // ORANGE, MTN, MOOV, WAVE, CARD
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_id')->nullable(); // ID transaction CinetPay
            $table->string('cinetpay_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Facturation
            $table->string('billing_email');
            $table->string('billing_first_name');
            $table->string('billing_last_name');
            $table->string('billing_phone', 20);

            // Documents
            $table->string('invoice_url')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('order_number');
            $table->index(['user_id', 'payment_status']);
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

### 5.6 Migration: create_order_items_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('photo_id')->constrained()->onDelete('restrict');
            $table->foreignUuid('photographer_id')->constrained('users')->onDelete('restrict');

            // Snapshots
            $table->string('photo_title');
            $table->string('photo_preview');
            $table->string('photographer_name');

            // Licence
            $table->enum('license_type', ['standard', 'extended']);
            $table->unsignedBigInteger('price'); // en FCFA

            // Commission
            $table->decimal('commission_rate', 5, 4);
            $table->unsignedBigInteger('commission_amount'); // en FCFA
            $table->unsignedBigInteger('photographer_amount'); // en FCFA

            // Download
            $table->string('download_url')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('download_expires_at')->nullable();

            $table->timestamp('created_at');

            // Indexes
            $table->index('order_id');
            $table->index('photo_id');
            $table->index('photographer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
```

### 5.7 Migration: create_withdrawals_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('photographer_id')->constrained('users')->onDelete('cascade');

            // Montant (en Franc CFA - XOF)
            $table->unsignedBigInteger('amount');

            // Statut
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');

            // Méthode
            $table->enum('payment_method', ['mobile_money', 'bank_transfer']);
            $table->json('payment_details');

            // Traitement
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->foreignUuid('processed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->string('transaction_id')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['photographer_id', 'status']);
            $table->index('status');
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
```

### 5.8 Migration: create_notifications_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at');

            // Indexes
            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

### 5.9 Migration: create_favorites_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('photo_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at');

            // Contrainte unique
            $table->unique(['user_id', 'photo_id']);

            // Indexes
            $table->index('user_id');
            $table->index('photo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
```

### 5.10 Migration: create_follows_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('photographer_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('created_at');

            // Contrainte unique
            $table->unique(['user_id', 'photographer_id']);

            // Indexes
            $table->index('user_id');
            $table->index('photographer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
```

### 5.11 Migration: create_revenues_table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('photographer_id')->constrained('users')->onDelete('cascade');
            $table->date('month'); // Format: YYYY-MM-01

            // Montants (en Franc CFA - XOF)
            $table->unsignedBigInteger('total_sales')->default(0);
            $table->unsignedBigInteger('commission')->default(0);
            $table->unsignedBigInteger('net_revenue')->default(0);

            // Soldes (en Franc CFA - XOF)
            $table->unsignedBigInteger('available_balance')->default(0);
            $table->unsignedBigInteger('pending_balance')->default(0);
            $table->unsignedBigInteger('withdrawn')->default(0);

            // Stats
            $table->unsignedInteger('sales_count')->default(0);
            $table->unsignedInteger('photos_sold')->default(0);

            $table->timestamp('updated_at');

            // Contrainte unique
            $table->unique(['photographer_id', 'month']);

            // Indexes
            $table->index('photographer_id');
            $table->index('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenues');
    }
};
```

---

## 6. MODÈLES ELOQUENT

### 6.1 User Model

**Fichier**: `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes, HasRoles;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'avatar_url',
        'phone',
        'bio',
        'account_type',
        'is_verified',
        'is_active',
        'email_verified_at',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'account_type' => $this->account_type,
            'is_verified' => $this->is_verified,
        ];
    }

    // Relationships
    public function photographerProfile()
    {
        return $this->hasOne(PhotographerProfile::class);
    }

    public function photos()
    {
        return $this->hasMany(Photo::class, 'photographer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class, 'photographer_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Photo::class, 'favorites')
            ->withTimestamps();
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'user_id', 'photographer_id')
            ->withTimestamps();
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'photographer_id', 'user_id')
            ->withTimestamps();
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopePhotographers($query)
    {
        return $query->where('account_type', 'photographer');
    }

    public function scopeBuyers($query)
    {
        return $query->where('account_type', 'buyer');
    }

    public function scopeAdmins($query)
    {
        return $query->where('account_type', 'admin');
    }

    // Methods
    public function isPhotographer(): bool
    {
        return $this->account_type === 'photographer';
    }

    public function isAdmin(): bool
    {
        return $this->account_type === 'admin';
    }

    public function isBuyer(): bool
    {
        return $this->account_type === 'buyer';
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }
}
```

### 6.2 PhotographerProfile Model

**Fichier**: `app/Models/PhotographerProfile.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotographerProfile extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'username',
        'display_name',
        'cover_photo_url',
        'location',
        'website',
        'instagram',
        'portfolio_url',
        'specialties',
        'status',
        'commission_rate',
        'total_sales',
        'total_revenue',
        'followers_count',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'specialties' => 'array',
        'commission_rate' => 'decimal:4',
        'total_revenue' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    // Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }
}
```

### 6.3 Photo Model

**Fichier**: `app/Models/Photo.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Photo extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'photographer_id',
        'category_id',
        'title',
        'description',
        'tags',
        'original_url',
        'preview_url',
        'thumbnail_url',
        'width',
        'height',
        'file_size',
        'format',
        'color_palette',
        'camera',
        'lens',
        'iso',
        'aperture',
        'shutter_speed',
        'focal_length',
        'taken_at',
        'location',
        'price_standard',
        'price_extended',
        'views_count',
        'downloads_count',
        'favorites_count',
        'sales_count',
        'is_public',
        'status',
        'rejection_reason',
        'moderated_at',
        'moderated_by',
        'featured',
        'featured_until',
    ];

    protected $casts = [
        'tags' => 'array',
        'color_palette' => 'array',
        'price_standard' => 'integer', // en FCFA
        'price_extended' => 'integer', // en FCFA
        'is_public' => 'boolean',
        'featured' => 'boolean',
        'taken_at' => 'datetime',
        'moderated_at' => 'datetime',
        'featured_until' => 'datetime',
    ];

    // Relationships
    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true)
            ->where(function ($q) {
                $q->whereNull('featured_until')
                    ->orWhere('featured_until', '>', now());
            });
    }

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

    public function scopeFilterByCategory($query, $categoryIds)
    {
        if (empty($categoryIds)) {
            return $query;
        }

        return $query->whereIn('category_id', (array) $categoryIds);
    }

    public function scopeFilterByPhotographer($query, $photographerId)
    {
        if (empty($photographerId)) {
            return $query;
        }

        return $query->where('photographer_id', $photographerId);
    }

    public function scopeFilterByPrice($query, $minPrice = null, $maxPrice = null)
    {
        if ($minPrice !== null) {
            $query->where('price_standard', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('price_standard', '<=', $maxPrice);
        }

        return $query;
    }

    public function scopeFilterByOrientation($query, $orientation)
    {
        if (empty($orientation)) {
            return $query;
        }

        return match ($orientation) {
            'landscape' => $query->whereRaw('width > height'),
            'portrait' => $query->whereRaw('height > width'),
            'square' => $query->whereRaw('width = height'),
            default => $query,
        };
    }

    public function scopeSortBy($query, $sortBy)
    {
        return match ($sortBy) {
            'popularity' => $query->orderByDesc('views_count'),
            'date' => $query->latest(),
            'price_asc' => $query->orderBy('price_standard'),
            'price_desc' => $query->orderByDesc('price_standard'),
            default => $query->latest(),
        };
    }

    // Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(User $moderator): void
    {
        $this->update([
            'status' => 'approved',
            'is_public' => true,
            'moderated_at' => now(),
            'moderated_by' => $moderator->id,
            'rejection_reason' => null,
        ]);
    }

    public function reject(User $moderator, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'is_public' => false,
            'moderated_at' => now(),
            'moderated_by' => $moderator->id,
            'rejection_reason' => $reason,
        ]);
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function incrementSales(): void
    {
        $this->increment('sales_count');
    }

    public function incrementDownloads(): void
    {
        $this->increment('downloads_count');
    }

    public function getOrientationAttribute(): string
    {
        if ($this->width > $this->height) {
            return 'landscape';
        } elseif ($this->height > $this->width) {
            return 'portrait';
        }
        return 'square';
    }
}
```

### 6.4 Category Model

**Fichier**: `app/Models/Category.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon_url',
        'parent_id',
        'display_order',
        'is_active',
        'photo_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('display_order');
    }

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    // Methods
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    public function updatePhotoCount(): void
    {
        $this->update([
            'photo_count' => $this->photos()->approved()->count(),
        ]);
    }
}
```

### 6.5 Order Model

**Fichier**: `app/Models/Order.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'order_number',
        'user_id',
        'subtotal',
        'tax',
        'discount',
        'total',
        'payment_method',
        'payment_provider',
        'payment_status',
        'payment_id',
        'paid_at',
        'billing_email',
        'billing_first_name',
        'billing_last_name',
        'billing_phone',
        'invoice_url',
    ];

    protected $casts = [
        'subtotal' => 'integer', // en FCFA
        'tax' => 'integer', // en FCFA
        'discount' => 'integer', // en FCFA
        'total' => 'integer', // en FCFA
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('payment_status', 'refunded');
    }

    // Methods
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->payment_status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->payment_status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->payment_status === 'refunded';
    }

    public function markAsCompleted(string $paymentId): void
    {
        $this->update([
            'payment_status' => 'completed',
            'payment_id' => $paymentId,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['payment_status' => 'failed']);
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
```

### 6.6 OrderItem Model

**Fichier**: `app/Models/OrderItem.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'photo_id',
        'photographer_id',
        'photo_title',
        'photo_preview',
        'photographer_name',
        'license_type',
        'price',
        'commission_rate',
        'commission_amount',
        'photographer_amount',
        'download_url',
        'download_count',
        'download_expires_at',
    ];

    protected $casts = [
        'price' => 'integer', // en FCFA
        'commission_rate' => 'decimal:4',
        'commission_amount' => 'integer', // en FCFA
        'photographer_amount' => 'integer', // en FCFA
        'created_at' => 'datetime',
        'download_expires_at' => 'datetime',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }

    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }

    // Methods
    public function generateDownloadUrl(): string
    {
        // Génère une URL signée valide 24h
        $expires = now()->addHours(24);

        $this->update([
            'download_expires_at' => $expires,
        ]);

        return \URL::temporarySignedRoute(
            'downloads.photo',
            $expires,
            ['orderItem' => $this->id]
        );
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    public function canDownload(): bool
    {
        return $this->order->isCompleted()
            && ($this->download_expires_at === null || $this->download_expires_at->isFuture());
    }
}
```

### 6.7 Withdrawal Model

**Fichier**: `app/Models/Withdrawal.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'photographer_id',
        'amount',
        'status',
        'payment_method',
        'payment_details',
        'requested_at',
        'processed_at',
        'processed_by',
        'notes',
        'transaction_id',
    ];

    protected $casts = [
        'amount' => 'integer', // en FCFA
        'payment_details' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(User $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'processed_at' => now(),
            'processed_by' => $admin->id,
            'notes' => $notes,
        ]);
    }

    public function complete(User $admin, string $transactionId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'processed_by' => $admin->id,
            'transaction_id' => $transactionId,
            'notes' => $notes,
        ]);
    }

    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => $admin->id,
            'notes' => $reason,
        ]);
    }
}
```

### 6.8 Notification Model

**Fichier**: `app/Models/Notification.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    // Methods
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}
```

### 6.9 Revenue Model

**Fichier**: `app/Models/Revenue.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'photographer_id',
        'month',
        'total_sales',
        'commission',
        'net_revenue',
        'available_balance',
        'pending_balance',
        'withdrawn',
        'sales_count',
        'photos_sold',
    ];

    protected $casts = [
        'month' => 'date',
        'total_sales' => 'integer', // en FCFA
        'commission' => 'integer', // en FCFA
        'net_revenue' => 'integer', // en FCFA
        'available_balance' => 'integer', // en FCFA
        'pending_balance' => 'integer', // en FCFA
        'withdrawn' => 'integer', // en FCFA
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function photographer()
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }
}
```

---

## 7. AUTHENTIFICATION JWT

### 7.1 Configuration JWT

**Fichier**: `config/jwt.php`

```php
<?php

return [
    'secret' => env('JWT_SECRET'),
    'keys' => [
        'public' => env('JWT_PUBLIC_KEY'),
        'private' => env('JWT_PRIVATE_KEY'),
        'passphrase' => env('JWT_PASSPHRASE'),
    ],
    'ttl' => env('JWT_TTL', 60), // minutes
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 14 jours
    'algo' => env('JWT_ALGO', 'HS256'),
    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],
    'persistent_claims' => [],
    'lock_subject' => true,
    'leeway' => env('JWT_LEEWAY', 0),
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),
    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),
    'decrypt_cookies' => false,
    'providers' => [
        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,
        'auth' => Tymon\JWTAuth\Providers\Auth\Illuminate::class,
        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,
    ],
];
```

**Fichier**: `config/auth.php` (modifier)

```php
'defaults' => [
    'guard' => 'api',
    'passwords' => 'users',
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

### 7.2 AuthService

**Fichier**: `app/Services/AuthService.php`

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\PhotographerProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Créer l'utilisateur
            $user = User::create([
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'account_type' => $data['account_type'],
            ]);

            // Si photographe, créer le profil
            if ($data['account_type'] === 'photographer') {
                $username = $this->generateUniqueUsername($user);

                PhotographerProfile::create([
                    'user_id' => $user->id,
                    'username' => $username,
                    'display_name' => $user->full_name,
                    'status' => 'pending',
                ]);
            }

            // Générer token JWT
            $token = JWTAuth::fromUser($user);

            return [
                'user' => $user->load('photographerProfile'),
                'token' => $token,
            ];
        });
    }

    public function login(string $email, string $password, bool $rememberMe = false): array
    {
        $credentials = compact('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $user = auth()->user();

        // Vérifier que le compte est actif
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Votre compte a été désactivé.'],
            ]);
        }

        // Mettre à jour last_login
        $user->update(['last_login' => now()]);

        // Si remember me, prolonger le TTL du token
        if ($rememberMe) {
            JWTAuth::factory()->setTTL(config('jwt.refresh_ttl'));
        }

        return [
            'user' => $user->load('photographerProfile'),
            'token' => $token,
        ];
    }

    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function refresh(): string
    {
        return JWTAuth::refresh(JWTAuth::getToken());
    }

    public function me(): User
    {
        return auth()->user()->load('photographerProfile');
    }

    private function generateUniqueUsername(User $user): string
    {
        $baseUsername = strtolower($user->first_name . '_' . $user->last_name);
        $baseUsername = preg_replace('/[^a-z0-9_]/', '', $baseUsername);

        $username = $baseUsername;
        $counter = 1;

        while (PhotographerProfile::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }
}
```

---

## 8. STOCKAGE AWS S3

### 8.1 Configuration AWS S3

**Fichier**: `config/filesystems.php` (ajouter)

```php
'disks' => [
    // ... autres disques

    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'throw' => false,
    ],

    's3_public' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

**Fichier**: `.env` (ajouter)

```env
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=afrolens-photos
AWS_URL=https://afrolens-photos.s3.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false

# Optionnel: CloudFront CDN
AWS_CLOUDFRONT_URL=https://d1234abcd.cloudfront.net
```

### 8.2 StorageService

**Fichier**: `app/Services/StorageService.php`

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    private string $disk = 's3';

    public function storePhoto(UploadedFile $file, string $photographerId, string $type = 'original'): string
    {
        $filename = $this->generateFilename($file);
        $path = "photos/{$photographerId}/{$type}/{$filename}";

        Storage::disk($this->disk)->put($path, file_get_contents($file), 'public');

        return Storage::disk($this->disk)->url($path);
    }

    public function storeAvatar(UploadedFile $file, string $userId): string
    {
        $filename = $this->generateFilename($file);
        $path = "avatars/{$userId}/{$filename}";

        Storage::disk($this->disk)->put($path, file_get_contents($file), 'public');

        return Storage::disk($this->disk)->url($path);
    }

    public function storeCoverPhoto(UploadedFile $file, string $photographerId): string
    {
        $filename = $this->generateFilename($file);
        $path = "covers/{$photographerId}/{$filename}";

        Storage::disk($this->disk)->put($path, file_get_contents($file), 'public');

        return Storage::disk($this->disk)->url($path);
    }

    public function storeInvoice(string $content, string $orderNumber): string
    {
        $path = "invoices/{$orderNumber}.pdf";

        Storage::disk($this->disk)->put($path, $content, 'public');

        return Storage::disk($this->disk)->url($path);
    }

    public function generateSignedUrl(string $path, int $expirationMinutes = 1440): string
    {
        // 1440 minutes = 24 heures
        return Storage::disk($this->disk)->temporaryUrl(
            $path,
            now()->addMinutes($expirationMinutes)
        );
    }

    public function delete(string $url): bool
    {
        $path = $this->urlToPath($url);
        return Storage::disk($this->disk)->delete($path);
    }

    public function exists(string $url): bool
    {
        $path = $this->urlToPath($url);
        return Storage::disk($this->disk)->exists($path);
    }

    private function generateFilename(UploadedFile $file): string
    {
        return Str::uuid() . '.' . $file->getClientOriginalExtension();
    }

    private function urlToPath(string $url): string
    {
        // Convertir URL en path relatif
        $baseUrl = config('filesystems.disks.s3.url');
        return str_replace($baseUrl . '/', '', $url);
    }

    public function getOriginalPath(string $url): string
    {
        return $this->urlToPath($url);
    }
}
```

### 8.3 ImageProcessingService

**Fichier**: `app/Services/ImageProcessingService.php`

```php
<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use Illuminate\Support\Facades\File;

class ImageProcessingService
{
    private ImageManager $imageManager;
    private StorageService $storageService;

    public function __construct(StorageService $storageService)
    {
        $this->imageManager = new ImageManager(new Driver());
        $this->storageService = $storageService;
    }

    public function processUploadedPhoto(string $tempPath, string $photographerId): array
    {
        $image = $this->imageManager->read($tempPath);

        // Extraire métadonnées
        $metadata = $this->extractMetadata($image, $tempPath);

        // Générer thumbnail (300x300)
        $thumbnailPath = $this->generateThumbnail($image, $tempPath);
        $thumbnailUrl = $this->uploadToS3($thumbnailPath, $photographerId, 'thumbnails');

        // Générer preview avec watermark (1200px max)
        $previewPath = $this->generatePreviewWithWatermark($image, $tempPath);
        $previewUrl = $this->uploadToS3($previewPath, $photographerId, 'previews');

        // Uploader original
        $originalUrl = $this->uploadToS3($tempPath, $photographerId, 'originals');

        // Nettoyer fichiers temporaires
        File::delete([$thumbnailPath, $previewPath, $tempPath]);

        return [
            'original_url' => $originalUrl,
            'preview_url' => $previewUrl,
            'thumbnail_url' => $thumbnailUrl,
            'width' => $metadata['width'],
            'height' => $metadata['height'],
            'file_size' => $metadata['file_size'],
            'format' => $metadata['format'],
            'color_palette' => $metadata['color_palette'],
        ];
    }

    private function generateThumbnail($image, string $originalPath): string
    {
        $thumbnail = clone $image;
        $thumbnail->cover(300, 300);

        $thumbnailPath = storage_path('app/temp/thumb_' . basename($originalPath));
        $thumbnail->save($thumbnailPath, quality: 85);

        return $thumbnailPath;
    }

    private function generatePreviewWithWatermark($image, string $originalPath): string
    {
        $preview = clone $image;

        // Redimensionner à 1200px max
        $preview->scale(width: 1200);

        // Appliquer watermark
        $preview = $this->applyWatermark($preview);

        $previewPath = storage_path('app/temp/preview_' . basename($originalPath));
        $preview->save($previewPath, quality: 80);

        return $previewPath;
    }

    private function applyWatermark($image)
    {
        $width = $image->width();
        $height = $image->height();

        // Pattern de watermark répété en diagonale
        $text = 'Pouire';
        $fontSize = 40;
        $angle = -45;
        $spacing = 150;

        for ($y = -$height; $y < $height * 2; $y += $spacing) {
            for ($x = -$width; $x < $width * 2; $x += $spacing) {
                $image->text($text, $x, $y, function ($font) use ($fontSize, $angle) {
                    $font->filename(public_path('fonts/Roboto-Bold.ttf'));
                    $font->size($fontSize);
                    $font->color('#ffffff');
                    $font->opacity(0.3);
                    $font->angle($angle);
                    $font->align('center');
                    $font->valign('middle');
                });
            }
        }

        return $image;
    }

    private function extractMetadata($image, string $path): array
    {
        $exif = @exif_read_data($path);

        return [
            'width' => $image->width(),
            'height' => $image->height(),
            'file_size' => File::size($path),
            'format' => strtolower($image->extension()),
            'color_palette' => $this->extractColorPalette($image),
            'exif' => $exif ? $this->parseExifData($exif) : [],
        ];
    }

    private function extractColorPalette($image): array
    {
        // Redimensionner pour performance
        $small = clone $image;
        $small->scale(width: 100);

        // Extraire couleurs dominantes (algorithme simplifié)
        $colors = [];
        // TODO: Implémenter extraction couleurs dominantes
        // Peut utiliser une librairie comme color-thief-php

        return $colors;
    }

    private function parseExifData(array $exif): array
    {
        return [
            'camera' => $exif['Model'] ?? null,
            'lens' => $exif['LensModel'] ?? null,
            'iso' => $exif['ISOSpeedRatings'] ?? null,
            'aperture' => $exif['FNumber'] ?? null,
            'shutter_speed' => $exif['ExposureTime'] ?? null,
            'focal_length' => isset($exif['FocalLength']) ? (int) $exif['FocalLength'] : null,
            'taken_at' => isset($exif['DateTimeOriginal']) ?
                \Carbon\Carbon::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']) : null,
        ];
    }

    private function uploadToS3(string $localPath, string $photographerId, string $type): string
    {
        $file = new \Illuminate\Http\UploadedFile($localPath, basename($localPath));
        return $this->storageService->storePhoto($file, $photographerId, $type);
    }
}
```

---

## 9. ROUTES API

**Fichier**: `routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Auth\VerificationController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\FavoriteController;
use App\Http\Controllers\Api\Photographer\DashboardController as PhotographerDashboardController;
use App\Http\Controllers\Api\Photographer\PhotoController as PhotographerPhotoController;
use App\Http\Controllers\Api\Photographer\RevenueController;
use App\Http\Controllers\Api\Photographer\WithdrawalController as PhotographerWithdrawalController;
use App\Http\Controllers\Api\Photographer\AnalyticsController as PhotographerAnalyticsController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\PhotoModerationController;
use App\Http\Controllers\Api\Admin\PhotographerController as AdminPhotographerController;
use App\Http\Controllers\Api\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Api\Admin\AnalyticsController as AdminAnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword']);
    Route::post('/reset-password', [PasswordController::class, 'resetPassword']);
    Route::get('/verify-email/{token}', [VerificationController::class, 'verify']);
});

// Photos publiques
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

// Catégories publiques
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}', [CategoryController::class, 'show']);
    Route::get('/{category}/photos', [CategoryController::class, 'photos']);
});

// Photographes publics
Route::prefix('photographers')->group(function () {
    Route::get('/', [AdminPhotographerController::class, 'index']);
    Route::get('/{photographer}', [AdminPhotographerController::class, 'show']);
    Route::get('/{photographer}/photos', [AdminPhotographerController::class, 'photos']);
    Route::get('/{photographer}/followers', [AdminPhotographerController::class, 'followers']);
});

// Protected routes (require authentication)
Route::middleware('auth:api')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [PasswordController::class, 'changePassword']);
        Route::post('/resend-verification', [VerificationController::class, 'resend']);
    });

    // User profile
    Route::prefix('user')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/avatar', [ProfileController::class, 'updateAvatar']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread/count', [NotificationController::class, 'unreadCount']);
        Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{notification}', [NotificationController::class, 'destroy']);
    });

    // Favorites
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/', [FavoriteController::class, 'store']);
        Route::delete('/{photo}', [FavoriteController::class, 'destroy']);
    });

    // Follows
    Route::post('/photographers/{photographer}/follow', [AdminPhotographerController::class, 'follow']);
    Route::delete('/photographers/{photographer}/unfollow', [AdminPhotographerController::class, 'unfollow']);

    // Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{cartItem}', [CartController::class, 'updateItem']);
        Route::delete('/items/{cartItem}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::post('/{order}/payment', [OrderController::class, 'processPayment']);
        Route::get('/{order}/invoice', [OrderController::class, 'downloadInvoice']);
    });

    // Downloads
    Route::get('/downloads/{orderItem}', [DownloadController::class, 'download'])
        ->name('downloads.photo');

    // Photographer routes
    Route::middleware('photographer')->prefix('photographer')->group(function () {
        Route::get('/dashboard', [PhotographerDashboardController::class, 'index']);

        // Photos
        Route::prefix('photos')->group(function () {
            Route::get('/', [PhotographerPhotoController::class, 'index']);
            Route::post('/', [PhotographerPhotoController::class, 'store']);
            Route::get('/{photo}', [PhotographerPhotoController::class, 'show']);
            Route::put('/{photo}', [PhotographerPhotoController::class, 'update']);
            Route::delete('/{photo}', [PhotographerPhotoController::class, 'destroy']);
        });

        // Revenue
        Route::prefix('revenue')->group(function () {
            Route::get('/summary', [RevenueController::class, 'summary']);
            Route::get('/monthly', [RevenueController::class, 'monthly']);
            Route::get('/transactions', [RevenueController::class, 'transactions']);
            Route::get('/stats', [RevenueController::class, 'stats']);
        });

        // Withdrawals
        Route::prefix('withdrawals')->group(function () {
            Route::get('/', [PhotographerWithdrawalController::class, 'index']);
            Route::post('/', [PhotographerWithdrawalController::class, 'store']);
            Route::get('/{withdrawal}', [PhotographerWithdrawalController::class, 'show']);
        });

        // Analytics
        Route::prefix('analytics')->group(function () {
            Route::get('/overview', [PhotographerAnalyticsController::class, 'overview']);
            Route::get('/photos', [PhotographerAnalyticsController::class, 'photos']);
            Route::get('/sales', [PhotographerAnalyticsController::class, 'sales']);
            Route::get('/revenue', [PhotographerAnalyticsController::class, 'revenue']);
        });
    });

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
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

        // Withdrawals
        Route::prefix('withdrawals')->group(function () {
            Route::get('/', [AdminWithdrawalController::class, 'index']);
            Route::get('/pending', [AdminWithdrawalController::class, 'pending']);
            Route::post('/{withdrawal}/approve', [AdminWithdrawalController::class, 'approve']);
            Route::post('/{withdrawal}/reject', [AdminWithdrawalController::class, 'reject']);
            Route::post('/{withdrawal}/complete', [AdminWithdrawalController::class, 'complete']);
        });

        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'adminIndex']);
            Route::get('/{order}', [OrderController::class, 'adminShow']);
        });

        // Analytics
        Route::prefix('analytics')->group(function () {
            Route::get('/overview', [AdminAnalyticsController::class, 'overview']);
            Route::get('/users', [AdminAnalyticsController::class, 'users']);
            Route::get('/photos', [AdminAnalyticsController::class, 'photos']);
            Route::get('/revenue', [AdminAnalyticsController::class, 'revenue']);
            Route::get('/photographers', [AdminAnalyticsController::class, 'photographers']);
        });

        // Categories (CRUD)
        Route::resource('categories', CategoryController::class)->except(['create', 'edit']);
    });
});
```

---

## 10. CONTRÔLEURS

### 10.1 AuthController

**Fichier**: `app/Http/Controllers/Api/Auth/AuthController.php`

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->email,
            $request->password,
            $request->boolean('remember_me')
        );

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    public function refresh(): JsonResponse
    {
        $token = $this->authService->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
            ],
        ]);
    }

    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }
}
```

### 10.2 PhotoController (Public)

**Fichier**: `app/Http/Controllers/Api/PhotoController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhotoResource;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $photos = Photo::query()
            ->with(['photographer.photographerProfile', 'category'])
            ->approved()
            ->public()
            ->latest()
            ->paginate($request->input('per_page', 24));

        return response()->json([
            'success' => true,
            'data' => [
                'photos' => PhotoResource::collection($photos->items()),
                'meta' => [
                    'current_page' => $photos->currentPage(),
                    'per_page' => $photos->perPage(),
                    'total' => $photos->total(),
                    'last_page' => $photos->lastPage(),
                    'from' => $photos->firstItem(),
                    'to' => $photos->lastItem(),
                ],
            ],
        ]);
    }

    public function show(Photo $photo): JsonResponse
    {
        // Vérifier que la photo est publique
        if (!$photo->is_public && !$this->userOwnsPhoto($photo)) {
            return response()->json([
                'success' => false,
                'message' => 'Photo non trouvée',
            ], 404);
        }

        $photo->load(['photographer.photographerProfile', 'category']);

        return response()->json([
            'success' => true,
            'data' => [
                'photo' => new PhotoResource($photo),
            ],
        ]);
    }

    public function featured(Request $request): JsonResponse
    {
        $photos = Photo::query()
            ->with(['photographer.photographerProfile', 'category'])
            ->approved()
            ->public()
            ->featured()
            ->latest()
            ->paginate($request->input('per_page', 24));

        return response()->json([
            'success' => true,
            'data' => [
                'photos' => PhotoResource::collection($photos->items()),
                'meta' => [
                    'current_page' => $photos->currentPage(),
                    'per_page' => $photos->perPage(),
                    'total' => $photos->total(),
                    'last_page' => $photos->lastPage(),
                ],
            ],
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $photos = Photo::query()
            ->with(['photographer.photographerProfile', 'category'])
            ->approved()
            ->public()
            ->latest()
            ->limit($request->input('limit', 12))
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'photos' => PhotoResource::collection($photos),
            ],
        ]);
    }

    public function popular(Request $request): JsonResponse
    {
        $photos = Photo::query()
            ->with(['photographer.photographerProfile', 'category'])
            ->approved()
            ->public()
            ->orderByDesc('views_count')
            ->limit($request->input('limit', 12))
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'photos' => PhotoResource::collection($photos),
            ],
        ]);
    }

    public function similar(Photo $photo, Request $request): JsonResponse
    {
        $similarPhotos = Photo::query()
            ->with(['photographer.photographerProfile', 'category'])
            ->approved()
            ->public()
            ->where('id', '!=', $photo->id)
            ->where(function ($query) use ($photo) {
                $query->where('category_id', $photo->category_id)
                    ->orWhere('photographer_id', $photo->photographer_id);
            })
            ->inRandomOrder()
            ->limit($request->input('limit', 6))
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'photos' => PhotoResource::collection($similarPhotos),
            ],
        ]);
    }

    public function incrementView(Photo $photo): JsonResponse
    {
        $photo->incrementViews();

        return response()->json([
            'success' => true,
            'message' => 'Vue enregistrée',
        ]);
    }

    private function userOwnsPhoto(Photo $photo): bool
    {
        return auth()->check() && auth()->id() === $photo->photographer_id;
    }
}
```

### 10.3 SearchController

**Fichier**: `app/Http/Controllers/Api/SearchController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photo\SearchPhotoRequest;
use App\Http\Resources\PhotoResource;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function search(SearchPhotoRequest $request): JsonResponse
    {
        $query = Photo::query()
            ->with(['photographer.photographerProfile', 'category'])
            ->approved()
            ->public();

        // Recherche full-text
        if ($request->has('query')) {
            $query->search($request->query);
        }

        // Filtres
        if ($request->has('categories')) {
            $query->filterByCategory($request->categories);
        }

        if ($request->has('photographer_id')) {
            $query->filterByPhotographer($request->photographer_id);
        }

        if ($request->has('min_price') || $request->has('max_price')) {
            $query->filterByPrice($request->min_price, $request->max_price);
        }

        if ($request->has('orientation')) {
            $query->filterByOrientation($request->orientation);
        }

        // Tri
        $query->sortBy($request->input('sort_by', 'date'));

        // Pagination
        $photos = $query->paginate($request->input('per_page', 24));

        return response()->json([
            'success' => true,
            'data' => [
                'photos' => PhotoResource::collection($photos->items()),
                'meta' => [
                    'current_page' => $photos->currentPage(),
                    'per_page' => $photos->perPage(),
                    'total' => $photos->total(),
                    'last_page' => $photos->lastPage(),
                    'from' => $photos->firstItem(),
                    'to' => $photos->lastItem(),
                ],
            ],
        ]);
    }
}
```

### 10.4 Photographer/PhotoController

**Fichier**: `app/Http/Controllers/Api/Photographer/PhotoController.php`

```php
<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photo\StorePhotoRequest;
use App\Http\Requests\Photo\UpdatePhotoRequest;
use App\Http\Resources\PhotoResource;
use App\Jobs\ProcessPhotoUpload;
use App\Models\Photo;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhotoController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageProcessingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $photos = auth()->user()
            ->photos()
            ->with('category')
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate($request->input('per_page', 24));

        return response()->json([
            'success' => true,
            'data' => [
                'photos' => PhotoResource::collection($photos->items()),
                'meta' => [
                    'current_page' => $photos->currentPage(),
                    'per_page' => $photos->perPage(),
                    'total' => $photos->total(),
                    'last_page' => $photos->lastPage(),
                ],
            ],
        ]);
    }

    public function store(StorePhotoRequest $request): JsonResponse
    {
        $uploadedPhotos = [];

        DB::transaction(function () use ($request, &$uploadedPhotos) {
            foreach ($request->file('photos') as $file) {
                // Stocker temporairement
                $tempPath = $file->store('temp');

                // Dispatch job pour traitement asynchrone
                ProcessPhotoUpload::dispatch(
                    $tempPath,
                    auth()->id(),
                    $request->validated()
                );

                // Créer entrée photo avec statut pending
                $photo = Photo::create([
                    'photographer_id' => auth()->id(),
                    'category_id' => $request->category_id,
                    'title' => $request->title,
                    'description' => $request->description,
                    'tags' => explode(',', $request->tags),
                    'price_standard' => $request->price_standard,
                    'price_extended' => $request->price_extended,
                    'location' => $request->location,
                    'status' => 'pending',
                    'is_public' => false,
                    // Autres champs seront remplis par le job
                ]);

                $uploadedPhotos[] = $photo;
            }
        });

        return response()->json([
            'success' => true,
            'message' => count($uploadedPhotos) . ' photo(s) uploadée(s) avec succès',
            'data' => [
                'photos' => PhotoResource::collection($uploadedPhotos),
            ],
        ], 201);
    }

    public function show(Photo $photo): JsonResponse
    {
        $this->authorize('view', $photo);

        return response()->json([
            'success' => true,
            'data' => [
                'photo' => new PhotoResource($photo->load('category')),
            ],
        ]);
    }

    public function update(UpdatePhotoRequest $request, Photo $photo): JsonResponse
    {
        $this->authorize('update', $photo);

        $photo->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Photo mise à jour avec succès',
            'data' => [
                'photo' => new PhotoResource($photo),
            ],
        ]);
    }

    public function destroy(Photo $photo): JsonResponse
    {
        $this->authorize('delete', $photo);

        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo supprimée avec succès',
        ]);
    }
}
```

### 10.5 OrderController

**Fichier**: `app/Http/Controllers/Api/OrderController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\PayOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = auth()->user()
            ->orders()
            ->with('items.photo')
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => OrderResource::collection($orders->items()),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
            ],
        ]);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = DB::transaction(function () use ($request) {
            // Créer la commande
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => auth()->id(),
                'subtotal' => $request->subtotal,
                'tax' => $request->tax ?? 0,
                'discount' => $request->discount ?? 0,
                'total' => $request->total,
                'payment_method' => $request->payment_method,
                'billing_email' => $request->billing_email,
                'billing_first_name' => $request->billing_first_name,
                'billing_last_name' => $request->billing_last_name,
                'billing_phone' => $request->billing_phone,
                'payment_status' => 'pending',
            ]);

            // Créer les items
            foreach ($request->items as $item) {
                $photo = Photo::findOrFail($item['photo_id']);

                $price = $item['license_type'] === 'extended'
                    ? $photo->price_extended
                    : $photo->price_standard;

                $commissionRate = $photo->photographer->photographerProfile->commission_rate;
                $commissionAmount = $price * $commissionRate;
                $photographerAmount = $price - $commissionAmount;

                $order->items()->create([
                    'photo_id' => $photo->id,
                    'photographer_id' => $photo->photographer_id,
                    'photo_title' => $photo->title,
                    'photo_preview' => $photo->preview_url,
                    'photographer_name' => $photo->photographer->full_name,
                    'license_type' => $item['license_type'],
                    'price' => $price,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'photographer_amount' => $photographerAmount,
                ]);
            }

            return $order->load('items');
        });

        return response()->json([
            'success' => true,
            'message' => 'Commande créée avec succès',
            'data' => [
                'order' => new OrderResource($order),
            ],
        ], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => new OrderResource($order->load('items.photo')),
            ],
        ]);
    }

    public function processPayment(PayOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('pay', $order);

        if (!$order->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande a déjà été traitée',
            ], 400);
        }

        try {
            $result = $this->paymentService->processPayment(
                $order,
                $request->payment_method,
                $request->payment_details
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Paiement effectué avec succès',
                    'data' => [
                        'order' => new OrderResource($order->fresh()->load('items')),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 402);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du paiement',
                'errors' => [
                    'payment' => [$e->getMessage()],
                ],
            ], 500);
        }
    }

    public function downloadInvoice(Order $order)
    {
        $this->authorize('view', $order);

        if (!$order->invoice_url) {
            return response()->json([
                'success' => false,
                'message' => 'Facture non disponible',
            ], 404);
        }

        return redirect($order->invoice_url);
    }
}
```

---

*Ce document est volontairement long et détaillé. Il continue avec les sections restantes...*

**SECTIONS RESTANTES À INCLURE:**

## 11. REQUESTS (VALIDATION)
## 12. MIDDLEWARES
## 13. SERVICES
## 14. JOBS & QUEUES
## 15. NOTIFICATIONS
## 16. PAIEMENTS
## 17. EMAILS
## 18. COMMANDES ARTISAN
## 19. TESTS
## 20. CONFIGURATION & DÉPLOIEMENT

**Note**: Le document complet fait environ 8000+ lignes avec toutes les sections détaillées. Je vais créer une version plus condensée mais complète pour ne pas dépasser les limites.

Voulez-vous que je:
1. Continue avec toutes les sections en détail (document très long)
2. Crée une version condensée mais complète
3. Sépare en plusieurs fichiers markdown

Que préférez-vous?