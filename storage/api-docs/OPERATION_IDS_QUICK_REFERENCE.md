# OperationId Quick Reference

This document provides a quick lookup table for all endpoints that need operationId updates.

## Statistics

- **Total Endpoints:** 90
- **Need Update (Hash IDs):** 76 (84.4%)
- **Already Descriptive:** 14 (15.6%)

---

## Endpoints Needing Updates (76)

### Admin Endpoints (31)

#### Analytics (4)
```
GET    /api/admin/analytics/photographers      -> getAdminAnalyticsPhotographers
GET    /api/admin/analytics/revenue            -> getAdminAnalyticsRevenue
GET    /api/admin/analytics/sales              -> getAdminAnalyticsSales
GET    /api/admin/analytics/user-growth        -> getAdminAnalyticsUserGrowth
```

#### Dashboard (1)
```
GET    /api/admin/dashboard                    -> getAdminDashboard
```

#### Photographers (7)
```
GET    /api/admin/photographers                           -> getAdminPhotographers
GET    /api/admin/photographers/pending                   -> getAdminPhotographersPending
GET    /api/admin/photographers/{photographer}            -> getAdminPhotographer
PUT    /api/admin/photographers/{photographer}/activate   -> activateAdminPhotographer
PUT    /api/admin/photographers/{photographer}/approve    -> approveAdminPhotographer
PUT    /api/admin/photographers/{photographer}/reject     -> rejectAdminPhotographer
PUT    /api/admin/photographers/{photographer}/suspend    -> suspendAdminPhotographer
```

#### Photo Moderation (8)
```
GET    /api/admin/photos                           -> getAdminPhotos
GET    /api/admin/photos/pending                   -> getAdminPhotosPending
POST   /api/admin/photos/bulk-approve              -> bulkApproveAdminPhotos
POST   /api/admin/photos/bulk-reject               -> bulkRejectAdminPhotos
DELETE /api/admin/photos/{photo}                   -> deleteAdminPhoto
PUT    /api/admin/photos/{photo}/approve           -> approveAdminPhoto
PUT    /api/admin/photos/{photo}/reject            -> rejectAdminPhoto
PUT    /api/admin/photos/{photo}/toggle-featured   -> toggleAdminPhotoFeatured
```

#### Users (5)
```
GET    /api/admin/users                  -> getAdminUsers
GET    /api/admin/users/{user}           -> getAdminUser
DELETE /api/admin/users/{user}           -> deleteAdminUser
PUT    /api/admin/users/{user}/activate  -> activateAdminUser
PUT    /api/admin/users/{user}/suspend   -> suspendAdminUser
```

#### Withdrawals (6)
```
GET    /api/admin/withdrawals                        -> getAdminWithdrawals
GET    /api/admin/withdrawals/pending                -> getAdminWithdrawalsPending
GET    /api/admin/withdrawals/{withdrawal}           -> getAdminWithdrawal
PUT    /api/admin/withdrawals/{withdrawal}/approve   -> approveAdminWithdrawal
PUT    /api/admin/withdrawals/{withdrawal}/complete  -> completeAdminWithdrawal
PUT    /api/admin/withdrawals/{withdrawal}/reject    -> rejectAdminWithdrawal
```

---

### Cart (5)
```
GET    /api/cart                  -> getCart
DELETE /api/cart                  -> clearCart
POST   /api/cart/items            -> addCartItem
PUT    /api/cart/items/{index}    -> updateCartItem
DELETE /api/cart/items/{index}    -> removeCartItem
```

---

### Downloads (4)
```
GET    /api/downloads/invoice/{order}   -> downloadOrderInvoice
GET    /api/downloads/order/{order}     -> downloadOrder
GET    /api/downloads/photo/{photo}     -> downloadPhoto
GET    /api/downloads/preview/{photo}   -> downloadPhotoPreview
```

---

### Favorites (3)
```
GET    /api/user/favorites          -> getUserFavorites
POST   /api/user/favorites/{photo}  -> addPhotoToFavorites
DELETE /api/user/favorites/{photo}  -> removePhotoFromFavorites
```

---

### Notifications (5)
```
GET    /api/user/notifications                      -> getUserNotifications
GET    /api/user/notifications/unread               -> getUserUnreadNotifications
PUT    /api/user/notifications/read-all             -> markAllNotificationsAsRead
DELETE /api/user/notifications/{notification}       -> deleteUserNotification
PUT    /api/user/notifications/{notification}/read  -> markNotificationAsRead
```

---

### Orders (5)
```
GET    /api/orders                -> getOrders
POST   /api/orders                -> createOrder
GET    /api/orders/{order}        -> getOrder
POST   /api/orders/{order}/pay    -> payOrder
GET    /api/orders/{order}/status -> getOrderStatus
```

