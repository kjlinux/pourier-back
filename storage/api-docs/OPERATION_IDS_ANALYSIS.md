# OpenAPI OperationId Analysis Report

**Generated:** 2025-11-17
**API Specification:** c:\laragon\www\pouire-back\storage\api-docs\api-docs.json

## Summary

-   **Total Endpoints:** 90
-   **Endpoints with Hash IDs (Need Update):** 76 (84.4%)
-   **Endpoints with Descriptive IDs:** 14 (15.6%)

## Status

The majority of endpoints currently use MD5 hash values as operationIds instead of descriptive, human-readable identifiers. This analysis provides suggested operationIds following Laravel and REST API naming conventions.

---

## Naming Conventions Used

### Standard CRUD Operations

-   **GET (list):** `get{Resources}` - e.g., `getPhotos`, `getAdminUsers`
-   **GET (single):** `get{Resource}` - e.g., `getPhoto`, `getAdminUser`
-   **POST (create):** `store{Resource}` - e.g., `storePhoto`, `storeOrder`
-   **PUT/PATCH (update):** `update{Resource}` - e.g., `updatePhoto`, `updateUser`
-   **DELETE:** `delete{Resource}` - e.g., `deletePhoto`, `deleteUser`

### Special Actions

-   **Action verbs:** `{verb}{Resource}` - e.g., `approvePhoto`, `rejectPhoto`, `suspendUser`
-   **Bulk operations:** `bulk{Action}{Resources}` - e.g., `bulkApprovePhotos`
-   **Sub-resources:** `get{Resource}{SubResource}` - e.g., `getPhotographerRevenue`
-   **Filtered lists:** `get{Resource}{Filter}` - e.g., `getPhotosFeatured`, `getAdminPhotosPending`

---

## Detailed Endpoint Analysis

### Admin - Analytics (4 endpoints)

| Status | Method | Path                               | Suggested OperationId            |
| ------ | ------ | ---------------------------------- | -------------------------------- |
| [HASH] | GET    | /api/admin/analytics/photographers | `getAdminAnalyticsPhotographers` |
| [HASH] | GET    | /api/admin/analytics/revenue       | `getAdminAnalyticsRevenue`       |
| [HASH] | GET    | /api/admin/analytics/sales         | `getAdminAnalyticsSales`         |
| [HASH] | GET    | /api/admin/analytics/user-growth   | `getAdminAnalyticsUserGrowth`    |

---

### Admin - Dashboard (1 endpoint)

| Status | Method | Path                 | Suggested OperationId |
| ------ | ------ | -------------------- | --------------------- |
| [HASH] | GET    | /api/admin/dashboard | `getAdminDashboard`   |

---

### Admin - Photographers (7 endpoints)

| Status | Method | Path                                             | Suggested OperationId          |
| ------ | ------ | ------------------------------------------------ | ------------------------------ |
| [HASH] | GET    | /api/admin/photographers                         | `getAdminPhotographers`        |
| [HASH] | GET    | /api/admin/photographers/pending                 | `getAdminPhotographersPending` |
| [HASH] | GET    | /api/admin/photographers/{photographer}          | `getAdminPhotographer`         |
| [HASH] | PUT    | /api/admin/photographers/{photographer}/activate | `activateAdminPhotographer`    |
| [HASH] | PUT    | /api/admin/photographers/{photographer}/approve  | `approveAdminPhotographer`     |
| [HASH] | PUT    | /api/admin/photographers/{photographer}/reject   | `rejectAdminPhotographer`      |
| [HASH] | PUT    | /api/admin/photographers/{photographer}/suspend  | `suspendAdminPhotographer`     |

---

### Admin - Photo Moderation (8 endpoints)

| Status | Method | Path                                      | Suggested OperationId      |
| ------ | ------ | ----------------------------------------- | -------------------------- |
| [HASH] | GET    | /api/admin/photos                         | `getAdminPhotos`           |
| [HASH] | GET    | /api/admin/photos/pending                 | `getAdminPhotosPending`    |
| [HASH] | DELETE | /api/admin/photos/{photo}                 | `deleteAdminPhoto`         |
| [HASH] | PUT    | /api/admin/photos/{photo}/approve         | `approveAdminPhoto`        |
| [HASH] | PUT    | /api/admin/photos/{photo}/reject          | `rejectAdminPhoto`         |
| [HASH] | PUT    | /api/admin/photos/{photo}/toggle-featured | `toggleAdminPhotoFeatured` |
| [HASH] | POST   | /api/admin/photos/bulk-approve            | `bulkApproveAdminPhotos`   |
| [HASH] | POST   | /api/admin/photos/bulk-reject             | `bulkRejectAdminPhotos`    |

---

### Admin - Users (5 endpoints)

| Status | Method | Path                             | Suggested OperationId |
| ------ | ------ | -------------------------------- | --------------------- |
| [HASH] | GET    | /api/admin/users                 | `getAdminUsers`       |
| [HASH] | GET    | /api/admin/users/{user}          | `getAdminUser`        |
| [HASH] | DELETE | /api/admin/users/{user}          | `deleteAdminUser`     |
| [HASH] | PUT    | /api/admin/users/{user}/activate | `activateAdminUser`   |
| [HASH] | PUT    | /api/admin/users/{user}/suspend  | `suspendAdminUser`    |

