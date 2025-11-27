# Guide d'intégration S3 pour le Frontend

## Vue d'ensemble

Le backend utilise Amazon S3 pour le stockage des fichiers (photos, factures, etc.). Cette documentation explique comment le frontend doit gérer cette intégration, notamment pour l'affichage et le téléchargement des fichiers.

## 📋 Table des matières

1. [Concepts clés](#concepts-clés)
2. [Types d'URLs retournées par l'API](#types-durls-retournées-par-lapi)
3. [Gestion de l'affichage des images](#gestion-de-laffichage-des-images)
4. [Gestion des téléchargements](#gestion-des-téléchargements)
5. [URLs signées et expiration](#urls-signées-et-expiration)
6. [Bonnes pratiques](#bonnes-pratiques)
7. [Exemples de code](#exemples-de-code)

---

## Concepts clés

### Stockage S3 vs Local

Le backend peut utiliser deux modes de stockage :
- **S3** (production) : Fichiers stockés sur Amazon S3
- **Local** (développement) : Fichiers stockés localement sur le serveur

La configuration est transparente pour le frontend - l'API retourne toujours des URLs utilisables directement.

### Visibilité des fichiers

Les fichiers ont deux niveaux de visibilité :

#### 1. **Public** (visibility: 'public')
- Thumbnails (miniatures)
- Previews (aperçus avec watermark)
- Avatars utilisateurs
- Images de couverture

Ces fichiers sont **accessibles directement** via leur URL sans authentification.

#### 2. **Privé** (visibility: 'private')
- Originaux haute résolution
- Factures (invoices)

Ces fichiers nécessitent une **authentification** et génèrent des **URLs signées temporaires**.

---

## Types d'URLs retournées par l'API

### 1. URLs publiques S3

**Format** : `https://bucket-name.s3.region.amazonaws.com/path/to/file.jpg`

**Utilisation** : Affichage direct dans `<img>` ou `<video>`

**Exemple de réponse API** :
```json
{
  "id": "9d445a1c-85c5-4b6d-9c38-99a4915d6dac",
  "title": "Sunset in Ouagadougou",
  "preview_url": "https://pouire.s3.us-east-1.amazonaws.com/photos/123/previews/preview-uuid-123456.jpg",
  "thumbnail_url": "https://pouire.s3.us-east-1.amazonaws.com/photos/123/thumbnails/thumb-uuid-123456.jpg"
}
```

### 2. URLs signées (temporaires)

**Format** : `https://bucket-name.s3.region.amazonaws.com/path/to/file.jpg?X-Amz-Algorithm=...&X-Amz-Credential=...&X-Amz-Signature=...`

**Utilisation** : Téléchargement de fichiers privés

**Caractéristiques** :
- ⏱️ **Durée de vie limitée** (24h par défaut)
- 🔒 **Sécurisées** par signature cryptographique
- 🚫 **Ne peuvent pas être réutilisées** après expiration

---

## Gestion de l'affichage des images

### Images publiques (Previews, Thumbnails)

Les URLs publiques peuvent être utilisées **directement** dans vos composants.

#### React/Next.js
```tsx
interface Photo {
  id: string;
  title: string;
  preview_url: string;
  thumbnail_url: string;
}

function PhotoCard({ photo }: { photo: Photo }) {
  return (
    <div className="photo-card">
      <img
        src={photo.preview_url}
        alt={photo.title}
        loading="lazy"
        onError={(e) => {
          e.currentTarget.src = '/fallback-image.jpg';
        }}
      />
    </div>
  );
}
```

#### Vue.js
```vue
<template>
  <div class="photo-card">
    <img
      :src="photo.preview_url"
      :alt="photo.title"
      loading="lazy"
      @error="handleImageError"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

interface Photo {
  id: string;
  title: string;
  preview_url: string;
  thumbnail_url: string;
}

const props = defineProps<{ photo: Photo }>();

const handleImageError = (e: Event) => {
  (e.target as HTMLImageElement).src = '/fallback-image.jpg';
};
</script>
```

### Cache et performance

Les URLs publiques S3 sont configurées avec :
- `Cache-Control: max-age=31536000` (1 an)
- **Conseil** : Activez le cache du navigateur pour ces ressources

```tsx
// Next.js - Configuration next.config.js
module.exports = {
  images: {
    domains: ['pouire.s3.us-east-1.amazonaws.com'],
    remotePatterns: [
      {
        protocol: 'https',
        hostname: '**.s3.*.amazonaws.com',
      },
    ],
  },
};
```

---

## Gestion des téléchargements

### 1. Téléchargement d'une photo individuelle

**Endpoint** : `GET /api/downloads/photo/{photoId}`

**Authentification** : Requise (Bearer token)

**Comportement** :
- ✅ **S3** : L'API retourne une **redirection (302)** vers une URL signée S3
- ✅ **Local** : L'API stream le fichier directement

#### Exemple avec Fetch API

```typescript
async function downloadPhoto(photoId: string, token: string) {
  try {
    const response = await fetch(
      `https://api.pouire.bf/api/downloads/photo/${photoId}`,
      {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'image/jpeg',
        },
      }
    );

    if (!response.ok) {
      throw new Error('Téléchargement échoué');
    }

    // Créer un blob depuis la réponse
    const blob = await response.blob();

    // Créer un lien de téléchargement
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `photo-${photoId}.jpg`;
    document.body.appendChild(a);
    a.click();

    // Nettoyage
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  } catch (error) {
    console.error('Erreur de téléchargement:', error);
    throw error;
  }
}
```

#### Exemple avec Axios

```typescript
import axios from 'axios';

async function downloadPhoto(photoId: string) {
  try {
    const response = await axios.get(
      `/api/downloads/photo/${photoId}`,
      {
        responseType: 'blob',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
        },
      }
    );

    const url = window.URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `photo-${photoId}.jpg`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Erreur:', error);
  }
}
```

### 2. Téléchargement de toutes les photos d'une commande (ZIP)

**Endpoint** : `GET /api/downloads/order/{orderId}`

**Authentification** : Requise

**Format** : Fichier ZIP contenant toutes les photos haute résolution

```typescript
async function downloadOrderZip(orderId: string) {
  try {
    const response = await fetch(
      `https://api.pouire.bf/api/downloads/order/${orderId}`,
      {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      }
    );

    if (!response.ok) {
      throw new Error('Téléchargement échoué');
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `order-${orderId}.zip`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  } catch (error) {
    console.error('Erreur:', error);
  }
}
```

### 3. Téléchargement de facture (PDF)

**Endpoint** : `GET /api/downloads/invoice/{orderId}`

**Authentification** : Requise

**Format** : Fichier PDF

```typescript
async function downloadInvoice(orderId: string) {
  try {
    const response = await axios.get(
      `/api/downloads/invoice/${orderId}`,
      {
        responseType: 'blob',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
        },
      }
    );

    const url = window.URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `invoice-${orderId}.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Erreur de téléchargement de facture:', error);
  }
}
```

### 4. Téléchargement de preview (public, sans auth)

**Endpoint** : `GET /api/downloads/preview/{photoId}`

**Authentification** : NON requise

```typescript
async function downloadPreview(photoId: string) {
  try {
    const response = await fetch(
      `https://api.pouire.bf/api/downloads/preview/${photoId}`
    );

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `preview-${photoId}.jpg`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  } catch (error) {
    console.error('Erreur:', error);
  }
}
```

---

## URLs signées et expiration

### Durée de vie

Les URLs signées générées par le backend ont une durée de vie de **24 heures**.

### Gestion de l'expiration

⚠️ **Important** : Ne stockez **JAMAIS** les URLs signées dans le localStorage ou le cache

```typescript
// ❌ MAUVAIS - Ne faites pas cela
localStorage.setItem('downloadUrl', signedUrl); // L'URL va expirer !

// ✅ BON - Toujours demander une nouvelle URL
async function getDownloadUrl(photoId: string) {
  const response = await api.get(`/downloads/photo/${photoId}`);
  return response.url; // Utilisez immédiatement
}
```

### Détection d'expiration

Si vous obtenez une erreur 403 lors de l'utilisation d'une URL signée :

```typescript
async function downloadWithRetry(photoId: string, retries = 1) {
  try {
    await downloadPhoto(photoId);
  } catch (error) {
    if (error.status === 403 && retries > 0) {
      // L'URL a expiré, réessayer
      console.log('URL expirée, nouvelle tentative...');
      await downloadWithRetry(photoId, retries - 1);
    } else {
      throw error;
    }
  }
}
```

---

## Bonnes pratiques

### ✅ À FAIRE

1. **Utilisez les URLs directement**
   ```typescript
   <img src={photo.preview_url} />
   ```

2. **Gérez les erreurs de chargement**
   ```typescript
   <img
     src={photo.preview_url}
     onError={(e) => e.currentTarget.src = '/fallback.jpg'}
   />
   ```

3. **Utilisez le lazy loading**
   ```typescript
   <img src={photo.preview_url} loading="lazy" />
   ```

4. **Ajoutez des indicateurs de chargement**
   ```typescript
   const [loading, setLoading] = useState(true);
   <img
     src={photo.preview_url}
     onLoad={() => setLoading(false)}
   />
   ```

5. **Implémentez une gestion d'erreur robuste**
   ```typescript
   try {
     await downloadPhoto(id);
   } catch (error) {
     if (error.response?.status === 403) {
       showError('Vous n\'avez pas acheté cette photo');
     } else if (error.response?.status === 404) {
       showError('Photo introuvable');
     } else {
       showError('Erreur de téléchargement');
     }
   }
   ```

### ❌ À ÉVITER

1. **Ne modifiez PAS les URLs**
   ```typescript
   // ❌ MAUVAIS
   const modifiedUrl = photo.preview_url.replace('s3', 'cdn');
   ```

2. **Ne cachez PAS les URLs signées**
   ```typescript
   // ❌ MAUVAIS
   localStorage.setItem('signedUrl', url);
   ```

3. **Ne faites PAS de proxy côté client**
   ```typescript
   // ❌ MAUVAIS - laissez le backend gérer cela
   const proxyUrl = `/proxy?url=${encodeURIComponent(s3Url)}`;
   ```

4. **N'utilisez PAS les URLs signées pour l'affichage**
   ```typescript
   // ❌ MAUVAIS - les previews sont publics
   <img src={getSignedUrl(photo.preview_url)} />

   // ✅ BON
   <img src={photo.preview_url} />
   ```

---

## Exemples de code complets

### Hook React personnalisé pour les téléchargements

```typescript
// usePhotoDownload.ts
import { useState } from 'react';
import { useAuth } from './useAuth';

export function usePhotoDownload() {
  const [downloading, setDownloading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const { token } = useAuth();

  const downloadPhoto = async (photoId: string, photoTitle: string) => {
    setDownloading(true);
    setError(null);

    try {
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL}/api/downloads/photo/${photoId}`,
        {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'image/jpeg',
          },
        }
      );

      if (!response.ok) {
        if (response.status === 403) {
          throw new Error('Vous n\'avez pas acheté cette photo');
        } else if (response.status === 404) {
          throw new Error('Photo introuvable');
        } else {
          throw new Error('Erreur de téléchargement');
        }
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${photoTitle}.jpg`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur inconnue');
      throw err;
    } finally {
      setDownloading(false);
    }
  };

  const downloadOrder = async (orderId: string, orderNumber: string) => {
    setDownloading(true);
    setError(null);

    try {
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL}/api/downloads/order/${orderId}`,
        {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        }
      );

      if (!response.ok) {
        throw new Error('Erreur de téléchargement de la commande');
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `order-${orderNumber}.zip`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur inconnue');
      throw err;
    } finally {
      setDownloading(false);
    }
  };

  const downloadInvoice = async (orderId: string, orderNumber: string) => {
    setDownloading(true);
    setError(null);

    try {
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL}/api/downloads/invoice/${orderId}`,
        {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        }
      );

      if (!response.ok) {
        throw new Error('Erreur de téléchargement de la facture');
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `invoice-${orderNumber}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur inconnue');
      throw err;
    } finally {
      setDownloading(false);
    }
  };

  return {
    downloadPhoto,
    downloadOrder,
    downloadInvoice,
    downloading,
    error,
  };
}
```

### Composant de bouton de téléchargement

```tsx
// DownloadButton.tsx
import { usePhotoDownload } from '@/hooks/usePhotoDownload';

interface DownloadButtonProps {
  photoId: string;
  photoTitle: string;
}

export function DownloadButton({ photoId, photoTitle }: DownloadButtonProps) {
  const { downloadPhoto, downloading, error } = usePhotoDownload();

  const handleDownload = async () => {
    try {
      await downloadPhoto(photoId, photoTitle);
    } catch (err) {
      // L'erreur est déjà gérée dans le hook
      console.error('Download failed:', err);
    }
  };

  return (
    <div>
      <button
        onClick={handleDownload}
        disabled={downloading}
        className="download-button"
      >
        {downloading ? (
          <>
            <span className="spinner" />
            Téléchargement...
          </>
        ) : (
          <>
            <DownloadIcon />
            Télécharger
          </>
        )}
      </button>
      {error && (
        <div className="error-message">
          {error}
        </div>
      )}
    </div>
  );
}
```

### Composable Vue.js pour les téléchargements

```typescript
// usePhotoDownload.ts
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';

export function usePhotoDownload() {
  const downloading = ref(false);
  const error = ref<string | null>(null);
  const authStore = useAuthStore();

  const downloadPhoto = async (photoId: string, photoTitle: string) => {
    downloading.value = true;
    error.value = null;

    try {
      const response = await fetch(
        `${import.meta.env.VITE_API_URL}/api/downloads/photo/${photoId}`,
        {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Accept': 'image/jpeg',
          },
        }
      );

      if (!response.ok) {
        if (response.status === 403) {
          throw new Error('Vous n\'avez pas acheté cette photo');
        } else if (response.status === 404) {
          throw new Error('Photo introuvable');
        } else {
          throw new Error('Erreur de téléchargement');
        }
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${photoTitle}.jpg`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur inconnue';
      throw err;
    } finally {
      downloading.value = false;
    }
  };

  return {
    downloadPhoto,
    downloading,
    error,
  };
}
```

---

## Résumé des endpoints

| Endpoint | Auth | Type | Description |
|----------|------|------|-------------|
| `GET /api/downloads/photo/{id}` | ✅ Oui | Image JPEG | Téléchargement haute résolution |
| `GET /api/downloads/order/{id}` | ✅ Oui | ZIP | Toutes les photos d'une commande |
| `GET /api/downloads/invoice/{id}` | ✅ Oui | PDF | Facture de commande |
| `GET /api/downloads/preview/{id}` | ❌ Non | Image JPEG | Preview avec watermark |

---

## Support et questions

Pour toute question sur l'intégration S3 :
1. Consultez la documentation complète de l'API OpenAPI : `/storage/api-docs/api-docs.yaml`
2. Vérifiez les logs du backend en cas d'erreur
3. Assurez-vous que les tokens JWT sont valides et non expirés

## Configuration environnement

Variables d'environnement frontend recommandées :

```env
# .env.local (Next.js) ou .env (Vite)
NEXT_PUBLIC_API_URL=https://api.pouire.bf
NEXT_PUBLIC_S3_BUCKET_URL=https://pouire.s3.us-east-1.amazonaws.com

# Pour Next.js Image Optimization
NEXT_PUBLIC_IMAGE_DOMAINS=pouire.s3.us-east-1.amazonaws.com
```

---

**Note** : Cette documentation suppose que le backend est configuré avec S3. Le comportement est identique en mode local, seule l'origine des URLs change.
