# Guide d'Intégration Frontend - Paiement OTP Ligdicash

## Vue d'ensemble

Ce document explique comment intégrer le nouveau flux de paiement OTP (One-Time Password) pour Orange Money et Ligdicash Wallet dans votre application frontend.

## 🎯 Différences entre les flux de paiement

### Flux avec Redirection (Existant)
- **Providers**: MTN, MOOV, WAVE, Cartes bancaires
- **Processus**: L'utilisateur est redirigé vers la page Ligdicash
- **Endpoints**: `POST /api/orders/{order}/pay`

### Flux OTP (Nouveau)
- **Providers**: ORANGE, LIGDICASH_WALLET
- **Processus**: L'utilisateur reste sur votre application et entre un code OTP reçu par SMS
- **Endpoints**:
  - `POST /api/orders/{order}/request-otp`
  - `POST /api/orders/{order}/validate-otp`

---

## 📋 Étapes d'implémentation

### Étape 1: Détection du type de flux

Lors de la sélection du provider de paiement, vérifiez s'il supporte l'OTP:

```javascript
// Configuration des providers
const OTP_PROVIDERS = ['ORANGE', 'LIGDICASH_WALLET'];
const REDIRECT_PROVIDERS = ['MTN', 'MOOV', 'WAVE'];

// Fonction pour déterminer le flux
function getPaymentFlow(provider) {
  if (OTP_PROVIDERS.includes(provider)) {
    return 'otp';
  }
  return 'redirect';
}

// Exemple d'utilisation
const selectedProvider = 'ORANGE';
const paymentFlow = getPaymentFlow(selectedProvider); // 'otp'
```

### Étape 2: Interface utilisateur pour le choix du provider

```jsx
// Exemple React/Vue - Sélection du provider
const PaymentProviderSelection = () => {
  const [provider, setProvider] = useState('');

  return (
    <div>
      <h3>Choisissez votre moyen de paiement</h3>

      {/* Providers OTP */}
      <div className="otp-providers">
        <label>
          <input
            type="radio"
            name="provider"
            value="ORANGE"
            onChange={(e) => setProvider(e.target.value)}
          />
          Orange Money (Paiement rapide par OTP)
        </label>

        <label>
          <input
            type="radio"
            name="provider"
            value="LIGDICASH_WALLET"
            onChange={(e) => setProvider(e.target.value)}
          />
          Ligdicash Wallet (Paiement rapide par OTP)
        </label>
      </div>

      {/* Providers avec redirection */}
      <div className="redirect-providers">
        <label>
          <input
            type="radio"
            name="provider"
            value="MTN"
            onChange={(e) => setProvider(e.target.value)}
          />
          MTN Mobile Money
        </label>

        <label>
          <input
            type="radio"
            name="provider"
            value="MOOV"
            onChange={(e) => setProvider(e.target.value)}
          />
          Moov Money
        </label>
      </div>
    </div>
  );
};
```

---

## 🔄 Flux OTP - Implémentation complète

### Étape 3: Demander un code OTP

#### Requête API

