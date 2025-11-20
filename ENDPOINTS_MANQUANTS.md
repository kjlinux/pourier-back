# Endpoints API Manquants - AfroLens

Ce document liste tous les endpoints API qui doivent être implémentés côté backend pour compléter l'intégration frontend-backend.

## Statut Actuel
- **Migration API**: 95% complète
- **Endpoints critiques manquants**: 4
- **Endpoints analytics manquants**: 7

---

## 1. Endpoints Critiques

### 1.1. Tracking des Vues de Photos

**Endpoint**: `POST /api/photos/{id}/view`

**Description**: Incrémenter le compteur de vues d'une photo lorsqu'un utilisateur la consulte.

**Paramètres**:
- `id` (path parameter): ID de la photo

**Corps de requête**: Aucun

**Réponse attendue**:
```json
{
  "success": true,
  "data": {
    "photo_id": "uuid",
    "views_count": 123
  }
}
```

**Cas d'utilisation**:
- Appelé automatiquement quand un utilisateur ouvre la page de détail d'une photo
- Permet de suivre la popularité des photos
- Utilisé dans les statistiques photographe

**Fichier frontend concerné**: `src/services/photoService.ts:196`

---

### 1.2. Demande de Réinitialisation de Mot de Passe

**Endpoint**: `POST /api/auth/forgot-password`

**Description**: Initier le processus de réinitialisation de mot de passe en envoyant un email avec un lien de réinitialisation.

**Corps de requête**:
```json
{
  "email": "utilisateur@example.com"
}
```

**Réponse attendue**:
```json
{
  "success": true,
  "message": "Un email de réinitialisation a été envoyé à votre adresse."
}
```

**Validation**:
- Email requis et doit être valide
- Email doit exister dans la base de données
- Rate limiting: max 3 requêtes par 15 minutes par email

**Cas d'utilisation**:
- Page "Mot de passe oublié"
- Email contient un token unique valide 1 heure

**Fichier frontend concerné**: `src/services/authService.ts:187`

---

### 1.3. Réinitialisation de Mot de Passe

**Endpoint**: `POST /api/auth/reset-password`

**Description**: Finaliser la réinitialisation du mot de passe avec le token reçu par email.

**Corps de requête**:
```json
{
  "token": "reset_token_from_email",
  "email": "utilisateur@example.com",
  "password": "nouveau_mot_de_passe",
  "password_confirmation": "nouveau_mot_de_passe"
}
```

**Réponse attendue**:
```json
{
  "success": true,
  "message": "Votre mot de passe a été réinitialisé avec succès."
}
```

**Validation**:
- Token requis et doit être valide (non expiré)
- Email requis et doit correspondre au token
- Password requis, min 8 caractères
- Password confirmation doit correspondre

**Cas d'utilisation**:
- Page accessible via le lien dans l'email
- Après succès, redirection vers la page de connexion

**Fichier frontend concerné**: `src/services/authService.ts:203`

---

### 1.4. Photos par Catégorie (Optionnel)

**Endpoint**: `GET /api/categories/{id}/photos`

**Description**: Récupérer toutes les photos d'une catégorie spécifique. *Note: Cette fonctionnalité peut être réalisée avec SearchService, mais un endpoint dédié serait plus performant.*

**Paramètres**:
- `id` (path parameter): ID de la catégorie
- `per_page` (query, optional): Nombre de résultats par page (défaut: 20)
- `page` (query, optional): Numéro de page (défaut: 1)
- `sort_by` (query, optional): Tri (date, popularity, price_asc, price_desc)

**Réponse attendue**:
```json
{
  "data": [
    {
      "id": "uuid",
      "title": "Photo Title",
      "description": "Description",
      "image_url": "https://...",
      "thumbnail_url": "https://...",
      "price": 5000,
      "category_id": "uuid",
      "photographer": {...},
      "views_count": 123,
      "favorites_count": 45
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 98
  }
}
```

**Alternative actuelle**: Le frontend utilise `SearchService.searchPhotos()` avec le paramètre `categories: [categoryId]`

**Fichier frontend concerné**: `src/services/photoService.ts:167`

---

## 2. Endpoints Analytics (Photographe)

Ces endpoints sont nécessaires pour fournir des données de visualisation dans le dashboard photographe. Actuellement, des données mockées sont générées côté frontend.

### 2.1. Vues au Fil du Temps

**Endpoint**: `GET /api/photographer/analytics/views-over-time`

**Description**: Récupérer l'historique des vues de photos du photographe sur une période donnée.

**Paramètres**:
- `period` (query, required): Période d'analyse (`7d`, `30d`, `90d`)

