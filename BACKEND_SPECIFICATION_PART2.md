# SPÉCIFICATION BACKEND LARAVEL 12 - PARTIE 2

## 11. REQUESTS (VALIDATION)

### 11.1 LoginRequest

**Fichier**: `app/Http/Requests/Auth/LoginRequest.php`

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
            'remember_me' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'password.required' => 'Le mot de passe est requis',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères',
        ];
    }
}
```

### 11.2 RegisterRequest

**Fichier**: `app/Http/Requests/Auth/RegisterRequest.php`

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:50'],
            'last_name' => ['required', 'string', 'min:2', 'max:50'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()],
            'account_type' => ['required', 'in:buyer,photographer'],
            'phone' => ['nullable', 'regex:/^\+226\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est requis',
            'first_name.min' => 'Le prénom doit contenir au moins 2 caractères',
            'last_name.required' => 'Le nom est requis',
            'email.required' => 'L\'email est requis',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.required' => 'Le mot de passe est requis',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
            'account_type.required' => 'Le type de compte est requis',
            'account_type.in' => 'Le type de compte doit être buyer ou photographer',
            'phone.regex' => 'Le format du téléphone est invalide (ex: +226 70 12 34 56)',
        ];
    }
}
```

### 11.3 StorePhotoRequest

**Fichier**: `app/Http/Requests/Photo/StorePhotoRequest.php`

```php
<?php

namespace App\Http\Requests\Photo;

use Illuminate\Foundation\Http\FormRequest;

class StorePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPhotographer();
    }

    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:51200'], // 50MB
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'exists:categories,id'],
            'tags' => ['required', 'string', function ($attribute, $value, $fail) {
                $tags = array_filter(array_map('trim', explode(',', $value)));
                if (count($tags) < 3) {
                    $fail('Vous devez fournir au moins 3 tags');
                }
                if (count($tags) > 20) {
                    $fail('Vous ne pouvez pas fournir plus de 20 tags');
                }
            }],
            'price_standard' => ['required', 'integer', 'min:500'], // en FCFA
            'price_extended' => ['required', 'integer', 'gte:price_standard', function ($attribute, $value, $fail) {
                $priceStandard = $this->input('price_standard');
                if ($value < ($priceStandard * 2)) {
                    $fail('Le prix extended doit être au moins le double du prix standard');
                }
            }],
            'location' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'photos.required' => 'Vous devez uploader au moins une photo',
            'photos.*.required' => 'Chaque photo est requise',
            'photos.*.image' => 'Le fichier doit être une image',
            'photos.*.mimes' => 'Formats acceptés: JPG, JPEG, PNG',
            'photos.*.max' => 'La taille maximale est de 50MB',
            'title.required' => 'Le titre est requis',
            'title.min' => 'Le titre doit contenir au moins 5 caractères',
            'title.max' => 'Le titre ne peut pas dépasser 200 caractères',
            'category_id.required' => 'La catégorie est requise',
            'category_id.exists' => 'Cette catégorie n\'existe pas',
            'tags.required' => 'Les tags sont requis',
            'price_standard.required' => 'Le prix standard est requis',
            'price_standard.min' => 'Le prix standard minimum est de 500 FCFA',
            'price_extended.required' => 'Le prix extended est requis',
            'price_extended.gte' => 'Le prix extended doit être supérieur ou égal au prix standard',
        ];
    }
}
```

### 11.4 UpdatePhotoRequest

**Fichier**: `app/Http/Requests/Photo/UpdatePhotoRequest.php`

```php
<?php

namespace App\Http\Requests\Photo;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('photo')->photographer_id;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'min:5', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'tags' => ['sometimes', 'string', function ($attribute, $value, $fail) {
                $tags = array_filter(array_map('trim', explode(',', $value)));
                if (count($tags) < 3) {
                    $fail('Vous devez fournir au moins 3 tags');
                }
                if (count($tags) > 20) {
                    $fail('Vous ne pouvez pas fournir plus de 20 tags');
                }
            }],
            'price_standard' => ['sometimes', 'integer', 'min:500'], // en FCFA
            'price_extended' => ['sometimes', 'integer', 'gte:price_standard', function ($attribute, $value, $fail) {
                $priceStandard = $this->input('price_standard', $this->route('photo')->price_standard);
                if ($value < ($priceStandard * 2)) {
                    $fail('Le prix extended doit être au moins le double du prix standard');
                }
            }],
            'location' => ['nullable', 'string', 'max:100'],
        ];
    }
}
```

### 11.5 SearchPhotoRequest

**Fichier**: `app/Http/Requests/Photo/SearchPhotoRequest.php`

```php
<?php

namespace App\Http\Requests\Photo;

use Illuminate\Foundation\Http\FormRequest;

class SearchPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:200'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'photographer_id' => ['nullable', 'exists:users,id'],
            'min_price' => ['nullable', 'integer', 'min:0'], // en FCFA
            'max_price' => ['nullable', 'integer', 'gte:min_price'], // en FCFA
            'orientation' => ['nullable', 'in:landscape,portrait,square'],
            'sort_by' => ['nullable', 'in:popularity,date,price_asc,price_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
```

### 11.6 CreateOrderRequest