---

### Admin - Withdrawals (6 endpoints)

| Status | Method | Path                                         | Suggested OperationId        |
| ------ | ------ | -------------------------------------------- | ---------------------------- |
| [HASH] | GET    | /api/admin/withdrawals                       | `getAdminWithdrawals`        |
| [HASH] | GET    | /api/admin/withdrawals/pending               | `getAdminWithdrawalsPending` |
| [HASH] | GET    | /api/admin/withdrawals/{withdrawal}          | `getAdminWithdrawal`         |
| [HASH] | PUT    | /api/admin/withdrawals/{withdrawal}/approve  | `approveAdminWithdrawal`     |
| [HASH] | PUT    | /api/admin/withdrawals/{withdrawal}/complete | `completeAdminWithdrawal`    |
| [HASH] | PUT    | /api/admin/withdrawals/{withdrawal}/reject   | `rejectAdminWithdrawal`      |

---

### Authentication (5 endpoints)

| Status | Method | Path               | Suggested OperationId  |
| ------ | ------ | ------------------ | ---------------------- |
| [OK]   | POST   | /api/auth/login    | `login`                |
| [OK]   | POST   | /api/auth/logout   | `logout`               |
| [OK]   | GET    | /api/auth/me       | `getAuthenticatedUser` |
| [OK]   | POST   | /api/auth/refresh  | `refreshToken`         |
| [OK]   | POST   | /api/auth/register | `register`             |

**Note:** Authentication endpoints already have proper descriptive operationIds.

---

### Cart (5 endpoints)

| Status | Method | Path                    | Suggested OperationId |
| ------ | ------ | ----------------------- | --------------------- |
| [HASH] | GET    | /api/cart               | `getCart`             |
| [HASH] | DELETE | /api/cart               | `clearCart`           |
| [HASH] | POST   | /api/cart/items         | `addCartItem`         |
| [HASH] | PUT    | /api/cart/items/{index} | `updateCartItem`      |
| [HASH] | DELETE | /api/cart/items/{index} | `removeCartItem`      |

---

### Categories (2 endpoints)

| Status | Method | Path                       | Suggested OperationId |
| ------ | ------ | -------------------------- | --------------------- |
| [OK]   | GET    | /api/categories            | `getCategories`       |
| [OK]   | GET    | /api/categories/{slugOrId} | `getCategory`         |

**Note:** Category endpoints already have proper descriptive operationIds.

---

### Downloads (4 endpoints)

| Status | Method | Path                           | Suggested OperationId  |
| ------ | ------ | ------------------------------ | ---------------------- |
| [HASH] | GET    | /api/downloads/invoice/{order} | `downloadOrderInvoice` |
| [HASH] | GET    | /api/downloads/order/{order}   | `downloadOrder`        |
| [HASH] | GET    | /api/downloads/photo/{photo}   | `downloadPhoto`        |
| [HASH] | GET    | /api/downloads/preview/{photo} | `downloadPhotoPreview` |

---

### Favorites (3 endpoints)

| Status | Method | Path                        | Suggested OperationId      |
| ------ | ------ | --------------------------- | -------------------------- |
| [HASH] | GET    | /api/user/favorites         | `getUserFavorites`         |
| [HASH] | POST   | /api/user/favorites/{photo} | `addPhotoToFavorites`      |
| [HASH] | DELETE | /api/user/favorites/{photo} | `removePhotoFromFavorites` |

---

### Notifications (5 endpoints)

| Status | Method | Path                                        | Suggested OperationId        |
| ------ | ------ | ------------------------------------------- | ---------------------------- |
| [HASH] | GET    | /api/user/notifications                     | `getUserNotifications`       |
| [HASH] | GET    | /api/user/notifications/unread              | `getUserUnreadNotifications` |
| [HASH] | DELETE | /api/user/notifications/{notification}      | `deleteUserNotification`     |
| [HASH] | PUT    | /api/user/notifications/{notification}/read | `markNotificationAsRead`     |
| [HASH] | PUT    | /api/user/notifications/read-all            | `markAllNotificationsAsRead` |

---

### Orders (5 endpoints)

| Status | Method | Path                       | Suggested OperationId |
| ------ | ------ | -------------------------- | --------------------- |
| [HASH] | GET    | /api/orders                | `getOrders`           |
| [HASH] | POST   | /api/orders                | `createOrder`         |
| [HASH] | GET    | /api/orders/{order}        | `getOrder`            |
| [HASH] | POST   | /api/orders/{order}/pay    | `payOrder`            |
| [HASH] | GET    | /api/orders/{order}/status | `getOrderStatus`      |

---

### Photographer - Analytics (2 endpoints)

| Status | Method | Path                                       | Suggested OperationId           |
| ------ | ------ | ------------------------------------------ | ------------------------------- |
| [HASH] | GET    | /api/photographer/analytics/popular-photos | `getPhotographerPopularPhotos`  |
| [HASH] | GET    | /api/photographer/analytics/sales          | `getPhotographerSalesAnalytics` |

