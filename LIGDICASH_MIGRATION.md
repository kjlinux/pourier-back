# Migration de CinetPay vers Ligdicash

Ce document décrit la migration du système de paiement de CinetPay vers Ligdicash pour la plateforme Pouire.

## Changements effectués

### 1. Configuration

#### Fichiers modifiés :
- `config/services.php` - Configuration Ligdicash ajoutée
- `.env.example` - Variables d'environnement Ligdicash

#### Variables d'environnement requises :

```env
LIGDICASH_API_URL=https://app.ligdicash.com/pay/v01/redirect/checkout-invoice
LIGDICASH_API_KEY=votre_api_key
LIGDICASH_AUTH_TOKEN=votre_bearer_token
LIGDICASH_PLATFORM=test # test ou live
LIGDICASH_CALLBACK_URL="${APP_URL}/api/webhooks/ligdicash"
LIGDICASH_RETURN_URL="${APP_URL}/payment/callback"
LIGDICASH_CANCEL_URL="${APP_URL}/payment/cancel"
LIGDICASH_STORE_NAME=Pouire
LIGDICASH_STORE_WEBSITE=https://pouire.bf
```

### 2. Base de données

#### Migration créée :
- `2025_11_27_114357_add_ligdicash_token_to_orders_table.php`

#### Changements :
- Ajout de la colonne `ligdicash_token` (nullable string)
- Suppression de la colonne `cinetpay_transaction_id`

#### Pour exécuter la migration :
```bash
php artisan migrate
```

### 3. Services

#### PaymentService (`app/Services/PaymentService.php`)

**Méthode `processPayment()` :**
- Adapté pour l'API Ligdicash
- Structure de données conforme à Ligdicash :
  - Création d'une facture avec items détaillés
  - Informations du store (nom, site web)
  - URLs de callback, return et cancel
  - Custom data pour traçabilité

**Méthode `checkPaymentStatus()` :**
- Appel à l'endpoint de confirmation Ligdicash
- Vérification : `response_code === '00'` ET `status === 'completed'`

### 4. Contrôleurs

#### WebhookController (`app/Http/Controllers/Api/WebhookController.php`)

**Nouvelles méthodes :**

1. `handleLigdicashWebhook()` - Callback POST de Ligdicash
   - Reçoit 2 requêtes (form-urlencoded et JSON)
   - Vérifie le statut du paiement
   - Prévient la duplication avec vérification `isCompleted()`
   - Complète la commande si succès

2. `handleLigdicashReturn()` - Redirection après paiement réussi
   - Vérifie le statut final
   - Redirige vers frontend success/failed

3. `handleLigdicashCancel()` - Redirection après annulation
   - Marque la commande comme échouée
   - Redirige vers frontend cancelled

### 5. Routes

#### Fichier `routes/api.php`

**Anciennes routes (supprimées) :**
```php
POST /api/webhooks/cinetpay
GET  /api/webhooks/cinetpay/return/{order}
```

**Nouvelles routes :**
```php
POST /api/webhooks/ligdicash
GET  /api/webhooks/ligdicash/return/{order}
GET  /api/webhooks/ligdicash/cancel/{order}
```

### 6. Modèle Order

#### Fichier `app/Models/Order.php`

**Champs ajoutés au $fillable :**
- `ligdicash_token` (remplace `cinetpay_transaction_id`)

## Flux de paiement Ligdicash

### 1. Initiation du paiement

```
Client → POST /api/orders/{order}/pay
  ↓
PaymentService::processPayment()
  ↓
API Ligdicash: POST /pay/v01/redirect/checkout-invoice/create
  ↓
Response: { response_code: "00", token: "...", response_text: "url_paiement" }
  ↓
Mise à jour Order: ligdicash_token = token
  ↓
Retour Client: { payment_url, payment_token }
```

### 2. Paiement par l'utilisateur

```
Client redirigé vers payment_url (Ligdicash)
  ↓
Utilisateur effectue le paiement
  ↓
Ligdicash envoie 2 webhooks POST vers callback_url
```

### 3. Traitement du callback

```
Ligdicash → POST /api/webhooks/ligdicash
  ↓
WebhookController::handleLigdicashWebhook()
  ↓
Vérification: response_code === "00" && status === "completed"
  ↓
Order::update({ payment_status: completed, ligdicash_token, ... })
  ↓
PaymentService::completeOrder()
  - Génère les URLs de téléchargement
  - Incrémente les ventes/téléchargements
```

### 4. Redirection utilisateur

```
Ligdicash redirige vers return_url ou cancel_url
  ↓
GET /api/webhooks/ligdicash/return/{order}
  ↓
Vérification du statut final
  ↓
Redirection frontend: /orders/{id}/success ou /failed
```

