<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class WithdrawalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/withdrawals",
     *     tags={"Admin - Withdrawals"},
     *     summary="Get all withdrawals with filtering",
     *     description="Retrieve all withdrawal requests with filtering by status and photographer. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by withdrawal status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "approved", "rejected", "completed"}, example="pending")
     *     ),
     *     @OA\Parameter(
     *         name="photographer_id",
     *         in="query",
     *         description="Filter by photographer UUID",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawals retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=450)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Withdrawal::with('photographer:id,first_name,last_name,email');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by photographer
        if ($request->has('photographer_id')) {
            $query->where('photographer_id', $request->input('photographer_id'));
        }

        $withdrawals = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/withdrawals/pending",
     *     tags={"Admin - Withdrawals"},
     *     summary="Get pending withdrawals",
     *     description="Retrieve all pending withdrawal requests ordered by creation date. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pending withdrawals retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer", example=15)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     )
     * )
     */
    public function pending(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::with('photographer:id,first_name,last_name,email')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/withdrawals/{withdrawal}",
     *     tags={"Admin - Withdrawals"},
     *     summary="Get withdrawal details",
     *     description="Retrieve detailed information about a specific withdrawal request including photographer contact information. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="withdrawal",
     *         in="path",
     *         description="Withdrawal UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Withdrawal not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Demande de retrait introuvable")
     *         )
     *     )
     * )
     */
    public function show(Withdrawal $withdrawal): JsonResponse
    {
        $withdrawal->load('photographer:id,first_name,last_name,email,phone');

        return response()->json([
            'success' => true,
            'data' => $withdrawal,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/withdrawals/{withdrawal}/approve",
     *     tags={"Admin - Withdrawals"},
     *     summary="Approve withdrawal request",
     *     description="Approve a pending withdrawal request with optional transaction reference and admin notes. Only pending requests can be approved. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="withdrawal",
     *         in="path",
     *         description="Withdrawal UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="transaction_reference", type="string", maxLength=255, example="TXN-2024-001234", description="Transaction reference number"),
     *             @OA\Property(property="admin_notes", type="string", maxLength=500, example="Payment processed via bank transfer", description="Admin notes for internal tracking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Demande de retrait approuvée avec succès."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Only pending requests can be approved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seules les demandes en attente peuvent être approuvées.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Withdrawal not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Demande de retrait introuvable")
     *         )
     *     )
     * )
     */
    public function approve(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $request->validate([
            'transaction_reference' => 'nullable|string|max:255',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les demandes en attente peuvent être approuvées.',
            ], 400);
        }

        $withdrawal->update([
            'status' => 'approved',
            'processed_at' => now(),
            'transaction_reference' => $request->input('transaction_reference'),
            'admin_notes' => $request->input('admin_notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de retrait approuvée avec succès.',
            'data' => $withdrawal->load('photographer'),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/withdrawals/{withdrawal}/reject",
     *     tags={"Admin - Withdrawals"},
     *     summary="Reject withdrawal request",
     *     description="Reject a pending withdrawal request with required rejection reason. Only pending requests can be rejected. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="withdrawal",
     *         in="path",
     *         description="Withdrawal UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rejection_reason"},
     *             @OA\Property(property="rejection_reason", type="string", maxLength=500, example="Insufficient balance or invalid payment details", description="Reason for rejection")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Demande de retrait rejetée avec succès."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Only pending requests can be rejected or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seules les demandes en attente peuvent être rejetées.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Withdrawal not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Demande de retrait introuvable")
     *         )
     *     )
     * )
     */
    public function reject(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les demandes en attente peuvent être rejetées.',
            ], 400);
        }

        $withdrawal->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande de retrait rejetée avec succès.',
            'data' => $withdrawal->load('photographer'),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/withdrawals/{withdrawal}/complete",
     *     tags={"Admin - Withdrawals"},
     *     summary="Mark withdrawal as completed",
     *     description="Mark an approved withdrawal as completed (payment sent) with required transaction reference. Only approved requests can be marked as completed. Requires admin role.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="withdrawal",
     *         in="path",
     *         description="Withdrawal UUID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="9d445a1c-85c5-4b6d-9c38-99a4915d6dac")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_reference"},
     *             @OA\Property(property="transaction_reference", type="string", maxLength=255, example="TXN-2024-001234", description="Transaction reference number confirming payment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal marked as completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Retrait marqué comme complété avec succès."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Only approved requests can be marked as completed or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seules les demandes approuvées peuvent être marquées comme complétées.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Withdrawal not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Demande de retrait introuvable")
     *         )
     *     )
     * )
     */
    public function complete(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $request->validate([
            'transaction_reference' => 'required|string|max:255',
        ]);

        if ($withdrawal->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les demandes approuvées peuvent être marquées comme complétées.',
            ], 400);
        }

        $withdrawal->update([
            'status' => 'completed',
            'transaction_reference' => $request->input('transaction_reference'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Retrait marqué comme complété avec succès.',
            'data' => $withdrawal,
        ]);
    }
}