**Fichier**: `app/Http/Requests/Order/CreateOrderRequest.php`

```php
<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.photo_id' => ['required', 'exists:photos,id'],
            'items.*.license_type' => ['required', 'in:standard,extended'],
            'subtotal' => ['required', 'integer', 'min:0'], // en FCFA
            'tax' => ['nullable', 'integer', 'min:0'], // en FCFA
            'discount' => ['nullable', 'integer', 'min:0'], // en FCFA
            'total' => ['required', 'integer', 'min:0'], // en FCFA
            'payment_method' => ['required', 'in:mobile_money,card'],
            'billing_email' => ['required', 'email'],
            'billing_first_name' => ['required', 'string'],
            'billing_last_name' => ['required', 'string'],
            'billing_phone' => ['required', 'string', 'regex:/^\+226\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Votre panier est vide',
            'items.min' => 'Votre panier doit contenir au moins un article',
            'items.*.photo_id.exists' => 'Une des photos n\'existe pas',
            'items.*.license_type.in' => 'Type de licence invalide',
            'billing_email.required' => 'L\'email de facturation est requis',
            'billing_email.email' => 'L\'email de facturation doit être valide',
            'billing_phone.regex' => 'Le format du téléphone est invalide',
        ];
    }
}
```

### 11.7 PayOrderRequest

**Fichier**: `app/Http/Requests/Order/PayOrderRequest.php`

```php
<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PayOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('order')->user_id;
    }

    public function rules(): array
    {
        // CinetPay gère le paiement côté serveur
        // Pas besoin de détails de carte côté client
        $rules = [
            'payment_method' => ['required', 'in:mobile_money,card'],
        ];

        // Pour Mobile Money, on peut demander le provider et le numéro
        if ($this->input('payment_method') === 'mobile_money') {
            $rules['payment_provider'] = ['nullable', 'string', 'in:ORANGE,MTN,MOOV,WAVE'];
            $rules['phone'] = ['nullable', 'string', 'regex:/^\+226\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'La méthode de paiement est requise',
            'payment_provider.in' => 'Le fournisseur Mobile Money n\'est pas supporté',
            'phone.regex' => 'Le format du téléphone est invalide (ex: +226 70 12 34 56)',
        ];
    }
}
```

### 11.8 CreateWithdrawalRequest

**Fichier**: `app/Http/Requests/Withdrawal/CreateWithdrawalRequest.php`

```php
<?php

namespace App\Http\Requests\Withdrawal;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\RevenueService;

class CreateWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPhotographer();
    }

    public function rules(): array
    {
        $rules = [
            'amount' => [
                'required',
                'integer',
                'min:5000', // Montant minimum 5000 FCFA
                function ($attribute, $value, $fail) {
                    $revenueService = app(RevenueService::class);
                    $availableBalance = $revenueService->getAvailableBalance($this->user()->id);

                    if ($value > $availableBalance) {
                        $fail('Le montant demandé dépasse votre solde disponible');
                    }
                },
            ],
            'payment_method' => ['required', 'in:mobile_money,bank_transfer'],
            'payment_details' => ['required', 'array'],
        ];

        if ($this->input('payment_method') === 'mobile_money') {
            $rules['payment_details.provider'] = ['required', 'in:ORANGE,MTN,MOOV,WAVE'];
            $rules['payment_details.phone'] = ['required', 'string', 'regex:/^\+226\s?\d{2}\s?\d{2}\s?\d{2}\s?\d{2}$/'];
            $rules['payment_details.name'] = ['required', 'string'];
        } elseif ($this->input('payment_method') === 'bank_transfer') {
            $rules['payment_details.bank_name'] = ['required', 'string'];
            $rules['payment_details.account_number'] = ['required', 'string'];
            $rules['payment_details.account_name'] = ['required', 'string'];
            $rules['payment_details.iban'] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant est requis',
            'amount.min' => 'Le montant minimum est de 5000 FCFA',
            'payment_method.required' => 'La méthode de paiement est requise',
            'payment_details.provider.required' => 'Le fournisseur Mobile Money est requis',
            'payment_details.phone.required' => 'Le numéro de téléphone est requis',
            'payment_details.phone.regex' => 'Le format du téléphone est invalide',
            'payment_details.bank_name.required' => 'Le nom de la banque est requis',
            'payment_details.account_number.required' => 'Le numéro de compte est requis',
            'payment_details.account_name.required' => 'Le nom du titulaire est requis',
        ];
    }
}
```

---

## 12. MIDDLEWARES

### 12.1 CheckRole Middleware

**Fichier**: `app/Http/Middleware/CheckRole.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        if (auth()->user()->account_type !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        return $next($request);
    }
}
```

### 12.2 CheckPhotographer Middleware

**Fichier**: `app/Http/Middleware/CheckPhotographer.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPhotographer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        if (!auth()->user()->isPhotographer()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux photographes',
            ], 403);
        }

        // Vérifier que le profil photographe est approuvé
        $profile = auth()->user()->photographerProfile;
        if (!$profile || !$profile->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre profil photographe doit être validé par un administrateur',
            ], 403);
        }

        return $next($request);
    }
}
```

### 12.3 CheckAdmin Middleware