**Réponse attendue**:
```json
{
  "data": [
    {
      "date": "2025-01-01",
      "views": 145
    },
    {
      "date": "2025-01-02",
      "views": 167
    }
  ],
  "summary": {
    "total_views": 5432,
    "average_daily_views": 181,
    "change_percentage": 12.5
  }
}
```

**Logique métier**:
- Agréger les vues par jour
- Calculer la variation par rapport à la période précédente
- Pour 7d: 7 points de données
- Pour 30d: 30 points de données (ou agrégation par semaine)
- Pour 90d: 90 points de données (ou agrégation par semaine)

---

### 2.2. Ventes au Fil du Temps

**Endpoint**: `GET /api/photographer/analytics/sales-over-time`

**Description**: Récupérer l'historique des ventes du photographe.

**Paramètres**:
- `period` (query, required): Période d'analyse (`7d`, `30d`, `90d`)

**Réponse attendue**:
```json
{
  "data": [
    {
      "date": "2025-01-01",
      "sales": 12
    },
    {
      "date": "2025-01-02",
      "sales": 8
    }
  ],
  "summary": {
    "total_sales": 156,
    "average_daily_sales": 5.2,
    "change_percentage": -3.2
  }
}
```

**Logique métier**:
- Compter le nombre de photos vendues par jour
- Inclure uniquement les commandes payées
- Calculer la variation par rapport à la période précédente

---

### 2.3. Revenus au Fil du Temps

**Endpoint**: `GET /api/photographer/analytics/revenue-over-time`

**Description**: Récupérer l'historique des revenus du photographe.

**Paramètres**:
- `period` (query, required): Période d'analyse (`7d`, `30d`, `90d`)

**Réponse attendue**:
```json
{
  "data": [
    {
      "date": "2025-01-01",
      "revenue": 45000
    },
    {
      "date": "2025-01-02",
      "revenue": 32000
    }
  ],
  "summary": {
    "total_revenue": 567000,
    "average_daily_revenue": 18900,
    "change_percentage": 8.7
  }
}
```

**Logique métier**:
- Sommer les revenus par jour (après commission plateforme)
- Revenus = prix_photo × (1 - commission_plateforme)
- Commission typique: 15-20%

---

### 2.4. Taux de Conversion au Fil du Temps

**Endpoint**: `GET /api/photographer/analytics/conversion-over-time`

**Description**: Récupérer l'évolution du taux de conversion (vues → ventes).

**Paramètres**:
- `period` (query, required): Période d'analyse (`7d`, `30d`, `90d`)

**Réponse attendue**:
```json
{
  "data": [
    {
      "date": "2025-01-01",
      "conversion_rate": 2.3
    },
    {
      "date": "2025-01-02",
      "conversion_rate": 1.8
    }
  ],
  "summary": {
    "average_conversion_rate": 2.1,
    "change_percentage": 0.5
  }
}
```

**Calcul**:
```
taux_conversion = (nombre_ventes / nombre_vues) × 100
```

---

### 2.5. Distribution Horaire

**Endpoint**: `GET /api/photographer/analytics/hourly-distribution`

**Description**: Analyser les heures de la journée où les photos sont le plus consultées/achetées.

**Paramètres**:
- `period` (query, required): Période d'analyse (`7d`, `30d`, `90d`)
- `metric` (query, optional): Métrique à analyser (`views`, `sales`) - défaut: `views`

**Réponse attendue**:
```json
{
  "data": [
    {
      "hour": 0,
      "value": 45
    },
    {
      "hour": 1,
      "value": 23
    },
    ...
    {
      "hour": 23,
      "value": 67
    }
  ],
  "peak_hours": [18, 19, 20],
  "lowest_hours": [3, 4, 5]
}
```

**Logique métier**:
- 24 points de données (0-23h)
- Agréger les événements par heure sur la période
- Identifier les heures de pointe et les heures creuses

---

### 2.6. Performance par Catégorie

**Endpoint**: `GET /api/photographer/analytics/category-performance`

**Description**: Analyser les performances des photos par catégorie.

**Paramètres**:
- `period` (query, required): Période d'analyse (`7d`, `30d`, `90d`)

**Réponse attendue**:
```json
{
  "data": [
    {
      "category_id": "uuid",
      "category_name": "Sport",
      "total_sales": 45,
      "total_revenue": 225000,
      "total_views": 3456,
      "conversion_rate": 1.3,
      "average_price": 5000
    },
    {
      "category_id": "uuid",
      "category_name": "Culture",
      "total_sales": 32,
      "total_revenue": 192000,
      "total_views": 2890,
      "conversion_rate": 1.1,
      "average_price": 6000
    }
  ],
  "top_category": {
    "by_sales": "Sport",
    "by_revenue": "Culture",
    "by_conversion": "Portraits"
  }
}
```