```javascript
// Fonction pour demander un OTP
async function requestOTP(orderId, phoneNumber, provider) {
  try {
    const response = await fetch(`/api/orders/${orderId}/request-otp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${accessToken}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        phone: phoneNumber,           // Format: "+226 70 12 34 56"
        payment_provider: provider    // "ORANGE" ou "LIGDICASH_WALLET"
      })
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // OTP envoyé avec succès
      return {
        success: true,
        expiresAt: data.data.expires_at,      // ISO 8601 date
        attemptsRemaining: data.data.attempts_remaining, // 2, 1, ou 0
        message: data.message
      };
    } else {
      // Erreur lors de l'envoi de l'OTP
      return {
        success: false,
        message: data.message
      };
    }
  } catch (error) {
    console.error('Erreur réseau:', error);
    return {
      success: false,
      message: 'Erreur de connexion au serveur'
    };
  }
}
```

#### Exemple d'utilisation

```javascript
// Exemple complet
const handleRequestOTP = async () => {
  setLoading(true);
  setError(null);

  const result = await requestOTP(
    order.id,
    '+226 70 12 34 56',
    'ORANGE'
  );

  if (result.success) {
    // Afficher le formulaire de saisie OTP
    setShowOTPInput(true);
    setOTPExpiresAt(new Date(result.expiresAt));
    setAttemptsRemaining(result.attemptsRemaining);

    // Démarrer le compte à rebours (5 minutes)
    startCountdown(result.expiresAt);

    // Afficher un message de succès
    toast.success(result.message);
  } else {
    // Afficher l'erreur
    setError(result.message);
    toast.error(result.message);
  }

  setLoading(false);
};
```

### Étape 4: Interface de saisie du code OTP

```jsx
// Composant React pour saisir l'OTP
const OTPInput = ({ orderId, onSuccess, onError }) => {
  const [otp, setOTP] = useState('');
  const [timeRemaining, setTimeRemaining] = useState(300); // 5 minutes en secondes
  const [loading, setLoading] = useState(false);

  // Compte à rebours
  useEffect(() => {
    if (timeRemaining <= 0) return;

    const timer = setInterval(() => {
      setTimeRemaining(prev => {
        if (prev <= 1) {
          clearInterval(timer);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  // Formater le temps restant (MM:SS)
  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  return (
    <div className="otp-input-container">
      <h3>Entrez le code OTP</h3>
      <p>Un code à 6 chiffres a été envoyé à votre téléphone par SMS</p>

      {/* Compte à rebours */}
      <div className="countdown">
        {timeRemaining > 0 ? (
          <span className="timer">⏱️ Expire dans: {formatTime(timeRemaining)}</span>
        ) : (
          <span className="expired">❌ Code expiré</span>
        )}
      </div>

      {/* Champ de saisie OTP */}
      <input
        type="text"
        inputMode="numeric"
        pattern="[0-9]*"
        maxLength={6}
        value={otp}
        onChange={(e) => setOTP(e.target.value.replace(/\D/g, ''))}
        placeholder="000000"
        className="otp-field"
        disabled={timeRemaining === 0}
      />

      {/* Bouton de validation */}
      <button
        onClick={() => handleValidateOTP(orderId, otp)}
        disabled={otp.length !== 6 || timeRemaining === 0 || loading}
        className="validate-btn"
      >
        {loading ? 'Validation en cours...' : 'Valider le paiement'}
      </button>

      {/* Bouton pour redemander un OTP */}
      {timeRemaining === 0 && (
        <button
          onClick={() => handleRequestNewOTP(orderId)}
          className="resend-btn"
        >
          Demander un nouveau code
        </button>
      )}
    </div>
  );
};
```

### Étape 5: Valider le code OTP

#### Requête API

```javascript
// Fonction pour valider l'OTP
async function validateOTP(orderId, otpCode) {
  try {
    const response = await fetch(`/api/orders/${orderId}/validate-otp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${accessToken}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        otp: otpCode  // Code à 6 chiffres
      })
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // Paiement réussi
      return {
        success: true,
        orderId: data.data.order_id,
        transactionId: data.data.transaction_id,
        message: data.message
      };
    } else {
      // Erreur de validation
      return {
        success: false,
        message: data.message // "Code OTP invalide", "OTP expiré", etc.
      };
    }
  } catch (error) {
    console.error('Erreur réseau:', error);
    return {
      success: false,
      message: 'Erreur de connexion au serveur'
    };
  }
}
```

#### Exemple d'utilisation

```javascript
const handleValidateOTP = async (orderId, otpCode) => {
  setLoading(true);
  setError(null);

  const result = await validateOTP(orderId, otpCode);

  if (result.success) {
    // Paiement réussi !
    toast.success(result.message);

    // Rediriger vers la page de succès
    router.push(`/orders/${result.orderId}/success`);
  } else {
    // Erreur de validation
    setError(result.message);
    toast.error(result.message);

    // Si le code est invalide, permettre une nouvelle tentative
    setOTP('');
  }

  setLoading(false);
};
```

---

## 🔄 Flux avec Redirection (Inchangé)

Pour les providers qui ne supportent pas l'OTP (MTN, MOOV, WAVE), utilisez le flux existant:

```javascript
async function initiateRedirectPayment(orderId, provider, phone) {
  try {
    const response = await fetch(`/api/orders/${orderId}/pay`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${accessToken}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        payment_method: 'mobile_money',
        payment_provider: provider,  // "MTN", "MOOV", "WAVE"
        phone: phone
      })
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // Rediriger vers la page de paiement Ligdicash
      window.location.href = data.data.payment_url;
    } else {
      toast.error(data.message);
    }
  } catch (error) {
    console.error('Erreur:', error);
  }
}
```

---

## 📊 Structure des données OrderResource

Lorsque vous récupérez une commande (`GET /api/orders/{order}`), vous recevez les champs OTP suivants:

```json
{
  "id": "uuid-de-la-commande",
  "order_number": "ORD-20251219-ABC123",
  "total": 15000,

  // Champs de paiement existants
  "payment_method": "mobile_money",
  "payment_provider": "ORANGE",
  "payment_status": "pending",
  "payment_id": null,
  "ligdicash_token": null,

  // Nouveaux champs OTP
  "payment_flow": "otp",                    // "otp" ou "redirect"
  "payment_phone": "+226 70 12 34 56",      // Numéro utilisé pour l'OTP
  "otp_requested_at": "2025-12-19T14:30:00Z",
  "otp_expires_at": "2025-12-19T14:35:00Z", // OTP expire après 5 minutes
  "otp_request_count": 1,                    // Nombre de tentatives (max 3)
  "can_request_otp": true,                   // Peut-on demander un nouvel OTP?
  "otp_expired": false,                      // L'OTP est-il expiré?
  "supports_otp": true,                      // Le provider supporte-t-il l'OTP?

  // Autres champs...
  "created_at": "2025-12-19T14:25:00Z",
  "updated_at": "2025-12-19T14:30:00Z"
}
```

---

## 🎨 Exemple complet d'implémentation (React)

```jsx
import { useState, useEffect } from 'react';

const PaymentPage = ({ order }) => {
  const [provider, setProvider] = useState('');
  const [phone, setPhone] = useState('');
  const [paymentFlow, setPaymentFlow] = useState(null);

  // État pour le flux OTP
  const [showOTPInput, setShowOTPInput] = useState(false);
  const [otp, setOTP] = useState('');
  const [otpExpiresAt, setOTPExpiresAt] = useState(null);
  const [attemptsRemaining, setAttemptsRemaining] = useState(3);
  const [timeRemaining, setTimeRemaining] = useState(0);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const OTP_PROVIDERS = ['ORANGE', 'LIGDICASH_WALLET'];

  // Déterminer le flux de paiement lors de la sélection du provider
  useEffect(() => {
    if (provider) {
      setPaymentFlow(OTP_PROVIDERS.includes(provider) ? 'otp' : 'redirect');
    }
  }, [provider]);

  // Compte à rebours pour l'OTP
  useEffect(() => {
    if (!otpExpiresAt) return;

    const updateTimer = () => {
      const now = new Date();
      const diff = Math.floor((new Date(otpExpiresAt) - now) / 1000);
      setTimeRemaining(Math.max(0, diff));
    };

    updateTimer();
    const interval = setInterval(updateTimer, 1000);

    return () => clearInterval(interval);
  }, [otpExpiresAt]);

  // Demander un code OTP
  const handleRequestOTP = async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await fetch(`/api/orders/${order.id}/request-otp`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          phone: phone,
          payment_provider: provider
        })
      });

      const data = await response.json();

      if (response.ok && data.success) {
        setShowOTPInput(true);
        setOTPExpiresAt(data.data.expires_at);
        setAttemptsRemaining(data.data.attempts_remaining);
        alert(data.message);
      } else {
        setError(data.message);
      }
    } catch (err) {
      setError('Erreur de connexion');
    } finally {
      setLoading(false);
    }
  };

  // Valider l'OTP
  const handleValidateOTP = async () => {
    if (otp.length !== 6) {
      setError('Le code OTP doit contenir 6 chiffres');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await fetch(`/api/orders/${order.id}/validate-otp`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ otp })
      });

      const data = await response.json();

      if (response.ok && data.success) {
        // Paiement réussi - rediriger
        window.location.href = `/orders/${data.data.order_id}/success`;
      } else {
        setError(data.message);
        setOTP(''); // Réinitialiser pour nouvelle tentative
      }
    } catch (err) {
      setError('Erreur de connexion');
    } finally {
      setLoading(false);
    }
  };

  // Paiement avec redirection
  const handleRedirectPayment = async () => {
    setLoading(true);

    try {
      const response = await fetch(`/api/orders/${order.id}/pay`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          payment_method: 'mobile_money',
          payment_provider: provider,
          phone: phone
        })
      });

      const data = await response.json();

      if (response.ok && data.success) {
        window.location.href = data.data.payment_url;
      } else {
        setError(data.message);
      }
    } catch (err) {
      setError('Erreur de connexion');
    } finally {
      setLoading(false);
    }
  };

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  return (
    <div className="payment-page">
      <h2>Paiement de la commande {order.order_number}</h2>
      <p>Montant: {order.total} FCFA</p>

      {error && <div className="error">{error}</div>}

      {!showOTPInput ? (
        <div className="payment-form">
          {/* Sélection du provider */}
          <div className="provider-selection">
            <h3>Choisissez votre moyen de paiement</h3>

            <label>
              <input type="radio" name="provider" value="ORANGE"
                onChange={(e) => setProvider(e.target.value)} />
              Orange Money 🟠 (Paiement rapide par OTP)
            </label>

            <label>
              <input type="radio" name="provider" value="LIGDICASH_WALLET"
                onChange={(e) => setProvider(e.target.value)} />
              Ligdicash Wallet 💰 (Paiement rapide par OTP)
            </label>

            <label>
              <input type="radio" name="provider" value="MTN"
                onChange={(e) => setProvider(e.target.value)} />
              MTN Mobile Money 🟡
            </label>

            <label>
              <input type="radio" name="provider" value="MOOV"
                onChange={(e) => setProvider(e.target.value)} />
              Moov Money 🔵
            </label>
          </div>

          {/* Numéro de téléphone */}
          {provider && (
            <div className="phone-input">
              <label>Numéro de téléphone</label>
              <input
                type="tel"
                placeholder="+226 70 12 34 56"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
              />
            </div>
          )}

          {/* Bouton de paiement */}
          {provider && phone && (
            <button
              onClick={paymentFlow === 'otp' ? handleRequestOTP : handleRedirectPayment}
              disabled={loading}
              className="pay-button"
            >
              {loading ? 'Chargement...' :
               paymentFlow === 'otp' ? 'Recevoir le code OTP' : 'Procéder au paiement'}
            </button>
          )}
        </div>
      ) : (
        <div className="otp-input-section">
          <h3>Entrez le code OTP</h3>
          <p>Un code à 6 chiffres a été envoyé au {phone}</p>

          {/* Compte à rebours */}
          <div className="countdown">
            {timeRemaining > 0 ? (
              <span className="timer">⏱️ Expire dans: {formatTime(timeRemaining)}</span>
            ) : (
              <span className="expired">❌ Code expiré</span>
            )}
          </div>

          {/* Tentatives restantes */}
          <p className="attempts">
            Tentatives restantes: {attemptsRemaining}/3
          </p>

          {/* Champ OTP */}
          <input
            type="text"
            inputMode="numeric"
            pattern="[0-9]*"
            maxLength={6}
            value={otp}
            onChange={(e) => setOTP(e.target.value.replace(/\D/g, ''))}
            placeholder="000000"
            className="otp-field"
            disabled={timeRemaining === 0}
            autoFocus
          />

          {/* Bouton de validation */}
          <button
            onClick={handleValidateOTP}
            disabled={otp.length !== 6 || timeRemaining === 0 || loading}
            className="validate-button"
          >
            {loading ? 'Validation...' : 'Valider le paiement'}
          </button>

          {/* Redemander un OTP */}
          {(timeRemaining === 0 && attemptsRemaining > 0) && (
            <button
              onClick={() => {
                setShowOTPInput(false);
                setOTP('');
                handleRequestOTP();
              }}
              className="resend-button"
            >
              Demander un nouveau code
            </button>
          )}

          {/* Limite atteinte */}
          {attemptsRemaining === 0 && (
            <div className="limit-reached">
              <p>⚠️ Nombre maximum de tentatives atteint</p>
              <button onClick={() => window.location.reload()}>
                Créer une nouvelle commande
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default PaymentPage;
```

---

## 🎨 Styles CSS recommandés

```css
/* Formulaire de paiement */
.payment-page {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
}

.error {
  background-color: #fee;
  color: #c00;
  padding: 10px;
  border-radius: 5px;
  margin-bottom: 15px;
}

.provider-selection {
  margin: 20px 0;
}

.provider-selection label {
  display: block;
  padding: 15px;
  margin: 10px 0;
  border: 2px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
}

.provider-selection label:hover {
  border-color: #007bff;
  background-color: #f8f9fa;
}

.provider-selection input[type="radio"] {
  margin-right: 10px;
}

/* Champ OTP */
.otp-field {
  width: 100%;
  font-size: 32px;
  text-align: center;
  letter-spacing: 10px;
  padding: 15px;
  border: 2px solid #007bff;
  border-radius: 8px;
  margin: 20px 0;
}

.otp-field:focus {
  outline: none;
  border-color: #0056b3;
  box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
}

/* Compte à rebours */
.countdown {
  text-align: center;
  margin: 15px 0;
  font-size: 18px;
}

.countdown .timer {
  color: #28a745;
  font-weight: bold;
}

.countdown .expired {
  color: #dc3545;
  font-weight: bold;
}

/* Boutons */
.pay-button,
.validate-button {
  width: 100%;
  padding: 15px;
  font-size: 18px;
  background-color: #28a745;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.pay-button:hover,
.validate-button:hover {
  background-color: #218838;
}

.pay-button:disabled,
.validate-button:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

.resend-button {
  width: 100%;
  padding: 12px;
  margin-top: 10px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.resend-button:hover {
  background-color: #0056b3;
}

/* Limite atteinte */
.limit-reached {
  text-align: center;
  padding: 20px;
  background-color: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 8px;
  margin-top: 20px;
}

.attempts {
  text-align: center;
  color: #6c757d;
  font-size: 14px;
}
```

---

## ⚠️ Gestion des erreurs

### Codes d'erreur courants

| Code HTTP | Message | Action recommandée |
|-----------|---------|-------------------|
| 422 | "Le format du numéro est invalide" | Afficher un message d'aide sur le format (+226 XX XX XX XX) |
| 422 | "Ce fournisseur ne supporte pas le paiement par OTP" | Rediriger vers le flux de redirection |
| 422 | "Nombre maximum de tentatives (3) atteint" | Proposer de créer une nouvelle commande |
| 422 | "Le code OTP a expiré" | Proposer de demander un nouveau code |
| 400 | "Code OTP invalide ou paiement refusé" | Permettre une nouvelle saisie |
| 400 | "Erreur lors de l'envoi du code OTP" | Réessayer ou changer de provider |
| 403 | "Accès non autorisé" | Rediriger vers la connexion |

### Exemple de gestion d'erreurs

```javascript
const handleAPIError = (response, data) => {
  switch (response.status) {
    case 422:
      // Erreur de validation
      if (data.message.includes('maximum de tentatives')) {
        return {
          type: 'limit_reached',
          message: 'Trop de tentatives. Veuillez créer une nouvelle commande.',
          action: 'create_new_order'
        };
      }
      if (data.message.includes('expiré')) {
        return {
          type: 'expired',
          message: 'Le code OTP a expiré.',
          action: 'request_new_otp'
        };
      }
      break;

    case 400:
      // Erreur métier
      if (data.message.includes('invalide')) {
        return {
          type: 'invalid_otp',
          message: 'Code OTP incorrect. Veuillez réessayer.',
          action: 'retry'
        };
      }
      break;

    case 403:
      // Non autorisé
      return {
        type: 'unauthorized',
        message: 'Session expirée. Veuillez vous reconnecter.',
        action: 'redirect_login'
      };

    default:
      return {
        type: 'unknown',
        message: 'Une erreur est survenue. Veuillez réessayer.',
        action: 'retry'
      };
  }
};
```

---

## 📱 Responsive Design

Assurez-vous que l'interface OTP fonctionne bien sur mobile:

```css
/* Mobile first */
@media (max-width: 768px) {
  .payment-page {
    padding: 10px;
  }

  .otp-field {
    font-size: 24px;
    letter-spacing: 5px;
  }

  .provider-selection label {
    font-size: 14px;
    padding: 12px;
  }
}
```

---

## ✅ Checklist d'implémentation

- [ ] Détecter automatiquement le type de flux selon le provider
- [ ] Implémenter le formulaire de demande d'OTP
- [ ] Afficher un compte à rebours de 5 minutes
- [ ] Permettre la saisie d'un code à 6 chiffres
- [ ] Valider le format de l'OTP côté client (6 chiffres uniquement)
- [ ] Afficher le nombre de tentatives restantes
- [ ] Proposer de redemander un OTP si expiré
- [ ] Bloquer après 3 tentatives
- [ ] Gérer tous les codes d'erreur possibles
- [ ] Tester sur mobile et desktop
- [ ] Ajouter des messages de succès/erreur clairs en français
- [ ] Implémenter l'auto-focus sur le champ OTP
- [ ] Empêcher la saisie de lettres dans le champ OTP
- [ ] Désactiver les boutons pendant le chargement

---

## 🔐 Bonnes pratiques de sécurité

1. **Ne jamais stocker l'OTP**: Ne gardez jamais le code OTP en localStorage ou sessionStorage
2. **Masquer le numéro**: Affichez le numéro partiellement (ex: +226 XX XX XX 56)
3. **HTTPS obligatoire**: Assurez-vous que toutes les requêtes passent par HTTPS
4. **Token d'authentification**: Toujours inclure le Bearer token dans les headers
5. **Validation côté client**: Validez le format avant l'envoi, mais ne faites jamais confiance uniquement à la validation client

---

## 📞 Support

Pour toute question sur l'implémentation:
- Consultez la documentation API complète
- Vérifiez les logs dans la console du navigateur
- Testez d'abord en environnement de staging avec `LIGDICASH_PLATFORM=test`

---

**Dernière mise à jour**: 19 décembre 2025
**Version API**: 1.0
**Compatibilité**: Orange Money, Ligdicash Wallet (OTP) | MTN, Moov, Wave (Redirection)
