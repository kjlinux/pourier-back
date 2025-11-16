# Guide d'Intégration des Rôles et Permissions - Frontend Pourier

## Table des Matières

1. [Vue d'Ensemble](#vue-densemble)
2. [Système de Rôles et Permissions](#système-de-rôles-et-permissions)
3. [Endpoints API](#endpoints-api)
4. [Structure des Réponses](#structure-des-réponses)
5. [Intégration Frontend](#intégration-frontend)
6. [Gestion des Erreurs](#gestion-des-erreurs)
7. [Cas d'Usage Spécifiques](#cas-dusage-spécifiques)

---

## Vue d'Ensemble

Le backend Pourier utilise un système de rôles et permissions granulaire basé sur **Spatie Laravel Permission**. Ce système permet au frontend de :

- ✅ Vérifier les capacités de l'utilisateur
- ✅ Afficher/masquer des éléments d'interface conditionnellement
- ✅ Protéger les routes
- ✅ Gérer le workflow d'approbation des photographes
- ✅ Fournir une expérience utilisateur personnalisée

---

## Système de Rôles et Permissions

### Rôles Disponibles

| Rôle | Valeur | Description | Nécessite Approbation |
|------|--------|-------------|----------------------|
| **Buyer** | `buyer` | Acheteur de photos (rôle par défaut) | Non |
| **Photographer** | `photographer` | Photographe vendant des photos | **Oui** (profil) |
| **Moderator** | `moderator` | Modérateur de contenu | Non |
| **Admin** | `admin` | Administrateur de la plateforme | Non |

### Permissions par Rôle

#### Buyer
```javascript
permissions: [
  'view-own-orders'
]
```

#### Photographer (Approuvé)
```javascript
permissions: [
  'upload-photos',
  'edit-own-photos',
  'delete-own-photos',
  'view-own-revenue',
  'request-withdrawals',
  'view-own-analytics',
  'view-own-orders'
]
```

#### Moderator
```javascript
permissions: [
  'view-all-photos',
  'moderate-photos',
  'approve-photos',
  'reject-photos',
  'view-photographers',
  'approve-photographers',
  'reject-photographers',
  'view-dashboard'
]
```

#### Admin (Toutes les permissions)
```javascript
permissions: [
  // Photo Management
  'upload-photos',
  'edit-own-photos',
  'delete-own-photos',
  'view-all-photos',
  'moderate-photos',
  'approve-photos',
  'reject-photos',
  'feature-photos',
  'delete-any-photo',

  // Revenue & Withdrawals
  'view-own-revenue',
  'view-all-revenue',
  'request-withdrawals',
  'approve-withdrawals',
  'reject-withdrawals',
  'complete-withdrawals',

  // User Management
  'view-users',
  'edit-users',
  'suspend-users',
  'activate-users',
  'delete-users',

  // Photographer Management
  'view-photographers',
  'approve-photographers',
  'reject-photographers',
  'suspend-photographers',
  'activate-photographers',

  // Analytics
  'view-own-analytics',
  'view-platform-analytics',

  // Orders
  'view-own-orders',
  'view-all-orders',
  'manage-orders',

  // Categories
  'manage-categories',

  // System
  'manage-featured-content',
  'view-dashboard'
]
```

### Statuts du Profil Photographe

**IMPORTANT** : Les photographes ont un système à deux niveaux :
1. Rôle `photographer` (type de compte)
2. Statut du profil photographe (nécessite approbation admin)

| Statut | Description | Peut Uploader |
|--------|-------------|---------------|
| `pending` | En attente d'approbation admin | ❌ Non |
| `approved` | Approuvé par un admin | ✅ Oui |
| `rejected` | Refusé par un admin | ❌ Non |
| `suspended` | Suspendu temporairement | ❌ Non |

---

## Endpoints API

### 1. Connexion / Inscription

**Retourne automatiquement les rôles et permissions**

```http
POST /api/auth/register
POST /api/auth/login
GET  /api/auth/me
```

**Réponse** :
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "uuid",
      "email": "user@example.com",
      "account_type": "photographer",
      "roles": ["photographer"],
      "permissions": ["upload-photos", "edit-own-photos", ...],
      "photographer_status": "approved",
      "is_approved_photographer": true,
      "is_verified": true,
      "is_active": true
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

### 2. Endpoint Abilities (Nouveau)

**Récupère les capacités de l'utilisateur connecté**

```http
GET /api/auth/abilities
Authorization: Bearer {token}
```

**Réponse** :
```json
{
  "success": true,
  "data": {
    "account_type": "photographer",
    "roles": ["photographer"],
    "permissions": ["upload-photos", "edit-own-photos", ...],
    "photographer_status": "approved",

    // Drapeaux de capacité (convenience flags)
    "can_upload_photos": true,
    "can_moderate": false,
    "can_manage_users": false,
    "can_approve_withdrawals": false,
    "can_view_platform_analytics": false,

    // Statut du compte
    "is_verified": true,
    "is_active": true,
    "is_approved_photographer": true
  }
}
```

---

## Structure des Réponses

### UserResource (Login, Register, Me)

```typescript
interface User {
  id: string;
  email: string;
  first_name: string;
  last_name: string;
  full_name: string;
  avatar_url: string | null;
  phone: string | null;
  bio: string | null;
  account_type: 'buyer' | 'photographer' | 'moderator' | 'admin';
  is_verified: boolean;
  is_active: boolean;
  email_verified_at: string | null;
  last_login: string | null;

  // Roles & Permissions
  roles: string[];
  permissions: string[];

  // Photographer Specific
  photographer_profile: PhotographerProfile | null;
  photographer_status: 'pending' | 'approved' | 'rejected' | 'suspended' | null;
  is_approved_photographer: boolean;

  created_at: string;
  updated_at: string;
}
```

### AbilitiesResponse

```typescript
interface Abilities {
  account_type: 'buyer' | 'photographer' | 'moderator' | 'admin';
  roles: string[];
  permissions: string[];
  photographer_status: 'pending' | 'approved' | 'rejected' | 'suspended' | null;

  // Capability Flags
  can_upload_photos: boolean;
  can_moderate: boolean;
  can_manage_users: boolean;
  can_approve_withdrawals: boolean;
  can_view_platform_analytics: boolean;

  // Account Status
  is_verified: boolean;
  is_active: boolean;
  is_approved_photographer: boolean;
}
```

---

## Intégration Frontend

### 1. Stockage de l'État Utilisateur

**Après Login/Register :**

```javascript
// Exemple avec Zustand (React)
import create from 'zustand';
import { persist } from 'zustand/middleware';

const useAuthStore = create(
  persist(
    (set, get) => ({
      user: null,
      token: null,

      setAuth: (user, token) => set({ user, token }),

      logout: () => set({ user: null, token: null }),

      // Helper methods
      hasRole: (role) => {
        const user = get().user;
        return user?.roles?.includes(role) || user?.account_type === role;
      },

      hasPermission: (permission) => {
        const user = get().user;
        return user?.permissions?.includes(permission);
      },

      isApprovedPhotographer: () => {
        const user = get().user;
        return user?.is_approved_photographer === true;
      },

      getPhotographerStatus: () => {
        const user = get().user;
        return user?.photographer_status;
      }
    }),
    {
      name: 'auth-storage',
      getStorage: () => localStorage
    }
  )
);

export default useAuthStore;
```

**Exemple avec Pinia (Vue 3) :**

```javascript
// stores/auth.js
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
  }),

  getters: {
    hasRole: (state) => (role) => {
      return state.user?.roles?.includes(role) ||
             state.user?.account_type === role;
    },

    hasPermission: (state) => (permission) => {
      return state.user?.permissions?.includes(permission);
    },

    isApprovedPhotographer: (state) => {
      return state.user?.is_approved_photographer === true;
    },

    photographerStatus: (state) => {
      return state.user?.photographer_status;
    },

    canUploadPhotos: (state) => {
      return state.user?.permissions?.includes('upload-photos') &&
             state.user?.is_approved_photographer === true;
    }
  },

  actions: {
    setAuth(user, token) {
      this.user = user;
      this.token = token;
    },

    logout() {
      this.user = null;
      this.token = null;
    }
  },

  persist: true
});
```

### 2. Protéger les Routes

**React Router (React) :**

```javascript
import { Navigate } from 'react-router-dom';
import useAuthStore from './stores/authStore';

// Protected Route Component
function ProtectedRoute({ children, requireRole, requirePermission }) {
  const { user, hasRole, hasPermission } = useAuthStore();

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  if (requireRole && !hasRole(requireRole)) {
    return <Navigate to="/403" replace />;
  }

  if (requirePermission && !hasPermission(requirePermission)) {
    return <Navigate to="/403" replace />;
  }

  return children;
}

// Photographer Route (with approval check)
function PhotographerRoute({ children }) {
  const { user, isApprovedPhotographer } = useAuthStore();

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  if (user.account_type !== 'photographer') {
    return <Navigate to="/403" replace />;
  }

  if (!isApprovedPhotographer()) {
    return <Navigate to="/photographer/pending-approval" replace />;
  }

  return children;
}

// Usage in routes
<Route
  path="/photographer/dashboard"
  element={
    <PhotographerRoute>
      <PhotographerDashboard />
    </PhotographerRoute>
  }
/>

<Route
  path="/admin/dashboard"
  element={
    <ProtectedRoute requireRole="admin">
      <AdminDashboard />
    </ProtectedRoute>
  }
/>
```

**Vue Router (Vue 3) :**

```javascript
// router/index.js
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
  {
    path: '/photographer/dashboard',
    component: () => import('@/views/Photographer/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      requiresRole: 'photographer',
      requiresApproval: true
    }
  },
  {
    path: '/admin/dashboard',
    component: () => import('@/views/Admin/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      requiresRole: 'admin'
    }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.user) {
    return next('/login');
  }

  if (to.meta.requiresRole && !authStore.hasRole(to.meta.requiresRole)) {
    return next('/403');
  }

  if (to.meta.requiresApproval && !authStore.isApprovedPhotographer) {
    return next('/photographer/pending-approval');
  }

  next();
});

export default router;
```

### 3. Rendu Conditionnel des Composants

**React :**

```javascript
import useAuthStore from './stores/authStore';

function PhotoUploadButton() {
  const { user, hasPermission, isApprovedPhotographer } = useAuthStore();

  // Don't show if not a photographer
  if (user?.account_type !== 'photographer') {
    return null;
  }

  // Show pending message if not approved
  if (!isApprovedPhotographer()) {
    return (
      <div className="alert alert-warning">
        Votre profil photographe est en attente d'approbation.
        Statut: {user.photographer_status}
      </div>
    );
  }

  // Show button if has permission
  if (hasPermission('upload-photos')) {
    return (
      <button onClick={handleUpload}>
        Uploader une Photo
      </button>
    );
  }

  return null;
}

// Admin Actions
function AdminActions() {
  const { hasPermission } = useAuthStore();

  return (
    <div>
      {hasPermission('approve-photos') && (
        <button>Approuver</button>
      )}

      {hasPermission('reject-photos') && (
        <button>Rejeter</button>
      )}

      {hasPermission('delete-any-photo') && (
        <button>Supprimer</button>
      )}
    </div>
  );
}
```

**Vue 3 :**

```vue
<template>
  <div>
    <!-- Photographer Upload -->
    <div v-if="authStore.user?.account_type === 'photographer'">
      <div v-if="!authStore.isApprovedPhotographer" class="alert alert-warning">
        Votre profil photographe est en attente d'approbation.
        Statut: {{ authStore.photographerStatus }}
      </div>

      <button
        v-if="authStore.hasPermission('upload-photos') && authStore.isApprovedPhotographer"
        @click="handleUpload"
      >
        Uploader une Photo
      </button>
    </div>

    <!-- Admin Actions -->
    <div v-if="authStore.hasRole('admin')">
      <button v-if="authStore.hasPermission('approve-photos')">
        Approuver
      </button>

      <button v-if="authStore.hasPermission('delete-any-photo')">
        Supprimer
      </button>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const handleUpload = () => {
  // Upload logic
};
</script>
```

### 4. Directive Personnalisée (Vue 3)

```javascript
// directives/permission.js
export const vPermission = {
  mounted(el, binding) {
    const { value } = binding;
    const authStore = useAuthStore();

    if (!authStore.hasPermission(value)) {
      el.parentNode?.removeChild(el);
    }
  }
};

// Usage in component
<button v-permission="'approve-photos'">Approuver</button>
```

### 5. Hook Personnalisé (React)

```javascript
// hooks/usePermission.js
import useAuthStore from '../stores/authStore';

export function usePermission(permission) {
  const { hasPermission } = useAuthStore();
  return hasPermission(permission);
}

export function useRole(role) {
  const { hasRole } = useAuthStore();
  return hasRole(role);
}

// Usage
function MyComponent() {
  const canUpload = usePermission('upload-photos');
  const isAdmin = useRole('admin');

  return (
    <div>
      {canUpload && <UploadButton />}
      {isAdmin && <AdminPanel />}
    </div>
  );
}
```

---

## Gestion des Erreurs

### Intercepteur Axios

```javascript
import axios from 'axios';
import { useAuthStore } from './stores/auth';
import { useRouter } from 'vue-router'; // or useNavigate for React

const api = axios.create({
  baseURL: 'http://localhost:8000/api'
});

// Request interceptor - add token
api.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore();
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const router = useRouter(); // or useNavigate()

    if (error.response?.status === 401) {
      // Token invalide ou expiré
      const authStore = useAuthStore();
      authStore.logout();
      router.push('/login');
    }

    if (error.response?.status === 403) {
      const data = error.response.data;

      // Check if photographer approval issue
      if (data.photographer_status) {
        router.push(`/photographer/status/${data.photographer_status}`);
      } else {
        // Generic forbidden
        router.push('/403');
      }
    }

    return Promise.reject(error);
  }
);

export default api;
```

### Gestion des Erreurs 403 (Forbidden)

**Page de Statut Photographe :**

```vue
<!-- views/Photographer/Status.vue -->
<template>
  <div class="container">
    <div v-if="status === 'pending'" class="alert alert-info">
      <h2>Profil en Attente d'Approbation</h2>
      <p>Votre profil photographe est en cours de vérification par notre équipe.</p>
      <p>Vous recevrez une notification une fois approuvé.</p>
    </div>

    <div v-if="status === 'rejected'" class="alert alert-danger">
      <h2>Profil Refusé</h2>
      <p>Votre profil photographe a été refusé.</p>
      <p>Raison: {{ rejectionReason }}</p>
      <button @click="reapply">Soumettre à Nouveau</button>
    </div>

    <div v-if="status === 'suspended'" class="alert alert-warning">
      <h2>Profil Suspendu</h2>
      <p>Votre profil photographe a été temporairement suspendu.</p>
      <p>Contactez le support pour plus d'informations.</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const status = ref(route.params.status);
const rejectionReason = ref(''); // Fetch from API

// Fetch photographer profile details
// ...
</script>
```

---

## Cas d'Usage Spécifiques

### 1. Navigation Menu Dynamique

```javascript
// React
function Navigation() {
  const { user, hasRole, hasPermission } = useAuthStore();

  const menuItems = [
    {
      label: 'Dashboard',
      path: '/',
      show: true
    },
    {
      label: 'Mes Photos',
      path: '/photographer/photos',
      show: hasRole('photographer')
    },
    {
      label: 'Revenus',
      path: '/photographer/revenue',
      show: hasPermission('view-own-revenue')
    },
    {
      label: 'Modération',
      path: '/admin/moderation',
      show: hasPermission('moderate-photos')
    },
    {
      label: 'Utilisateurs',
      path: '/admin/users',
      show: hasPermission('view-users')
    }
  ];

  return (
    <nav>
      {menuItems
        .filter(item => item.show)
        .map(item => (
          <Link key={item.path} to={item.path}>
            {item.label}
          </Link>
        ))
      }
    </nav>
  );
}
```

### 2. Vérification Avant Action

```javascript
// React
function PhotoCard({ photo }) {
  const { user, hasPermission } = useAuthStore();

  const handleDelete = async () => {
    // Check permission before action
    const canDeleteOwn = hasPermission('delete-own-photos') &&
                         photo.photographer_id === user.id;
    const canDeleteAny = hasPermission('delete-any-photo');

    if (!canDeleteOwn && !canDeleteAny) {
      alert('Vous n\'avez pas la permission de supprimer cette photo.');
      return;
    }

    // Proceed with delete
    try {
      await api.delete(`/photos/${photo.id}`);
    } catch (error) {
      if (error.response?.status === 403) {
        alert('Action non autorisée');
      }
    }
  };

  return (
    <div className="photo-card">
      <img src={photo.url} alt={photo.title} />

      {/* Show delete button only if user has permission */}
      {(hasPermission('delete-own-photos') || hasPermission('delete-any-photo')) && (
        <button onClick={handleDelete}>Supprimer</button>
      )}
    </div>
  );
}
```

### 3. Rafraîchir les Capacités

```javascript
// Appeler cet endpoint périodiquement ou après des actions importantes
async function refreshAbilities() {
  const authStore = useAuthStore();

  try {
    const response = await api.get('/auth/abilities');

    // Update user with fresh permissions
    authStore.setAuth({
      ...authStore.user,
      ...response.data.data
    }, authStore.token);

  } catch (error) {
    console.error('Failed to refresh abilities', error);
  }
}

// Call after:
// - Login
// - Role change
// - Permission change
// - Photographer approval
```

---

## Checklist d'Implémentation

### Backend (À Exécuter)

- [ ] Démarrer la base de données PostgreSQL
- [ ] Exécuter `php artisan migrate` (crée les tables de permissions)
- [ ] Exécuter `php artisan db:seed --class=RolePermissionSeeder` (crée les rôles/permissions)
- [ ] Assigner les rôles aux utilisateurs existants :
  ```php
  // Dans Tinker: php artisan tinker
  $user = User::find('user-id');
  $user->assignRole('photographer'); // ou 'buyer', 'admin', 'moderator'
  ```

### Frontend

- [x] Créer store/state management pour l'auth
- [x] Ajouter helpers `hasRole()` et `hasPermission()`
- [x] Configurer intercepteur Axios pour les erreurs 403
- [x] Créer composants de protection de routes
- [x] Implémenter rendu conditionnel basé sur permissions
- [x] Gérer le workflow d'approbation photographe
- [x] Créer pages de statut (pending, rejected, suspended)
- [x] Tester tous les rôles et permissions

---

## Référence Rapide des Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/auth/register` | POST | No | Créer un compte |
| `/api/auth/login` | POST | No | Se connecter |
| `/api/auth/logout` | POST | Yes | Se déconnecter |
| `/api/auth/me` | GET | Yes | Profil utilisateur |
| `/api/auth/abilities` | GET | Yes | **Capacités utilisateur** |
| `/api/auth/refresh` | POST | Yes | Rafraîchir token |

---

## Support et Questions

Pour toute question sur l'implémentation des rôles et permissions :

1. Consulter ce guide
2. Vérifier la documentation OpenAPI : `/api/documentation`
3. Contacter l'équipe backend

---

**Version** : 1.0
**Dernière mise à jour** : 2025-11-16
**Auteur** : Équipe Backend Pourier