---

### Photographer Endpoints (17)

#### Analytics (2)
```
GET    /api/photographer/analytics/popular-photos  -> getPhotographerPopularPhotos
GET    /api/photographer/analytics/sales           -> getPhotographerSalesAnalytics
```

#### Dashboard (2)
```
GET    /api/photographer/dashboard        -> getPhotographerDashboard
GET    /api/photographer/dashboard/stats  -> getPhotographerDashboardStats
```

#### Photos (5)
```
GET    /api/photographer/photos         -> getPhotographerPhotos
POST   /api/photographer/photos         -> uploadPhoto
GET    /api/photographer/photos/{photo} -> getPhotographerPhoto
PUT    /api/photographer/photos/{photo} -> updatePhotographerPhoto
DELETE /api/photographer/photos/{photo} -> deletePhotographerPhoto
```

#### Revenue (4)
```
GET    /api/photographer/revenue           -> getPhotographerRevenue
GET    /api/photographer/revenue/available -> getPhotographerAvailableRevenue
GET    /api/photographer/revenue/history   -> getPhotographerRevenueHistory
GET    /api/photographer/revenue/pending   -> getPhotographerPendingRevenue
```

#### Withdrawals (4)
```
GET    /api/photographer/withdrawals               -> getPhotographerWithdrawals
POST   /api/photographer/withdrawals               -> createWithdrawalRequest
GET    /api/photographer/withdrawals/{withdrawal}  -> getPhotographerWithdrawal
DELETE /api/photographer/withdrawals/{withdrawal}  -> cancelWithdrawalRequest
```

---

### User Profile (4)
```
GET    /api/user/profile          -> getUserProfile
PUT    /api/user/profile          -> updateUserProfile
POST   /api/user/profile/avatar   -> uploadUserAvatar
PUT    /api/user/profile/password -> updateUserPassword
```

---

### Webhooks (2)
```
POST   /api/webhooks/cinetpay                -> handleCinetpayWebhook
GET    /api/webhooks/cinetpay/return/{order} -> handleCinetpayReturn
```

---

## Endpoints Already Correct (14)

These endpoints already have proper descriptive operationIds:

### Authentication (5)
```
POST   /api/auth/login    -> login
POST   /api/auth/logout   -> logout
GET    /api/auth/me       -> getAuthenticatedUser
POST   /api/auth/refresh  -> refreshToken
POST   /api/auth/register -> register
```

### Categories (2)
```
GET    /api/categories             -> getCategories
GET    /api/categories/{slugOrId}  -> getCategory
```

### Photos (6)
```
GET    /api/photos                  -> getPhotos
GET    /api/photos/featured         -> getFeaturedPhotos
GET    /api/photos/popular          -> getPopularPhotos
GET    /api/photos/recent           -> getRecentPhotos
GET    /api/photos/{photo}          -> getPhoto
GET    /api/photos/{photo}/similar  -> getSimilarPhotos
```

### Search (1)
```
GET    /api/search/photos -> searchPhotos
```

---

## Implementation Guide

### Option 1: Manual Update in Controllers

Update the OpenAPI annotations in your Laravel controllers:

```php
/**
 * @OA\Get(
 *     path="/api/admin/photos",
 *     operationId="getAdminPhotos",
 *     tags={"Admin - Photo Moderation"},
 *     ...
 * )
 */
public function index()
{
    // ...
}
```

### Option 2: Bulk Update via JSON Mapping

Use the generated JSON mapping file at:
`c:\laragon\www\pourier-back\storage\api-docs\operation-ids-mapping.json`

This file contains all current and suggested operationIds for programmatic updates.

### Option 3: Direct OpenAPI Spec Update

Directly update the `storage/api-docs/api-docs.json` file with the suggested operationIds.

---

## Naming Pattern Reference

| Pattern | Example | Use Case |
|---------|---------|----------|
| `get{Resources}` | `getPhotos` | List all resources |
| `get{Resource}` | `getPhoto` | Get single resource |
| `store{Resource}` | `storePhoto` | Create new resource |
| `update{Resource}` | `updatePhoto` | Update existing resource |
| `delete{Resource}` | `deletePhoto` | Delete resource |
| `{action}{Resource}` | `approvePhoto` | Special action on resource |
| `bulk{Action}{Resources}` | `bulkApprovePhotos` | Bulk operation |
| `get{Resource}{Filter}` | `getPhotosPending` | Filtered list |
| `{action}{Resource}{Noun}` | `uploadUserAvatar` | Action on sub-resource |

---

**Generated:** 2025-11-17
**For:** Pourier Back API
**Full Analysis:** See `OPERATION_IDS_ANALYSIS.md`