**Fichier**: `app/Http/Middleware/CheckAdmin.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux administrateurs',
            ], 403);
        }

        return $next($request);
    }
}
```

### 12.4 TrackPhotoView Middleware

**Fichier**: `app/Http/Middleware/TrackPhotoView.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class TrackPhotoView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Incrémenter les vues uniquement si c'est une visite unique dans les dernières 24h
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
}
```

### 12.5 Enregistrement des Middlewares

**Fichier**: `bootstrap/app.php` (Laravel 11+)

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'photographer' => \App\Http\Middleware\CheckPhotographer::class,
            'admin' => \App\Http\Middleware\CheckAdmin::class,
            'track.view' => \App\Http\Middleware\TrackPhotoView::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## 13. SERVICES

### 13.1 PhotoService

**Fichier**: `app/Services/PhotoService.php`

```php
<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\User;
use App\Jobs\GenerateWatermark;
use App\Jobs\ExtractExifData;
use Illuminate\Support\Facades\DB;

class PhotoService
{
    public function __construct(
        private ImageProcessingService $imageProcessingService,
        private StorageService $storageService
    ) {}

    public function approvePhoto(Photo $photo, User $moderator): Photo
    {
        DB::transaction(function () use ($photo, $moderator) {
            $photo->approve($moderator);

            // Mettre à jour le compteur de photos de la catégorie
            $photo->category->updatePhotoCount();

            // Notifier le photographe
            $photo->photographer->notify(new \App\Notifications\PhotoApprovedNotification($photo));
        });

        return $photo->fresh();
    }

    public function rejectPhoto(Photo $photo, User $moderator, string $reason): Photo
    {
        DB::transaction(function () use ($photo, $moderator, $reason) {
            $photo->reject($moderator, $reason);

            // Notifier le photographe
            $photo->photographer->notify(new \App\Notifications\PhotoRejectedNotification($photo, $reason));
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

    public function unfeaturePhoto(Photo $photo): Photo
    {
        $photo->update([
            'featured' => false,
            'featured_until' => null,
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
}
```

### 13.2 PaymentService

