# Configuration des Rôles et Permissions - Instructions de Setup

## ⚠️ IMPORTANT - UUID Support

Ce système utilise des **UUIDs** pour les tables de rôles et permissions, en cohérence avec le reste de l'application Pourier.

### Modifications Spécifiques UUID

Les fichiers suivants ont été créés/modifiés pour supporter les UUIDs :

1. **Modèles personnalisés** :
   - `app/Models/Role.php` - Étend `Spatie\Permission\Models\Role` avec support UUID
   - `app/Models/Permission.php` - Étend `Spatie\Permission\Models\Permission` avec support UUID

2. **Configuration** :
   - `config/permission.php` - Pointe vers nos modèles personnalisés

3. **Migration modifiée** :
   - `database/migrations/2025_11_16_232433_create_permission_tables.php` - Utilise `uuid()` au lieu de `bigIncrements()`

---

## Étapes à Suivre

### 1. Migration de la Base de Données

**IMPORTANT** : Vous devez avoir votre base de données PostgreSQL en cours d'exécution.

```bash
# Exécuter les migrations (crée les tables de permissions avec UUIDs)
php artisan migrate
```

Cette commande créera les tables suivantes **avec clés primaires UUID** :
- `roles` - Table des rôles (id: UUID)
- `permissions` - Table des permissions (id: UUID)
- `model_has_permissions` - Permissions assignées aux utilisateurs (permission_id: UUID, model_id: UUID)
- `model_has_roles` - Rôles assignés aux utilisateurs (role_id: UUID, model_id: UUID)
- `role_has_permissions` - Permissions assignées aux rôles (permission_id: UUID, role_id: UUID)

### 2. Seeding des Rôles et Permissions

```bash
# Exécuter le seeder pour créer les rôles et permissions
php artisan db:seed --class=RolePermissionSeeder
```

Ceci créera :
- **4 rôles** : buyer, photographer, moderator, admin
- **40+ permissions** organisées par catégorie
- **Association automatique** des permissions aux rôles

### 3. Assigner les Rôles aux Utilisateurs Existants

Si vous avez déjà des utilisateurs dans votre base de données, vous devez leur assigner des rôles.

**Option A : Utiliser Tinker (Recommandé pour quelques utilisateurs)**

```bash
php artisan tinker
```

```php
// Assigner le rôle "buyer" à tous les utilisateurs de type buyer
$buyers = User::where('account_type', 'buyer')->get();
foreach ($buyers as $user) {
    $user->assignRole('buyer');
}

// Assigner le rôle "photographer" à tous les photographes
$photographers = User::where('account_type', 'photographer')->get();
foreach ($photographers as $photographer) {
    $photographer->assignRole('photographer');
}

// Assigner le rôle "admin" à un admin spécifique
$admin = User::where('email', 'admin@pourier.com')->first();
if ($admin) {
    $admin->assignRole('admin');
}
```

**Option B : Créer un Seeder (Pour beaucoup d'utilisateurs)**

Créez `database/seeders/AssignExistingUsersRolesSeeder.php` :

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AssignExistingUsersRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Buyers
        User::where('account_type', 'buyer')
            ->whereDoesntHave('roles')
            ->each(function ($user) {
                $user->assignRole('buyer');
            });

        // Photographers
        User::where('account_type', 'photographer')
            ->whereDoesntHave('roles')
            ->each(function ($user) {
                $user->assignRole('photographer');
            });

        // Admins
        User::where('account_type', 'admin')
            ->whereDoesntHave('roles')
            ->each(function ($user) {
                $user->assignRole('admin');
            });

        $this->command->info('Roles assigned to existing users!');
    }
}
```

Puis exécutez :
```bash
php artisan db:seed --class=AssignExistingUsersRolesSeeder
```

### 4. Mettre à Jour le DatabaseSeeder (Optionnel)

Pour les futurs environnements de développement, ajoutez à `database/seeders/DatabaseSeeder.php` :

```php
public function run(): void
{
    $this->call([
        RolePermissionSeeder::class,
        // Vos autres seeders...
    ]);
}
```

### 5. Vérification

**Vérifier les rôles créés :**
```bash
php artisan tinker
```

```php
// Vérifier les rôles (utilise nos modèles personnalisés avec UUID)
\App\Models\Role::all()->pluck('name');
// Devrait retourner: ["buyer", "photographer", "moderator", "admin"]

// Vérifier que les IDs sont bien des UUIDs
\App\Models\Role::first()->id;
// Devrait retourner quelque chose comme: "9d3e4f5a-6b7c-8d9e-0f1a-2b3c4d5e6f7a"

// Vérifier le nombre de permissions
\App\Models\Permission::count();
// Devrait retourner: 40+

// Vérifier que les permissions utilisent aussi des UUIDs
\App\Models\Permission::first()->id;
// Devrait aussi être un UUID

// Vérifier les permissions d'un rôle
$admin = \App\Models\Role::findByName('admin', 'api');
$admin->permissions->pluck('name');

// Vérifier un utilisateur
$user = User::first();
$user->getRoleNames(); // Collection des rôles
$user->getAllPermissions()->pluck('name'); // Collection des permissions

// Vérifier la relation avec UUID
$user->roles()->first()?->id; // Devrait être un UUID
```

### 6. Tester l'API

**Test de Login :**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "photographer@test.com",
    "password": "password"
  }'
```

Vérifiez que la réponse contient :
- `user.roles` : array de rôles
- `user.permissions` : array de permissions
- `user.photographer_status` : statut si photographe
- `user.is_approved_photographer` : boolean

