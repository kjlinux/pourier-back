<?php

namespace App\Http\Controllers\Api\Photographer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Photographer\AnalyticsRequest;
use App\Services\PhotographerAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class AnalyticsController extends Controller
{
    public function __construct(
        protected PhotographerAnalyticsService $analyticsService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/photographer/analytics/sales",
     *     operationId="getPhotographerAnalyticsSales",
     *     tags={"Photographer - Analytics"},
     *     summary="Get sales analytics",
     *     description="Get sales analytics for a specified period (7, 30, or 90 days). Revenue shown is photographer's 80% share after 20% platform commission.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Analytics period",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7days", "30days", "90days"}, default="30days", example="30days")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_sales", type="integer", example=45, description="Total number of sales in the period"),
     *                 @OA\Property(property="total_revenue", type="number", format="float", example=180000, description="Total photographer revenue (80% share) in FCFA"),
     *                 @OA\Property(property="average_sale", type="number", format="float", example=4000, description="Average revenue per sale in FCFA")
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
    public function sales(Request $request): JsonResponse
    {
        $photographer = $request->user();
        $period = $request->input('period', '30days');

        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(30),
        };

        $sales = $photographer->orderItems()
            ->whereHas('order', fn($q) => $q->where('created_at', '>=', $startDate))
            ->with('photo:id,title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => $sales->count(),
                'total_revenue' => $sales->sum('photographer_amount'),
                'average_sale' => $sales->count() > 0 ? $sales->avg('photographer_amount') : 0,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/analytics/popular-photos",
     *     operationId="getPhotographerAnalyticsPopularPhotos",
     *     tags={"Photographer - Analytics"},
     *     summary="Get popular photos",
     *     description="Get top 10 best-selling photos ranked by total number of sales",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Popular photos retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="title", type="string", example="Sunset over Ouagadougou"),
     *                     @OA\Property(property="thumbnail_url", type="string", format="url", example="https://pourier.s3.amazonaws.com/thumbnails/xyz.jpg"),
     *                     @OA\Property(property="price_standard", type="number", format="float", example=5000),
     *                     @OA\Property(property="price_extended", type="number", format="float", example=10000),
     *                     @OA\Property(property="view_count", type="integer", example=1500),
     *                     @OA\Property(property="order_items_count", type="integer", example=25, description="Total number of times this photo was sold"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 ),
     *                 description="Top 10 photos by sales count"
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
    public function popularPhotos(Request $request): JsonResponse
    {
        $topPhotos = $request->user()->photos()
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->take(10)
            ->get();

        return response()->json(['success' => true, 'data' => $topPhotos]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/analytics/views-over-time",
     *     operationId="getPhotographerViewsOverTime",
     *     tags={"Photographer - Analytics"},
     *     summary="Get views over time",
     *     description="Get historical view counts for photographer's photos over a specified period",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Analytics period",
     *         required=true,
     *         @OA\Schema(type="string", enum={"7d", "30d", "90d"}, example="30d")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Views data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="date", type="string", format="date", example="2025-01-01"),
     *                     @OA\Property(property="views", type="integer", example=145)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="total_views", type="integer", example=5432),
     *                 @OA\Property(property="average_daily_views", type="number", example=181),
     *                 @OA\Property(property="change_percentage", type="number", example=12.5)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function viewsOverTime(AnalyticsRequest $request): JsonResponse
    {
        $result = $this->analyticsService->getViewsOverTime(
            $request->user(),
            $request->validated()['period']
        );

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'summary' => $result['summary'],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/analytics/sales-over-time",
     *     operationId="getPhotographerSalesOverTime",
     *     tags={"Photographer - Analytics"},
     *     summary="Get sales over time",
     *     description="Get historical sales counts over a specified period",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Analytics period",
     *         required=true,
     *         @OA\Schema(type="string", enum={"7d", "30d", "90d"}, example="30d")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="date", type="string", format="date", example="2025-01-01"),
     *                     @OA\Property(property="sales", type="integer", example=12)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="total_sales", type="integer", example=156),
     *                 @OA\Property(property="average_daily_sales", type="number", example=5.2),
     *                 @OA\Property(property="change_percentage", type="number", example=-3.2)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function salesOverTime(AnalyticsRequest $request): JsonResponse
    {
        $result = $this->analyticsService->getSalesOverTime(
            $request->user(),
            $request->validated()['period']
        );

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'summary' => $result['summary'],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/analytics/revenue-over-time",
     *     operationId="getPhotographerRevenueOverTime",
     *     tags={"Photographer - Analytics"},
     *     summary="Get revenue over time",
     *     description="Get historical revenue data (photographer's 80% share after commission) over a specified period",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Analytics period",
     *         required=true,
     *         @OA\Schema(type="string", enum={"7d", "30d", "90d"}, example="30d")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Revenue data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="date", type="string", format="date", example="2025-01-01"),
     *                     @OA\Property(property="revenue", type="integer", example=45000)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="total_revenue", type="integer", example=567000),
     *                 @OA\Property(property="average_daily_revenue", type="integer", example=18900),
     *                 @OA\Property(property="change_percentage", type="number", example=8.7)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function revenueOverTime(AnalyticsRequest $request): JsonResponse
    {
        $result = $this->analyticsService->getRevenueOverTime(
            $request->user(),
            $request->validated()['period']
        );

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'summary' => $result['summary'],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/analytics/conversion-over-time",
     *     operationId="getPhotographerConversionOverTime",
     *     tags={"Photographer - Analytics"},
     *     summary="Get conversion rate over time",
     *     description="Get historical conversion rate (views to sales) over a specified period",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Analytics period",
     *         required=true,
     *         @OA\Schema(type="string", enum={"7d", "30d", "90d"}, example="30d")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conversion data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="date", type="string", format="date", example="2025-01-01"),
     *                     @OA\Property(property="conversion_rate", type="number", example=2.3)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="summary",
     *                 type="object",
     *                 @OA\Property(property="average_conversion_rate", type="number", example=2.1),
     *                 @OA\Property(property="change_percentage", type="number", example=0.5)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function conversionOverTime(AnalyticsRequest $request): JsonResponse
    {
        $result = $this->analyticsService->getConversionOverTime(
            $request->user(),
            $request->validated()['period']
        );

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'summary' => $result['summary'],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/analytics/hourly-distribution",
     *     operationId="getPhotographerHourlyDistribution",
     *     tags={"Photographer - Analytics"},
     *     summary="Get hourly distribution",
     *     description="Analyze which hours of the day photos are most viewed or purchased",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Analytics period",
     *         required=true,
     *         @OA\Schema(type="string", enum={"7d", "30d", "90d"}, example="30d")
     *     ),
     *     @OA\Parameter(
     *         name="metric",
     *         in="query",
     *         description="Metric to analyze",
     *         required=false,
     *         @OA\Schema(type="string", enum={"views", "sales"}, default="views")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hourly distribution retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="hour", type="integer", example=18),
     *                     @OA\Property(property="value", type="integer", example=245)
     *                 )
     *             ),
     *             @OA\Property(property="peak_hours", type="array", @OA\Items(type="integer"), example={18, 19, 20}),
     *             @OA\Property(property="lowest_hours", type="array", @OA\Items(type="integer"), example={3, 4, 5})
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function hourlyDistribution(AnalyticsRequest $request): JsonResponse
    {
        $metric = $request->input('metric', 'views');

        $result = $this->analyticsService->getHourlyDistribution(
            $request->user(),
            $request->validated()['period'],
            $metric
        );

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'peak_hours' => $result['peak_hours'],
            'lowest_hours' => $result['lowest_hours'],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/photographer/analytics/category-performance",
     *     operationId="getPhotographerCategoryPerformance",
     *     tags={"Photographer - Analytics"},
     *     summary="Get performance by category",
     *     description="Analyze photo performance grouped by category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Analytics period",
     *         required=true,
     *         @OA\Schema(type="string", enum={"7d", "30d", "90d"}, example="30d")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category performance retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="category_id", type="string", format="uuid"),
     *                     @OA\Property(property="category_name", type="string", example="Sport"),
     *                     @OA\Property(property="total_sales", type="integer", example=45),
     *                     @OA\Property(property="total_revenue", type="integer", example=225000),
     *                     @OA\Property(property="total_views", type="integer", example=3456),
     *                     @OA\Property(property="conversion_rate", type="number", example=1.3),
     *                     @OA\Property(property="average_price", type="integer", example=5000)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="top_category",
     *                 type="object",
     *                 @OA\Property(property="by_sales", type="string", example="Sport"),
     *                 @OA\Property(property="by_revenue", type="string", example="Culture"),
     *                 @OA\Property(property="by_conversion", type="string", example="Portraits")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function categoryPerformance(AnalyticsRequest $request): JsonResponse
    {
        $result = $this->analyticsService->getCategoryPerformance(
            $request->user(),
            $request->validated()['period']
        );

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'top_category' => $result['top_category'],
        ]);
    }
}