**Fichier**: `app/Services/PaymentService.php`

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Jobs\GenerateInvoicePdf;
use App\Jobs\SendOrderConfirmationEmail;
use App\Notifications\NewSaleNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentService
{
    private const COMMISSION_RATE = 0.20; // 20%

    public function __construct(
        private RevenueService $revenueService
    ) {}

    public function processPayment(Order $order, string $paymentMethod, ?string $paymentProvider = null, ?string $phone = null): array
    {
        try {
            // Initialiser le paiement via CinetPay
            $cinetpayData = [
                'apikey' => config('services.cinetpay.api_key'),
                'site_id' => config('services.cinetpay.site_id'),
                'transaction_id' => $order->order_number,
                'amount' => $order->total, // en FCFA (integer)
                'currency' => 'XOF',
                'description' => 'Achat photos AfroLens - Commande ' . $order->order_number,
                'notify_url' => route('webhooks.cinetpay'),
                'return_url' => config('app.frontend_url') . '/orders/' . $order->id,
                'channels' => $this->getCinetPayChannels($paymentMethod, $paymentProvider),
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ],
            ];

            // Ajouter le numéro de téléphone si fourni (pour Mobile Money)
            if ($phone) {
                $cinetpayData['customer_phone_number'] = $phone;
            }

            $response = Http::post(config('services.cinetpay.api_url') . '/payment', $cinetpayData);

            if ($response->successful() && $response->json('code') === '201') {
                $data = $response->json('data');

                // Mettre à jour la commande avec l'ID de transaction CinetPay
                $order->update([
                    'cinetpay_transaction_id' => $data['payment_token'],
                ]);

                return [
                    'success' => true,
                    'message' => 'Paiement initialisé avec succès',
                    'payment_url' => $data['payment_url'],
                    'payment_token' => $data['payment_token'],
                ];
            }

            $order->markAsFailed();

            return [
                'success' => false,
                'message' => $response->json('message', 'Échec de l\'initialisation du paiement'),
            ];

        } catch (\Exception $e) {
            $order->markAsFailed();

            return [
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement: ' . $e->getMessage(),
            ];
        }
    }

    private function getCinetPayChannels(string $paymentMethod, ?string $provider = null): string
    {
        // Déterminer les canaux CinetPay selon la méthode de paiement
        if ($paymentMethod === 'mobile_money') {
            // Si un provider spécifique est demandé
            if ($provider) {
                return match ($provider) {
                    'ORANGE' => 'ORANGE_MONEY_BF',
                    'MTN' => 'MTN_MONEY_BF',
                    'MOOV' => 'MOOV_MONEY_BF',
                    'WAVE' => 'WAVE_BF',
                    default => 'ALL', // Tous les Mobile Money si non reconnu
                };
            }
            return 'ALL'; // Tous les Mobile Money
        }

        if ($paymentMethod === 'card') {
            return 'CARD'; // Paiement par carte
        }

        return 'ALL'; // Par défaut, tous les moyens de paiement
    }

    public function checkPaymentStatus(Order $order): array
    {
        try {
            $response = Http::post(config('services.cinetpay.api_url') . '/check', [
                'apikey' => config('services.cinetpay.api_key'),
                'site_id' => config('services.cinetpay.site_id'),
                'transaction_id' => $order->order_number,
            ]);

            if ($response->successful()) {
                $data = $response->json('data');

                return [
                    'success' => true,
                    'status' => $data['status'],
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de vérifier le statut du paiement',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }

    private function completeOrder(Order $order, string $transactionId): void
    {
        DB::transaction(function () use ($order, $transactionId) {
            // Marquer la commande comme complétée
            $order->markAsCompleted($transactionId);

            // Générer les URLs de téléchargement
            foreach ($order->items as $item) {
                $item->update([
                    'download_url' => $item->generateDownloadUrl(),
                ]);
            }

            // Mettre à jour les statistiques des photos
            foreach ($order->items as $item) {
                $item->photo->incrementSales();
            }

            // Enregistrer les revenus pour chaque photographe
            $this->revenueService->recordSales($order);

            // Notifier les photographes des ventes
            $this->notifyPhotographers($order);

            // Dispatcher les jobs
            GenerateInvoicePdf::dispatch($order);
            SendOrderConfirmationEmail::dispatch($order);
        });
    }

    private function notifyPhotographers(Order $order): void
    {
        $photographerSales = [];

        foreach ($order->items as $item) {
            $photographerId = $item->photographer_id;

            if (!isset($photographerSales[$photographerId])) {
                $photographerSales[$photographerId] = [
                    'photographer' => $item->photographer,
                    'items' => [],
                ];
            }

            $photographerSales[$photographerId]['items'][] = $item;
        }

        foreach ($photographerSales as $sale) {
            $sale['photographer']->notify(
                new NewSaleNotification($order, $sale['items'])
            );
        }
    }
}
```

### 13.3 RevenueService

**Fichier**: `app/Services/RevenueService.php`

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Revenue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueService
{
    private const SECURITY_PERIOD_DAYS = 30;
    private const COMMISSION_RATE = 0.20;

    public function recordSales(Order $order): void
    {
        $month = Carbon::parse($order->paid_at)->startOfMonth();

        foreach ($order->items as $item) {
            $this->updateRevenue(
                $item->photographer_id,
                $month,
                $item->price,
                $item->photographer_amount
            );
        }
    }

    private function updateRevenue(string $photographerId, Carbon $month, int $saleAmount, int $photographerAmount): void
    {
        $revenue = Revenue::firstOrCreate(
            [
                'photographer_id' => $photographerId,
                'month' => $month,
            ],
            [
                'total_sales' => 0,
                'commission' => 0,
                'net_revenue' => 0,
                'available_balance' => 0,
                'pending_balance' => 0,
                'withdrawn' => 0,
                'sales_count' => 0,
                'photos_sold' => 0,
            ]
        );

        // Commission: 20% du montant de vente (en FCFA)
        $commission = (int) round($saleAmount * self::COMMISSION_RATE);

        $revenue->increment('total_sales', $saleAmount);
        $revenue->increment('commission', $commission);
        $revenue->increment('net_revenue', $photographerAmount);
        $revenue->increment('pending_balance', $photographerAmount);
        $revenue->increment('sales_count');
        $revenue->increment('photos_sold');
        $revenue->touch('updated_at');
    }

    public function calculateAvailableBalance(string $photographerId): int
    {
        $securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

        return (int) Revenue::where('photographer_id', $photographerId)
            ->where('month', '<=', $securityDate->startOfMonth())
            ->sum('pending_balance');
    }

    public function getAvailableBalance(string $photographerId): int
    {
        return $this->calculateAvailableBalance($photographerId);
    }

    public function getPendingBalance(string $photographerId): int
    {
        $securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

        return (int) Revenue::where('photographer_id', $photographerId)
            ->where('month', '>', $securityDate->startOfMonth())
            ->sum('pending_balance');
    }

    public function getTotalWithdrawn(string $photographerId): int
    {
        return (int) Revenue::where('photographer_id', $photographerId)
            ->sum('withdrawn');
    }

    public function processWithdrawal(string $photographerId, int $amount): void
    {
        $securityDate = Carbon::now()->subDays(self::SECURITY_PERIOD_DAYS);

        DB::transaction(function () use ($photographerId, $amount, $securityDate) {
            $revenues = Revenue::where('photographer_id', $photographerId)
                ->where('month', '<=', $securityDate->startOfMonth())
                ->where('pending_balance', '>', 0)
                ->orderBy('month')
                ->lockForUpdate()
                ->get();

            $remainingAmount = $amount;

            foreach ($revenues as $revenue) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $deduction = min($remainingAmount, $revenue->pending_balance);

                $revenue->decrement('pending_balance', $deduction);
                $revenue->decrement('available_balance', $deduction);
                $revenue->increment('withdrawn', $deduction);

                $remainingAmount -= $deduction;
            }
        });
    }

    public function getMonthlyRevenues(string $photographerId, int $months = 12): array
    {
        $startMonth = Carbon::now()->subMonths($months)->startOfMonth();

        return Revenue::where('photographer_id', $photographerId)
            ->where('month', '>=', $startMonth)
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getSummary(string $photographerId): array
    {
        return [
            'available_balance' => $this->getAvailableBalance($photographerId),
            'pending_balance' => $this->getPendingBalance($photographerId),
            'total_withdrawn' => $this->getTotalWithdrawn($photographerId),
            'total_revenue' => Revenue::where('photographer_id', $photographerId)->sum('net_revenue'),
            'total_sales' => Revenue::where('photographer_id', $photographerId)->sum('sales_count'),
        ];
    }
}
```

### 13.4 NotificationService

**Fichier**: `app/Services/NotificationService.php`

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;

class NotificationService
{
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
}
```

### 13.5 InvoiceService

**Fichier**: `app/Services/InvoiceService.php`

```php
<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function __construct(
        private StorageService $storageService
    ) {}

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
}
```

---

## 14. JOBS & QUEUES

### 14.1 ProcessPhotoUpload Job

**Fichier**: `app/Jobs/ProcessPhotoUpload.php`

```php
<?php

namespace App\Jobs;

use App\Models\Photo;
use App\Services\ImageProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessPhotoUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function __construct(
        private string $tempPath,
        private string $photographerId,
        private array $metadata
    ) {}

    public function handle(ImageProcessingService $imageProcessingService): void
    {
        try {
            // Traiter l'image
            $result = $imageProcessingService->processUploadedPhoto(
                Storage::path($this->tempPath),
                $this->photographerId
            );

            // Mettre à jour la photo dans la BDD
            $photo = Photo::where('photographer_id', $this->photographerId)
                ->where('status', 'pending')
                ->whereNull('original_url')
                ->latest()
                ->first();

            if ($photo) {
                $photo->update([
                    'original_url' => $result['original_url'],
                    'preview_url' => $result['preview_url'],
                    'thumbnail_url' => $result['thumbnail_url'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                    'file_size' => $result['file_size'],
                    'format' => $result['format'],
                    'color_palette' => $result['color_palette'],
                ]);

                // Dispatcher le job d'extraction EXIF
                ExtractExifData::dispatch($photo);
            }

            // Nettoyer le fichier temporaire
            Storage::delete($this->tempPath);

        } catch (\Exception $e) {
            \Log::error('Erreur traitement photo: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Échec traitement photo: ' . $exception->getMessage());
        Storage::delete($this->tempPath);
    }
}
```

### 14.2 ExtractExifData Job

**Fichier**: `app/Jobs/ExtractExifData.php`

```php
<?php

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtractExifData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Photo $photo
    ) {}

    public function handle(): void
    {
        try {
            // Télécharger temporairement l'image depuis S3
            $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.jpg';
            file_put_contents($tempPath, file_get_contents($this->photo->original_url));

            $exif = @exif_read_data($tempPath);

            if ($exif) {
                $this->photo->update([
                    'camera' => $exif['Model'] ?? null,
                    'lens' => $exif['LensModel'] ?? null,
                    'iso' => $exif['ISOSpeedRatings'] ?? null,
                    'aperture' => isset($exif['FNumber']) ? 'f/' . $exif['FNumber'] : null,
                    'shutter_speed' => $exif['ExposureTime'] ?? null,
                    'focal_length' => isset($exif['FocalLength']) ? (int) $exif['FocalLength'] : null,
                    'taken_at' => isset($exif['DateTimeOriginal']) ?
                        \Carbon\Carbon::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']) : null,
                ]);
            }

            // Nettoyer
            unlink($tempPath);

        } catch (\Exception $e) {
            \Log::error('Erreur extraction EXIF: ' . $e->getMessage());
        }
    }
}
```

### 14.3 GenerateInvoicePdf Job

**Fichier**: `app/Jobs/GenerateInvoicePdf.php`

```php
<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoicePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Order $order
    ) {}

    public function handle(InvoiceService $invoiceService): void
    {
        try {
            $invoiceService->generateInvoice($this->order);
        } catch (\Exception $e) {
            \Log::error('Erreur génération facture: ' . $e->getMessage());
            throw $e;
        }
    }
}
```

### 14.4 SendOrderConfirmationEmail Job

**Fichier**: `app/Jobs/SendOrderConfirmationEmail.php`

```php
<?php