## Structure des données Ligdicash

### Requête d'initiation de paiement

```json
{
  "commande": {
    "invoice": {
      "items": [
        {
          "name": "Titre de la photo",
          "description": "Photo par Nom Photographe - Licence: standard",
          "quantity": 1,
          "unit_price": 5000,
          "total_price": 5000
        }
      ],
      "total_amount": 5000,
      "devise": "XOF",
      "description": "Achat photos Pouire - Commande ORD-20231127-ABC123",
      "customer": "",
      "customer_firstname": "Jean",
      "customer_lastname": "Dupont",
      "customer_email": "jean.dupont@example.com",
      "external_id": "",
      "otp": ""
    },
    "store": {
      "name": "Pouire",
      "website_url": "https://pouire.bf"
    },
    "actions": {
      "cancel_url": "https://api.pouire.bf/api/webhooks/ligdicash/cancel/{order_id}",
      "return_url": "https://api.pouire.bf/api/webhooks/ligdicash/return/{order_id}",
      "callback_url": "https://api.pouire.bf/api/webhooks/ligdicash"
    },
    "custom_data": {
      "order_id": "uuid",
      "order_number": "ORD-20231127-ABC123",
      "user_id": "uuid"
    }
  }
}
```

### Réponse d'initiation

```json
{
  "response_code": "00",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "response_text": "https://app.ligdicash.com/pay/checkout/...",
  "description": "",
  "custom_data": {},
  "wiki": "https://developers.ligdicash.com/errors"
}
```

### Callback Webhook

```json
{
  "response_code": "00",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "status": "completed",
  "amount": 5000,
  "operator_name": "Orange Money",
  "custom_data": {
    "order_id": "uuid",
    "order_number": "ORD-20231127-ABC123",
    "user_id": "uuid"
  },
  "date": "2023-11-27 12:00:00",
  "customer": "+22997000000"
}
```

## Codes de statut Ligdicash

| Code | Statut | Description |
|------|--------|-------------|
| `00` | Success | Opération réussie |
| `01` | Error | Erreur lors du traitement |

| Status | Description |
|--------|-------------|
| `completed` | Paiement complété avec succès |
| `pending` | Paiement en attente |
| `notcompleted` | Paiement échoué ou annulé |

## Sécurité

### Points importants :

1. **Pas de vérification de signature** : Contrairement à CinetPay, Ligdicash ne documente pas de mécanisme de signature HMAC pour les webhooks. Il est recommandé de :
   - Vérifier que la commande existe
   - Vérifier le statut via l'API de confirmation
   - Éviter le traitement en double avec `isCompleted()`

2. **URLs sécurisées** : Toujours utiliser HTTPS pour les callbacks et return URLs

3. **Validation des montants** : Le webhook reçoit le montant - vous pouvez le comparer à `$order->total`

## Configuration Ligdicash Dashboard

Pour obtenir vos credentials :

1. Créez un compte sur https://client.ligdicash.com/
2. Créez un projet API
3. Récupérez :
   - `LIGDICASH_API_KEY` (Apikey)
   - `LIGDICASH_AUTH_TOKEN` (Bearer token)
4. Configurez l'URL de callback : `https://votre-domaine.com/api/webhooks/ligdicash`

## Tests

### En mode test :

```env
LIGDICASH_PLATFORM=test
```

### Vérification des endpoints :

1. **Créer une commande** : `POST /api/orders`
2. **Initier le paiement** : `POST /api/orders/{order}/pay`
3. **Simuler le callback** : `POST /api/webhooks/ligdicash` avec payload test
4. **Vérifier le statut** : `GET /api/orders/{order}/status`

## Migration en production

### Checklist avant déploiement :

- [ ] Configurer les credentials Ligdicash en production
- [ ] Changer `LIGDICASH_PLATFORM=live`
- [ ] Exécuter la migration de base de données
- [ ] Tester le flux complet en environnement de staging
- [ ] Vérifier les URLs de callback (HTTPS obligatoire)
- [ ] Vérifier les redirections frontend
- [ ] Tester avec différents moyens de paiement (Orange Money, MTN, Moov, Wave)
- [ ] Monitorer les logs pendant les premiers paiements

## Ressources

- **Documentation Ligdicash** : https://developers.ligdicash.com/
- **API Payin avec redirection** : https://developers.ligdicash.com/api1/payin-redirection
- **Callback documentation** : https://developers.ligdicash.com/api1/callback
- **SDK PHP Ligdicash** : https://github.com/Ligdicash/ligdicash-php

## Support

En cas de problème :
- Consulter les logs Laravel : `storage/logs/laravel.log`
- Vérifier les webhooks reçus dans les logs
- Contacter le support Ligdicash : support@ligdicash.com
