# Exigences Backend - Historique des Achats

Ce document décrit les champs et fonctionnalités que le backend doit implémenter/vérifier pour que l'historique des achats fonctionne correctement côté frontend.

## Endpoints API Existants (à vérifier)

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/orders` | GET | Liste des commandes de l'utilisateur authentifié |
| `/api/orders/{order}` | GET | Détails d'une commande |
| `/api/downloads/photo/{photo}` | GET | Télécharger une photo HD achetée |
| `/api/downloads/order/{order}` | GET | Télécharger toutes les photos d'une commande en ZIP |
| `/api/downloads/invoice/{order}` | GET | Télécharger la facture PDF |

---

## Structure des données requises

### 1. Objet `Order` (Commande)

Le frontend attend les champs suivants dans la réponse de `GET /api/orders` :

```json
{
  "data": [
    {
      "id": "uuid",
      "order_number": "ORD-2024-001",
      "user_id": "uuid",
      "subtotal": 15000,
      "total": 15000,
      "payment_status": "completed|pending|failed|refunded",
      "payment_method": "mobile_money|card",
      "transaction_id": "CINETPAY-123456",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:35:00Z",
      "items": [
        {
          "id": "uuid",
          "photo_id": "uuid",
          "license_type": "standard|extended",
          "price": 5000,
          "photo": {
            "id": "uuid",
            "title": "Titre de la photo",
            "preview_url": "https://...",
            "thumbnail_url": "https://...",
            "photographer": {
              "id": "uuid",
              "name": "John Doe",
              "display_name": "John Photography"
            }
          }
        }
      ]
    }
  ],
  "meta": {
    "total": 10,
    "current_page": 1,
    "last_page": 2
  }
}
```

---

## Champs critiques à inclure

### Dans chaque `Order` :

| Champ | Type | Description | Critique |
|-------|------|-------------|----------|
| `id` | string (UUID) | ID unique de la commande | ✅ Oui |
| `order_number` | string | Numéro lisible de la commande | ✅ Oui |
| `subtotal` | number | Sous-total en FCFA | ✅ Oui |
| `total` | number | Total final en FCFA | ✅ Oui |
| `payment_status` | string | Statut: completed, pending, failed, refunded | ✅ Oui |
| `payment_method` | string | Méthode: mobile_money, card | ✅ Oui |
| `transaction_id` | string | ID de transaction CinetPay | ⚠️ Optionnel |
| `created_at` | datetime | Date de création | ✅ Oui |
| `items` | array | Liste des items de la commande | ✅ Oui |

### Dans chaque `OrderItem` :

| Champ | Type | Description | Critique |
|-------|------|-------------|----------|
| `id` | string (UUID) | ID unique de l'item | ✅ Oui |
| `photo_id` | string (UUID) | ID de la photo | ✅ Oui |
| `license_type` | string | Type de licence: standard, extended | ✅ Oui |
| `price` | number | Prix de l'item en FCFA | ✅ Oui |
| `photo` | object | Objet Photo complet (voir ci-dessous) | ✅ Oui |

### Dans l'objet `photo` imbriqué :

| Champ | Type | Description | Critique |
|-------|------|-------------|----------|
| `id` | string (UUID) | ID de la photo | ✅ Oui |
| `title` | string | Titre de la photo | ✅ Oui |
| `preview_url` | string | URL de la prévisualisation | ✅ Oui |
| `thumbnail_url` | string | URL de la miniature (fallback) | ⚠️ Recommandé |
| `photographer` | object | Objet Photographe | ✅ Oui |

### Dans l'objet `photographer` imbriqué :

| Champ | Type | Description | Critique |
|-------|------|-------------|----------|
| `id` | string (UUID) | ID du photographe | ⚠️ Optionnel |
| `name` | string | Nom du photographe | ✅ Oui |
| `display_name` | string | Nom d'affichage | ⚠️ Recommandé |

---

## Endpoints de téléchargement

### `GET /api/downloads/photo/{photo}`

- **Authentification** : Requise
- **Autorisation** : L'utilisateur doit avoir une commande complétée contenant cette photo
- **Réponse** : Fichier image (blob) en haute résolution
- **Headers suggérés** :
  ```
  Content-Type: image/jpeg (ou autre)
  Content-Disposition: attachment; filename="photo-title.jpg"
  ```

### `GET /api/downloads/order/{order}`

- **Authentification** : Requise
- **Autorisation** : L'utilisateur doit être propriétaire de la commande et la commande doit être complétée
- **Réponse** : Fichier ZIP contenant toutes les photos HD
- **Headers suggérés** :
  ```
  Content-Type: application/zip
  Content-Disposition: attachment; filename="order-ORD-2024-001.zip"
  ```

### `GET /api/downloads/invoice/{order}`

- **Authentification** : Requise
- **Autorisation** : L'utilisateur doit être propriétaire de la commande
- **Réponse** : Fichier PDF de la facture
- **Headers suggérés** :
  ```
  Content-Type: application/pdf
  Content-Disposition: attachment; filename="Facture-ORD-2024-001.pdf"
  ```

---

## Points importants à vérifier

### 1. Relations Eloquent
Assurez-vous que les relations sont bien chargées :

```php
// Dans OrderController@index
$orders = Order::with(['items.photo.photographer'])
    ->where('user_id', auth()->id())
    ->orderBy('created_at', 'desc')
    ->paginate($perPage);
```

### 2. Format de réponse
La pagination doit suivre le format Laravel standard avec `data` et `meta`.

### 3. Sécurité des téléchargements
- Vérifier que l'utilisateur a bien acheté la photo avant de permettre le téléchargement HD
- Les fichiers HD doivent être stockés dans un répertoire non-public
- Générer des URLs signées ou utiliser le streaming via le contrôleur

### 4. Génération des factures PDF
Le backend doit générer automatiquement les factures PDF lors de la complétion du paiement. Inclure :
- Informations de l'acheteur
- Détails des items achetés
- Prix unitaires et total
- Numéro de facture unique
- Date

---

## Statuts de paiement

| Statut | Description |
|--------|-------------|
| `pending` | Commande créée, paiement en attente |
| `completed` | Paiement réussi, téléchargements disponibles |
| `failed` | Paiement échoué |
| `refunded` | Commande remboursée |

---

## Checklist Backend

- [ ] L'endpoint `GET /api/orders` retourne les commandes avec les relations `items.photo.photographer`
- [ ] Chaque `Order` contient `payment_method` et `transaction_id`
- [ ] Chaque `OrderItem` contient l'objet `photo` complet avec `title`, `preview_url`, et `photographer`
- [ ] L'endpoint `GET /api/downloads/photo/{photo}` vérifie l'achat et retourne le fichier HD
- [ ] L'endpoint `GET /api/downloads/order/{order}` génère un ZIP des photos HD
- [ ] L'endpoint `GET /api/downloads/invoice/{order}` retourne la facture PDF
- [ ] Les réponses utilisent les bons Content-Type et Content-Disposition

---

## Contact

Si vous avez des questions sur les exigences frontend, référez-vous au fichier :
- `src/pages/user/Orders.jsx` - Page d'historique des commandes
- `src/services/orderService.ts` - Service d'appel API
- `src/api/services/DownloadsService.ts` - Service de téléchargement généré