namespace App\Jobs;

use App\Models\Order;
use App\Mail\OrderConfirmationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Order $order
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->order->billing_email)
                ->send(new OrderConfirmationMail($this->order));
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email confirmation: ' . $e->getMessage());
        }
    }
}
```

### 14.5 CalculateMonthlyRevenue Job

**Fichier**: `app/Jobs/CalculateMonthlyRevenue.php`

```php
<?php

namespace App\Jobs;

use App\Models\Revenue;
use App\Services\RevenueService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateMonthlyRevenue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(RevenueService $revenueService): void
    {
        try {
            // Mettre à jour les soldes disponibles
            $securityDate = Carbon::now()->subDays(30);

            Revenue::where('month', '<=', $securityDate->startOfMonth())
                ->where('pending_balance', '>', 0)
                ->each(function ($revenue) {
                    $revenue->update([
                        'available_balance' => $revenue->available_balance + $revenue->pending_balance,
                        'pending_balance' => 0,
                    ]);
                });

            \Log::info('Revenus mensuels calculés avec succès');

        } catch (\Exception $e) {
            \Log::error('Erreur calcul revenus: ' . $e->getMessage());
        }
    }
}
```

---

## 15. NOTIFICATIONS

### 15.1 PhotoApprovedNotification

**Fichier**: `app/Notifications/PhotoApprovedNotification.php`

```php
<?php

