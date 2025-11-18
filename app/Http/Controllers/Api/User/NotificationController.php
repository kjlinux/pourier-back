<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user/notifications",
     *     operationId="getUserNotifications",
     *     tags={"Notifications"},
     *     summary="Get all user notifications",
     *     description="Retrieve all notifications for the authenticated user with pagination (20 per page)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notifications retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac"),
     *                         @OA\Property(property="user_id", type="string", format="uuid"),
     *                         @OA\Property(property="type", type="string", enum={"order_completed", "new_sale", "withdrawal_processed", "photo_approved", "photo_rejected"}, example="order_completed"),
     *                         @OA\Property(property="title", type="string", example="Commande terminée"),
     *                         @OA\Property(property="message", type="string", example="Votre commande #ORD-123456 a été traitée avec succès"),
     *                         @OA\Property(property="data", type="object", nullable=true, example={"order_id": "9d445a1c-85c5-4b6d-9c38-99a4915d6dac", "amount": 15000}),
     *                         @OA\Property(property="read_at", type="string", format="date-time", nullable=true, example="2024-01-15T10:30:00.000000Z"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:00:00.000000Z")
     *                     )
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="last_page", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    /**
     * @OA\Get(
     *     path="/api/user/notifications/unread",
     *     operationId="getUserNotificationsUnread",
     *     tags={"Notifications"},
     *     summary="Get unread notifications",
     *     description="Retrieve only unread notifications for the authenticated user (no pagination)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unread notifications retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac"),
     *                     @OA\Property(property="user_id", type="string", format="uuid"),
     *                     @OA\Property(property="type", type="string", enum={"order_completed", "new_sale", "withdrawal_processed", "photo_approved", "photo_rejected"}, example="new_sale"),
     *                     @OA\Property(property="title", type="string", example="Nouvelle vente"),
     *                     @OA\Property(property="message", type="string", example="Votre photo a été vendue pour 5000 FCFA"),
     *                     @OA\Property(property="data", type="object", nullable=true, example={"photo_id": "9d445a1c-85c5-4b6d-9c38-99a4915d6dac", "amount": 5000}),
     *                     @OA\Property(property="read_at", type="string", nullable=true, example=null),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:00:00.000000Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function unread(Request $request): JsonResponse
    {
        $unread = Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $unread]);
    }

    /**
     * @OA\Put(
     *     path="/api/user/notifications/{notification}/read",
     *     operationId="updateUserNotificationsRead",
     *     tags={"Notifications"},
     *     summary="Mark notification as read",
     *     description="Mark a specific notification as read for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="notification",
     *         in="path",
     *         description="Notification UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification marquée comme lue.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - notification doesn't belong to user",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non autorisé.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     )
     * )
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Notification marquée comme lue.']);
    }

    /**
     * @OA\Put(
     *     path="/api/user/notifications/read-all",
     *     operationId="readAllUserNotifications",
     *     tags={"Notifications"},
     *     summary="Mark all notifications as read",
     *     description="Mark all unread notifications as read for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Toutes les notifications marquées comme lues.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Toutes les notifications marquées comme lues.']);
    }

    /**
     * @OA\Delete(
     *     path="/api/user/notifications/{notification}",
     *     operationId="deleteUserNotifications",
     *     tags={"Notifications"},
     *     summary="Delete notification",
     *     description="Delete a specific notification for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="notification",
     *         in="path",
     *         description="Notification UUID to delete",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification supprimée.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - notification doesn't belong to user",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non autorisé.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     )
     * )
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notification supprimée.']);
    }
}