**Logique métier**:
- Grouper par catégorie
- Calculer les métriques par catégorie
- Trier par revenus (descendant)

---

### 2.7. Insights Audience

**Endpoint**: `GET /api/photographer/analytics/audience-insights`

**Description**: Fournir des informations sur l'audience (géographie, appareils, sources de trafic).

**Paramètres**:
- `period` (query, required): Période d'analyse (`7d`, `30d`, `90d`)

**Réponse attendue**:
```json
{
  "geographic": [
    {
      "country": "Sénégal",
      "country_code": "SN",
      "visits": 3456,
      "percentage": 45.2
    },
    {
      "country": "Côte d'Ivoire",
      "country_code": "CI",
      "visits": 2134,
      "percentage": 27.9
    }
  ],
  "devices": [
    {
      "device_type": "mobile",
      "visits": 5234,
      "percentage": 68.5
    },
    {
      "device_type": "desktop",
      "visits": 1890,
      "percentage": 24.7
    },
    {
      "device_type": "tablet",
      "visits": 520,
      "percentage": 6.8
    }
  ],
  "referrers": [
    {
      "source": "direct",
      "visits": 3456,
      "percentage": 45.2
    },
    {
      "source": "google",
      "visits": 2134,
      "percentage": 27.9
    },
    {
      "source": "facebook",
      "visits": 1234,
      "percentage": 16.1
    },
    {
      "source": "instagram",
      "visits": 820,
      "percentage": 10.8
    }
  ]
}
```

**Logique métier**:
- Collecter via User-Agent et IP
- Respect RGPD: anonymisation des données
- Top 5 pays, appareils, et sources

**Note**: Nécessite tracking côté backend (middleware analytics ou intégration service tiers)

---

## 3. Implémentation Recommandée

### Phase 1: Endpoints Critiques (Priorité Haute)
1. ✅ `POST /api/photos/{id}/view` - Tracking vues
2. ✅ `POST /api/auth/forgot-password` - Mot de passe oublié
3. ✅ `POST /api/auth/reset-password` - Réinitialisation

### Phase 2: Analytics de Base (Priorité Moyenne)
4. ✅ `GET /api/photographer/analytics/views-over-time`
5. ✅ `GET /api/photographer/analytics/sales-over-time`
6. ✅ `GET /api/photographer/analytics/revenue-over-time`

### Phase 3: Analytics Avancés (Priorité Basse)
7. ✅ `GET /api/photographer/analytics/conversion-over-time`
8. ✅ `GET /api/photographer/analytics/hourly-distribution`
9. ✅ `GET /api/photographer/analytics/category-performance`

### Phase 4: Analytics Audience (Optionnel)
10. ⬜ `GET /api/photographer/analytics/audience-insights` (nécessite tracking avancé)

---

## 4. Considérations Techniques

### Performance
- **Caching**: Les données analytics doivent être cachées (Redis) car elles ne changent pas en temps réel
- **TTL recommandé**: 5-15 minutes pour les analytics
- **Agrégation**: Utiliser des tables d'agrégation pré-calculées pour les périodes 30d et 90d

### Sécurité
- **Authentification**: Tous les endpoints analytics nécessitent un token JWT valide
- **Authorization**: Photographe ne peut voir que ses propres données
- **Rate Limiting**: 100 requêtes/minute pour les analytics

### Base de Données
- **Views Tracking**: Ajouter une table `photo_views` avec (photo_id, user_id, ip, created_at)
- **Analytics Tables**: Créer des tables d'agrégation journalières pour optimiser les requêtes
  - `daily_photo_stats` (photo_id, date, views, sales, revenue)
  - `daily_photographer_stats` (photographer_id, date, total_views, total_sales, total_revenue)

### Validation
- Tous les paramètres `period` doivent accepter uniquement: `7d`, `30d`, `90d`
- Les dates de retour doivent être au format ISO 8601 (YYYY-MM-DD)
- Les montants en FCFA (entier, pas de décimales)

---

## 5. Tests Recommandés

Pour chaque endpoint:
- ✅ Test unitaire: logique métier
- ✅ Test d'intégration: base de données
- ✅ Test de performance: temps de réponse < 500ms
- ✅ Test de sécurité: authorization & authentication

---

## 6. Documentation API

Une fois implémentés, ces endpoints doivent être:
1. Documentés dans Swagger/OpenAPI
2. Ajoutés au fichier de génération du client TypeScript
3. Testés avec Postman/Insomnia (collection partagée)

---

## Contact

Pour toute question sur ces spécifications, contacter l'équipe frontend ou créer une issue dans le repository.

**Date de création**: 2025-01-19
**Dernière mise à jour**: 2025-01-19
**Version**: 1.0