**Test de l'endpoint Abilities :**
```bash
curl -X GET http://localhost:8000/api/auth/abilities \
  -H "Authorization: Bearer {votre-token}"
```

Devrait retourner toutes les capacités de l'utilisateur.

---

## Fichiers Créés/Modifiés

### Nouveaux Fichiers

1. **Config**
   - `config/permission.php` - Configuration Spatie (modifiée pour utiliser nos modèles)

2. **Migrations**
   - `database/migrations/2025_11_16_232433_create_permission_tables.php` - **Modifiée pour UUID**

3. **Modèles (Support UUID)** ⭐ NOUVEAU
   - `app/Models/Role.php` - Modèle Role avec support UUID
   - `app/Models/Permission.php` - Modèle Permission avec support UUID

4. **Seeders**
   - `database/seeders/RolePermissionSeeder.php` - Utilise nos modèles UUID

5. **Policies**
   - `app/Policies/OrderPolicy.php`
   - `app/Policies/WithdrawalPolicy.php`
   - `app/Policies/UserPolicy.php`
   - `app/Policies/PhotographerProfilePolicy.php`

6. **Documentation**
   - `FRONTEND_ROLES_PERMISSIONS_GUIDE.md` - Guide d'intégration frontend complet
   - `SETUP_ROLES_PERMISSIONS.md` - Ce fichier

### Fichiers Modifiés

1. `app/Models/User.php`
   - Ajout de `$guard_name = 'api'`
   - Méthodes `isApprovedPhotographer()` et `getPhotographerStatus()`

2. `app/Http/Resources/UserResource.php`
   - Ajout des champs `roles`, `permissions`
   - Ajout des champs `photographer_status`, `is_approved_photographer`

3. `app/Services/AuthService.php`
   - Eager loading de `roles` et `permissions` dans register(), login(), me()
   - Auto-assignation des rôles lors de l'inscription ✅ FAIT

4. `app/Http/Controllers/Api/Auth/AuthController.php`
   - Nouvelle méthode `abilities()` avec documentation OpenAPI

5. `routes/api.php`
   - Nouvelle route `GET /api/auth/abilities`

6. `config/permission.php`
   - Pointe vers `App\Models\Role` et `App\Models\Permission` au lieu des modèles Spatie par défaut

---

## Configuration Automatique lors de l'Inscription

Le système est **DÉJÀ CONFIGURÉ** pour assigner automatiquement le rôle correspondant lors de l'inscription ✅

Le code dans `AuthService::register()` (lignes 35-52) :

```php
// Assign role based on account type
if ($user->isPhotographer()) {
    $user->assignRole('photographer');

    // Create photographer profile automatically
    $username = $this->generateUniqueUsername($data['first_name'], $data['last_name']);

    PhotographerProfile::create([
        'user_id' => $user->id,
        'username' => $username,
        'display_name' => $user->full_name,
        'status' => 'pending', // Requires admin approval
        'commission_rate' => 80.00, // 80% for photographer, 20% for platform
    ]);
} elseif ($user->isBuyer()) {
    $user->assignRole('buyer');
}
// Note: Admin role should be assigned manually via Tinker
```

**Comportement** :
- Les nouveaux acheteurs reçoivent automatiquement le rôle `buyer`
- Les nouveaux photographes reçoivent automatiquement le rôle `photographer`
- Les admins doivent être créés et assignés manuellement (sécurité)

---

## Permissions et Sécurité

### Bonnes Pratiques

1. **Ne jamais assigner le rôle "admin" automatiquement**
   - Les admins doivent être créés manuellement

2. **Toujours vérifier `isApprovedPhotographer()` pour les actions de photographe**
   - Avoir le rôle ne suffit pas
   - Le profil doit être approuvé

3. **Utiliser les Policies dans les contrôleurs**
   ```php
   $this->authorize('view', $photo);
   ```

4. **Vérifier les permissions au lieu des rôles quand possible**
   ```php
   // BON
   if ($user->can('delete-any-photo')) { ... }

   // MOINS BON
   if ($user->isAdmin()) { ... }
   ```

### Cache des Permissions

Spatie Permission met en cache les permissions. Si vous modifiez manuellement les permissions, videz le cache :

```bash
php artisan permission:cache-reset
```

Ou dans le code :
```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

---

## Prochaines Étapes

1. ✅ Exécuter les migrations
2. ✅ Exécuter le seeder des rôles/permissions
3. ✅ Assigner les rôles aux utilisateurs existants
4. ✅ Modifier `AuthService::register()` pour auto-assigner les rôles
5. ✅ Tester les endpoints API
6. ✅ Partager `FRONTEND_ROLES_PERMISSIONS_GUIDE.md` avec l'équipe frontend
7. ✅ Mettre à jour la documentation OpenAPI si nécessaire

---

## Dépannage

### Erreur "Role does not exist"

```bash
# Réexécuter le seeder
php artisan db:seed --class=RolePermissionSeeder
```

### Permissions ne s'affichent pas dans l'API

```bash
# Vider le cache des permissions
php artisan permission:cache-reset

# Vider tout le cache
php artisan cache:clear
```

### L'utilisateur n'a pas de rôles

```php
// Vérifier et assigner
$user = User::find('user-id');
$user->assignRole('buyer'); // ou le rôle approprié
```

---

**Créé le** : 2025-11-16
**Auteur** : Backend Team