---

### Photographer - Dashboard (2 endpoints)

| Status | Method | Path                              | Suggested OperationId           |
| ------ | ------ | --------------------------------- | ------------------------------- |
| [HASH] | GET    | /api/photographer/dashboard       | `getPhotographerDashboard`      |
| [HASH] | GET    | /api/photographer/dashboard/stats | `getPhotographerDashboardStats` |

---

### Photographer - Photos (5 endpoints)

| Status | Method | Path                             | Suggested OperationId     |
| ------ | ------ | -------------------------------- | ------------------------- |
| [HASH] | GET    | /api/photographer/photos         | `getPhotographerPhotos`   |
| [HASH] | POST   | /api/photographer/photos         | `uploadPhoto`             |
| [HASH] | GET    | /api/photographer/photos/{photo} | `getPhotographerPhoto`    |
| [HASH] | PUT    | /api/photographer/photos/{photo} | `updatePhotographerPhoto` |
| [HASH] | DELETE | /api/photographer/photos/{photo} | `deletePhotographerPhoto` |

---

### Photographer - Revenue (4 endpoints)

| Status | Method | Path                                | Suggested OperationId             |
| ------ | ------ | ----------------------------------- | --------------------------------- |
| [HASH] | GET    | /api/photographer/revenue           | `getPhotographerRevenue`          |
| [HASH] | GET    | /api/photographer/revenue/available | `getPhotographerAvailableRevenue` |
| [HASH] | GET    | /api/photographer/revenue/history   | `getPhotographerRevenueHistory`   |
| [HASH] | GET    | /api/photographer/revenue/pending   | `getPhotographerPendingRevenue`   |

---

### Photographer - Withdrawals (4 endpoints)

| Status | Method | Path                                       | Suggested OperationId        |
| ------ | ------ | ------------------------------------------ | ---------------------------- |
| [HASH] | GET    | /api/photographer/withdrawals              | `getPhotographerWithdrawals` |
| [HASH] | POST   | /api/photographer/withdrawals              | `createWithdrawalRequest`    |
| [HASH] | GET    | /api/photographer/withdrawals/{withdrawal} | `getPhotographerWithdrawal`  |
| [HASH] | DELETE | /api/photographer/withdrawals/{withdrawal} | `cancelWithdrawalRequest`    |

---

### Photos (6 endpoints)

| Status | Method | Path                        | Suggested OperationId |
| ------ | ------ | --------------------------- | --------------------- |
| [OK]   | GET    | /api/photos                 | `getPhotos`           |
| [OK]   | GET    | /api/photos/featured        | `getFeaturedPhotos`   |
| [OK]   | GET    | /api/photos/popular         | `getPopularPhotos`    |
| [OK]   | GET    | /api/photos/recent          | `getRecentPhotos`     |
| [OK]   | GET    | /api/photos/{photo}         | `getPhoto`            |
| [OK]   | GET    | /api/photos/{photo}/similar | `getSimilarPhotos`    |

**Note:** Photo endpoints already have proper descriptive operationIds.

---

### Search (1 endpoint)

| Status | Method | Path               | Suggested OperationId |
| ------ | ------ | ------------------ | --------------------- |
| [OK]   | GET    | /api/search/photos | `searchPhotos`        |

**Note:** Search endpoint already has proper descriptive operationId.

---

### User Profile (4 endpoints)

| Status | Method | Path                       | Suggested OperationId |
| ------ | ------ | -------------------------- | --------------------- |
| [HASH] | GET    | /api/user/profile          | `getUserProfile`      |
| [HASH] | PUT    | /api/user/profile          | `updateUserProfile`   |
| [HASH] | POST   | /api/user/profile/avatar   | `uploadUserAvatar`    |
| [HASH] | PUT    | /api/user/profile/password | `updateUserPassword`  |

---

### Webhooks (2 endpoints)

| Status | Method | Path                                  | Suggested OperationId   |
| ------ | ------ | ------------------------------------- | ----------------------- |
| [HASH] | POST   | /api/webhooks/cinetpay                | `handleCinetpayWebhook` |
| [HASH] | GET    | /api/webhooks/cinetpay/return/{order} | `handleCinetpayReturn`  |

---

## Recommendations

1. **Update Hash-Based OperationIds:** Replace all 76 hash-based operationIds with the suggested descriptive names.

2. **Consistency:** Ensure all operationIds follow the same naming convention pattern for better API documentation and client SDK generation.

3. **Controller Methods:** Consider aligning controller method names with the operationIds for better code organization.

4. **OpenAPI Annotations:** Update Laravel OpenAPI annotations in controllers to use the suggested operationIds.

5. **Documentation Generation:** After updating, regenerate API documentation to ensure client SDKs and API documentation reflect the new descriptive operationIds.

## Implementation Note

A JSON mapping file has been generated at:
`c:\laragon\www\pouire-back\storage\api-docs\operation-ids-mapping.json`

This file contains the complete mapping and can be used for automated updates to the OpenAPI specification or controller annotations.
