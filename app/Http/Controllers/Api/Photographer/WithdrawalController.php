<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use App\Models\Revenue;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class WithdrawalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/photographer/withdrawals",
     *     tags={"Photographer - Withdrawals"},
     *     summary="List withdrawal requests",
     *     description="Get all withdrawal requests for the photographer with pagination, ordered by most recent first",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal requests retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="photographer_id", type="string", format="uuid"),
     *                         @OA\Property(property="amount", type="number", format="float", example=50000, description="Withdrawal amount in FCFA"),
     *                         @OA\Property(property="payment_method", type="string", enum={"mobile_money", "bank_transfer"}, example="mobile_money"),
     *                         @OA\Property(
     *                             property="payment_details",
     *                             type="object",
     *                             example={"phone": "+22670123456", "provider": "Orange Money"}
     *                         ),
     *                         @OA\Property(property="status", type="string", enum={"pending", "approved", "completed", "rejected"}, example="pending"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-14T10:00:00Z"),
     *                         @OA\Property(property="processed_at", type="string", format="date-time", nullable=true, example="2025-11-15T14:30:00Z")
     *                     )
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=15)
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
        $withdrawals = Withdrawal::where('photographer_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $withdrawals]);
    }

    /**
     * @OA\Post(
     *     path="/api/photographer/withdrawals",
     *     tags={"Photographer - Withdrawals"},
     *     summary="Create withdrawal request",
     *     description="Request a withdrawal from available balance. Minimum amount: 10,000 FCFA. Amount must not exceed available balance.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "payment_method", "payment_details"},
     *             @OA\Property(property="amount", type="integer", example=50000, description="Withdrawal amount in FCFA (minimum: 10,000)"),
     *             @OA\Property(property="payment_method", type="string", enum={"mobile_money", "bank_transfer"}, example="mobile_money"),
     *             @OA\Property(
     *                 property="payment_details",
     *                 type="object",
     *                 description="Payment details based on method",
     *                 example={"phone": "+22670123456", "provider": "Orange Money", "account_name": "Jean Dupont"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal request created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Demande créée."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="photographer_id", type="string", format="uuid"),
     *                 @OA\Property(property="amount", type="number", format="float", example=50000),
     *                 @OA\Property(property="payment_method", type="string", example="mobile_money"),
     *                 @OA\Property(property="payment_details", type="object"),
     *                 @OA\Property(property="status", type="string", example="pending")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Insufficient balance or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Montant indisponible.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         ref="#/components/responses/ValidationErrorResponse"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:10000',
            'payment_method' => 'required|in:mobile_money,bank_transfer',
            'payment_details' => 'required|array',
        ]);

        $available = Revenue::where('photographer_id', $request->user()->id)
            ->where('available_at', '<=', now())
            ->sum('photographer_amount');

        if ($request->amount > $available) {
            return response()->json(['success' => false, 'message' => 'Montant indisponible.'], 400);
        }

        $withdrawal = Withdrawal::create([
            'photographer_id' => $request->user()->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_details' => $request->payment_details,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Demande créée.', 'data' => $withdrawal]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/withdrawals/{withdrawal}",
     *     tags={"Photographer - Withdrawals"},
     *     summary="Get withdrawal details",
     *     description="Retrieve detailed information about a specific withdrawal request. Only the request owner can view.",
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
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="photographer_id", type="string", format="uuid"),
     *                 @OA\Property(property="amount", type="number", format="float", example=50000),
     *                 @OA\Property(property="payment_method", type="string", enum={"mobile_money", "bank_transfer"}, example="mobile_money"),
     *                 @OA\Property(property="payment_details", type="object"),
     *                 @OA\Property(property="status", type="string", enum={"pending", "approved", "completed", "rejected"}, example="completed"),
     *                 @OA\Property(property="admin_notes", type="string", nullable=true, example="Payment processed successfully"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="processed_at", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - user is not the withdrawal owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non autorisé.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Withdrawal not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function show(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        if ($withdrawal->photographer_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        return response()->json(['success' => true, 'data' => $withdrawal]);
    }

    /**
     * @OA\Delete(
     *     path="/api/photographer/withdrawals/{withdrawal}",
     *     tags={"Photographer - Withdrawals"},
     *     summary="Cancel withdrawal request",
     *     description="Cancel a pending withdrawal request. Only pending requests can be cancelled by the owner.",
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
     *         description="Withdrawal request cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Demande annulée.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot cancel - withdrawal not pending or unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Impossible d'annuler.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Withdrawal not found",
     *         ref="#/components/responses/NotFoundResponse"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         ref="#/components/responses/UnauthorizedResponse"
     *     )
     * )
     */
    public function destroy(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        if ($withdrawal->photographer_id !== $request->user()->id || $withdrawal->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Impossible d\'annuler.'], 400);
        }

        $withdrawal->delete();
        return response()->json(['success' => true, 'message' => 'Demande annulée.']);
    }
}