namespace App\Notifications;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhotoApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Photo $photo
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

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
            ->subject('Photo approuvée - AfroLens')
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line("Votre photo \"{$this->photo->title}\" a été approuvée!")
            ->line('Elle est maintenant visible par tous les utilisateurs de la plateforme.')
            ->action('Voir ma photo', url('/photographer/photos/' . $this->photo->id))
            ->line('Merci d\'utiliser AfroLens!');
    }
}
```

### 15.2 PhotoRejectedNotification

**Fichier**: `app/Notifications/PhotoRejectedNotification.php`

```php
<?php

namespace App\Notifications;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhotoRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Photo $photo,
        private string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'photo_rejected',
            'title' => 'Photo rejetée',
            'message' => "Votre photo \"{$this->photo->title}\" a été rejetée. Raison: {$this->reason}",
            'data' => [
                'photo_id' => $this->photo->id,
                'photo_title' => $this->photo->title,
                'reason' => $this->reason,
            ],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Photo rejetée - AfroLens')
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line("Malheureusement, votre photo \"{$this->photo->title}\" a été rejetée.")
            ->line("Raison: {$this->reason}")
            ->line('Vous pouvez télécharger une nouvelle photo en respectant nos conditions.')
            ->action('Uploader une nouvelle photo', url('/photographer/upload'))
            ->line('Si vous avez des questions, n\'hésitez pas à nous contacter.');
    }
}
```

### 15.3 NewSaleNotification

**Fichier**: `app/Notifications/NewSaleNotification.php`

```php
<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSaleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Order $order,
        private array $items
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        $totalEarned = collect($this->items)->sum('photographer_amount');
        $photoCount = count($this->items);

        return [
            'type' => 'new_sale',
            'title' => 'Nouvelle vente',
            'message' => "Vous avez vendu {$photoCount} photo(s) pour un montant de {$totalEarned} FCFA",
            'data' => [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'photo_count' => $photoCount,
                'total_earned' => $totalEarned,
            ],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $totalEarned = collect($this->items)->sum('photographer_amount');
        $photoCount = count($this->items);

        return (new MailMessage)
            ->subject('Nouvelle vente - AfroLens')
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line("Félicitations! Vous avez réalisé une nouvelle vente.")
            ->line("{$photoCount} photo(s) vendue(s)")
            ->line("Montant gagné: {$totalEarned} FCFA")
            ->action('Voir mes revenus', url('/photographer/revenue'))
            ->line('Merci d\'utiliser AfroLens!');
    }
}
```

### 15.4 WithdrawalApprovedNotification

**Fichier**: `app/Notifications/WithdrawalApprovedNotification.php`

```php
<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Withdrawal $withdrawal
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'withdrawal_approved',
            'title' => 'Retrait approuvé',
            'message' => "Votre demande de retrait de {$this->withdrawal->amount} FCFA a été approuvée et sera traitée sous 24-48h.",
            'data' => [
                'withdrawal_id' => $this->withdrawal->id,
                'amount' => $this->withdrawal->amount,
            ],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Retrait approuvé - AfroLens')
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line("Votre demande de retrait de {$this->withdrawal->amount} FCFA a été approuvée.")
            ->line('Le montant sera transféré sur votre compte dans les 24-48h.')
            ->action('Voir mes retraits', url('/photographer/revenue'))
            ->line('Merci d\'utiliser AfroLens!');
    }
}
```

---

## 16. PAIEMENTS

### 16.1 Configuration Services

**Fichier**: `config/services.php`

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

### 16.2 Webhook Controller

**Fichier**: `app/Http/Controllers/Api/WebhookController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleCinetPayWebhook(Request $request)
    {
        Log::info('CinetPay Webhook', $request->all());

        // Vérifier la signature du webhook pour sécurité
        $token = $request->input('cpm_trans_id');
        $transactionId = $request->input('cpm_custom');
        $amount = $request->input('cpm_amount');
        $status = $request->input('cpm_result');

        // Vérifier la signature
        $signature = $request->input('signature');
        $apiKey = config('services.cinetpay.api_key');
        $siteId = config('services.cinetpay.site_id');

        // Calculer la signature attendue
        $expectedSignature = hash('sha256', $siteId . $transactionId . $apiKey);

        if ($signature !== $expectedSignature) {
            Log::warning('CinetPay webhook signature mismatch', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Trouver la commande
        $order = Order::where('order_number', $transactionId)->first();

        if (!$order) {
            Log::error('CinetPay webhook: Order not found', ['transaction_id' => $transactionId]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Traiter selon le statut
        if ($status === '00' && $order->isPending()) {
            // Paiement réussi
            $order->update([
                'payment_status' => 'completed',
                'payment_id' => $token,
                'cinetpay_transaction_id' => $token,
                'payment_provider' => $request->input('payment_method'),
                'paid_at' => now(),
            ]);

            // Dispatcher les jobs de post-paiement
            app(PaymentService::class)->completeOrder($order, $token);

            Log::info('CinetPay payment completed', [
                'order_id' => $order->id,
                'transaction_id' => $token,
            ]);

        } elseif ($status !== '00') {
            // Paiement échoué
            $order->markAsFailed();

            Log::warning('CinetPay payment failed', [
                'order_id' => $order->id,
                'status' => $status,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    public function handleCinetPayReturn(Request $request, string $orderId)
    {
        // Page de retour après paiement
        $order = Order::findOrFail($orderId);

        // Vérifier le statut du paiement auprès de CinetPay
        $paymentService = app(PaymentService::class);
        $result = $paymentService->checkPaymentStatus($order);

        if ($result['success'] && $result['status'] === 'ACCEPTED') {
            return redirect()->away(config('app.frontend_url') . '/orders/' . $order->id . '/success');
        }

        return redirect()->away(config('app.frontend_url') . '/orders/' . $order->id . '/failed');
    }
}
```

---

## 17. EMAILS

### 17.1 OrderConfirmationMail

**Fichier**: `app/Mail/OrderConfirmationMail.php`

```php
<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de commande - ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
            with: [
                'order' => $this->order->load('items.photo'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
```

**Fichier**: `resources/views/emails/order-confirmation.blade.php`

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
        .order-info { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .item { padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .total { font-size: 18px; font-weight: bold; margin-top: 15px; }
        .button { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
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
                <p><strong>Numéro de commande:</strong> {{ $order->order_number }}</p>
                <p><strong>Date:</strong> {{ $order->paid_at->format('d/m/Y H:i') }}</p>

                <h4>Photos achetées:</h4>
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

        <div class="footer">
            <p>© {{ date('Y') }} AfroLens. Tous droits réservés.</p>
            <p>Contact: contact@afrolens.com</p>
        </div>
    </div>
</body>
</html>
```

### 17.2 WelcomeMail

**Fichier**: `app/Mail/WelcomeMail.php`

```php
<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue sur AfroLens',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
        );
    }
}
```

---

## 18. COMMANDES ARTISAN

### 18.1 CalculateRevenuesCommand

**Fichier**: `app/Console/Commands/CalculateRevenuesCommand.php`

```php
<?php

namespace App\Console\Commands;

use App\Jobs\CalculateMonthlyRevenue;
use Illuminate\Console\Command;

class CalculateRevenuesCommand extends Command
{
    protected $signature = 'revenues:calculate';
    protected $description = 'Calculer les revenus mensuels et mettre à jour les soldes disponibles';

    public function handle(): int
    {
        $this->info('Calcul des revenus en cours...');

        CalculateMonthlyRevenue::dispatch();

        $this->info('Job de calcul des revenus dispatché avec succès');

        return Command::SUCCESS;
    }
}
```

### 18.2 CleanExpiredDownloadsCommand

**Fichier**: `app/Console/Commands/CleanExpiredDownloadsCommand.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use Illuminate\Console\Command;

class CleanExpiredDownloadsCommand extends Command
{
    protected $signature = 'downloads:clean-expired';
    protected $description = 'Nettoyer les URLs de téléchargement expirées';

    public function handle(): int
    {
        $this->info('Nettoyage des téléchargements expirés...');

        $count = OrderItem::whereNotNull('download_expires_at')
            ->where('download_expires_at', '<', now())
            ->update([
                'download_url' => null,
                'download_expires_at' => null,
            ]);

        $this->info("{$count} téléchargement(s) expiré(s) nettoyé(s)");

        return Command::SUCCESS;
    }
}
```

### 18.3 SendMonthlySummariesCommand

**Fichier**: `app/Console/Commands/SendMonthlySummariesCommand.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Mail\MonthlySummaryMail;
use App\Services\RevenueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonthlySummariesCommand extends Command
{
    protected $signature = 'summaries:send-monthly';
    protected $description = 'Envoyer les résumés mensuels aux photographes';

    public function __construct(
        private RevenueService $revenueService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Envoi des résumés mensuels...');

        $photographers = User::photographers()
            ->with('photographerProfile')
            ->whereHas('photographerProfile', function ($query) {
                $query->where('status', 'approved');
            })
            ->get();

        $count = 0;

        foreach ($photographers as $photographer) {
            $summary = $this->revenueService->getSummary($photographer->id);

            Mail::to($photographer->email)
                ->send(new MonthlySummaryMail($photographer, $summary));

            $count++;
        }

        $this->info("{$count} résumé(s) envoyé(s)");

        return Command::SUCCESS;
    }
}
```

### 18.4 Scheduler

**Fichier**: `routes/console.php`

```php
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('revenues:calculate')->daily();
Schedule::command('downloads:clean-expired')->hourly();
Schedule::command('summaries:send-monthly')->monthlyOn(1, '08:00');
Schedule::command('notifications:clean-old')->weekly();
```

---

## 19. TESTS

### 19.1 Test Authentification

**Fichier**: `tests/Feature/Auth/AuthenticationTest.php`

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
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
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                    'token',
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
    }
}
```

### 19.2 Test Photos

**Fichier**: `tests/Feature/PhotoTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Photo;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

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
                'price_standard' => 25,
                'price_extended' => 75,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('photos', [
            'title' => 'Test Photo',
            'photographer_id' => $photographer->id,
        ]);
    }

    public function test_public_can_view_approved_photos(): void
    {
        $photo = Photo::factory()->approved()->create();

        $response = $this->getJson("/api/photos/{$photo->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'photo' => [
                        'id' => $photo->id,
                        'title' => $photo->title,
                    ],
                ],
            ]);
    }

    public function test_public_cannot_view_pending_photos(): void
    {
        $photo = Photo::factory()->pending()->create();

        $response = $this->getJson("/api/photos/{$photo->id}");

        $response->assertStatus(404);
    }
}
```

---

## 20. CONFIGURATION & DÉPLOIEMENT

### 20.1 Variables d'environnement (.env)

```env
APP_NAME=AfroLens
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.afrolens.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Database
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=afrolens
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
AWS_BUCKET=afrolens-photos
AWS_URL=https://afrolens-photos.s3.amazonaws.com

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@afrolens.com
MAIL_FROM_NAME="${APP_NAME}"

# CinetPay
CINETPAY_API_URL=https://api-checkout.cinetpay.com/v2
CINETPAY_SITE_ID=your-site-id
CINETPAY_API_KEY=your-api-key
CINETPAY_SECRET_KEY=your-secret-key
CINETPAY_NOTIFY_URL=https://api.afrolens.com/webhooks/cinetpay
CINETPAY_RETURN_URL=https://afrolens.com/payment/return

# Sentry (monitoring)
SENTRY_LARAVEL_DSN=your-sentry-dsn
```

### 20.2 Dockerfile

```dockerfile
FROM php:8.3-fpm

# Arguments
ARG user=laravel
ARG uid=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    supervisor

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
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

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

### 20.3 docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: afrolens-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - afrolens

  postgres:
    image: postgres:16
    container_name: afrolens-db
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres-data:/var/lib/postgresql/data
    networks:
      - afrolens

  redis:
    image: redis:7-alpine
    container_name: afrolens-redis
    restart: unless-stopped
    networks:
      - afrolens

  nginx:
    image: nginx:alpine
    container_name: afrolens-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    networks:
      - afrolens

  queue-worker:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: afrolens-queue
    restart: unless-stopped
    command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
    volumes:
      - ./:/var/www
    networks:
      - afrolens

networks:
  afrolens:
    driver: bridge

volumes:
  postgres-data:
```

### 20.4 Commandes de déploiement

```bash
# Installation initiale
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan jwt:secret
php artisan migrate --force
php artisan db:seed --class=CategorySeeder
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Mise à jour
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

### 20.5 Supervisor Configuration

**Fichier**: `/etc/supervisor/conf.d/afrolens-worker.conf`

```ini
[program:afrolens-worker]
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

### 20.6 Nginx Configuration

**Fichier**: `nginx.conf`

```nginx
server {
    listen 80;
    server_name api.afrolens.com;
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

---

## RÉSUMÉ FINAL

Ce document complet spécifie tout ce qui est nécessaire pour développer le backend Laravel 12 pour AfroLens/Pouire :

### ✅ Points clés couverts

1. **Base de données PostgreSQL** avec toutes les migrations
2. **Authentification JWT** complète avec tymon/jwt-auth
3. **Stockage AWS S3** pour toutes les images
4. **Traitement d'images** avec watermarks automatiques
5. **Paiements via CinetPay** - Mobile Money (Orange, MTN, Moov, Wave) + Carte bancaire
6. **Système de revenus** avec période de sécurité et retraits (montants en FCFA - integer)
7. **Notifications** in-app et email
8. **Jobs asynchrones** pour performance
9. **API REST complète** avec ~70 endpoints
10. **Tests automatisés** unitaires et feature
11. **Configuration Docker** prête pour production
12. **Documentation** complète de chaque composant

### 💰 Spécificités Paiement & Devise

- **Devise**: Franc CFA (XOF) uniquement
- **Format prix**: Integer (pas de décimales)
- **Prix minimum photo**: 500 FCFA
- **Retrait minimum**: 5000 FCFA
- **Passerelle de paiement**: CinetPay (API unifiée pour tous les moyens de paiement)
- **Moyens de paiement supportés**:
  - Mobile Money: Orange Money, MTN Money, Moov Money, Wave
  - Carte bancaire (Visa, Mastercard via CinetPay)

### 📂 Prochaines étapes

Avec cette spécification, vous pouvez maintenant :

1. Créer le projet Laravel 12
2. Implémenter chaque section dans l'ordre
3. Tester au fur et à mesure
4. Déployer en production

### 🎯 Ordre de développement recommandé

1. Setup projet + migrations
2. Modèles Eloquent
3. Authentification JWT
4. Services de base (Storage, Image Processing)
5. Routes + Contrôleurs principaux
6. Jobs & Queues
7. Paiements & Revenus
8. Notifications & Emails
9. Tests
10. Déploiement

**Ce document est maintenant votre référence complète pour développer le backend Laravel !**
