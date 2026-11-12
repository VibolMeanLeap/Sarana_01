<?php

namespace App\Http\Controllers;

use App\Models\Costumer;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    /**
     * Administrator dashboard with a validated, shareable reporting period.
     * Inventory/product cards remain current-state metrics; sales metrics use
     * the selected date range.
     */
    public function index(Request $request)
    {
        $allowedRanges = ['today', '7days', '30days', 'this_month', 'custom'];
        $range = (string) $request->query('range', '30days');
        if (! in_array($range, $allowedRanges, true)) {
            $range = '30days';
        }

        $now = now();
        $today = $now->copy()->startOfDay();
        $filterError = null;

        switch ($range) {
            case 'today':
                $start = $today->copy();
                $end = $now->copy()->endOfDay();
                $dateRangeLabel = 'Today';
                break;

            case '7days':
                $start = $today->copy()->subDays(6);
                $end = $now->copy()->endOfDay();
                $dateRangeLabel = 'Last 7 days';
                break;

            case 'this_month':
                $start = $today->copy()->startOfMonth();
                $end = $now->copy()->endOfDay();
                $dateRangeLabel = 'This month';
                break;

            case 'custom':
                // First click on Custom opens the date fields without applying
                // an incomplete filter. The default values are the latest 30 days.
                if (! $request->filled('start_date') && ! $request->filled('end_date')) {
                    $start = $today->copy()->subDays(29);
                    $end = $now->copy()->endOfDay();
                    $dateRangeLabel = 'Custom range';
                    break;
                }

                $validator = Validator::make($request->query(), [
                    'start_date' => ['required', 'date_format:Y-m-d'],
                    'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
                ]);

                if ($validator->fails()) {
                    $filterError = 'Choose a valid start date and end date.';
                    $start = $today->copy()->subDays(29);
                    $end = $now->copy()->endOfDay();
                    $dateRangeLabel = 'Custom range';
                } else {
                    $start = Carbon::createFromFormat('Y-m-d', $request->query('start_date'))->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', $request->query('end_date'))->startOfDay();

                    if ($endDate->gt($today)) {
                        $filterError = 'The end date cannot be in the future.';
                    } elseif ($start->diffInDays($endDate) > 365) {
                        $filterError = 'Custom date ranges cannot exceed 366 days.';
                    }

                    if ($filterError !== null) {
                        $start = $today->copy()->subDays(29);
                        $end = $now->copy()->endOfDay();
                        $dateRangeLabel = 'Custom range';
                    } else {
                        $end = $endDate->endOfDay();
                        $dateRangeLabel = $start->copy()->locale(app()->getLocale())->translatedFormat('d M Y').' – '.$endDate->copy()->locale(app()->getLocale())->translatedFormat('d M Y');
                    }
                }
                break;

            case '30days':
            default:
                $start = $today->copy()->subDays(29);
                $end = $now->copy()->endOfDay();
                $dateRangeLabel = 'Last 30 days';
                break;
        }

        $startDateValue = $range === 'custom' && $filterError === null && $request->query('start_date')
            ? (string) $request->query('start_date')
            : $start->format('Y-m-d');
        $endDateValue = $range === 'custom' && $filterError === null && $request->query('end_date')
            ? (string) $request->query('end_date')
            : $end->format('Y-m-d');

        $periodOrders = Order::query()->whereBetween('order_date', [$start, $end]);
        $totalOrders = (clone $periodOrders)->count();
        $paidOrders = (clone $periodOrders)->where('is_paid', true)->count();
        $pendingOrders = (clone $periodOrders)->where('is_paid', false)->count();
        $totalRevenue = (float) (clone $periodOrders)->where('is_paid', true)
            ->sum(DB::raw('COALESCE(final_price, total_amount, 0)'));

        // Current inventory/customer totals are intentionally not date-filtered.
        $productCount = Product::where('status', 'Active')->count();
        $stockUnits = (int) Product::sum('qty');
        $customerCount = Costumer::count();

        $lowStockProducts = Product::with('category')
            ->where('status', 'Active')
            ->where('qty', '<=', 5)
            ->orderBy('qty')
            ->limit(5)
            ->get();

        $topStockProducts = Product::where('status', 'Active')
            ->orderByDesc('qty')
            ->limit(7)
            ->get(['product_name', 'qty']);

        $stockChartLabels = $topStockProducts->pluck('product_name')->values();
        $stockChartValues = $topStockProducts->pluck('qty')->map(fn ($qty) => (int) $qty)->values();

        // Build daily rows, then condense long custom ranges into weekly buckets.
        $dailyRows = (clone $periodOrders)
            ->selectRaw('DATE(order_date) as sales_day, COUNT(*) as order_count, SUM(CASE WHEN is_paid = 1 THEN COALESCE(final_price, total_amount, 0) ELSE 0 END) as paid_revenue')
            ->groupBy(DB::raw('DATE(order_date)'))
            ->get()
            ->keyBy('sales_day');

        $dayCount = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $bucketMode = $dayCount <= 14 ? 'day' : ($dayCount <= 90 ? 'week' : 'month');
        $trendBuckets = [];
        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();

        while ($cursor->lte($lastDay)) {
            $row = $dailyRows->get($cursor->format('Y-m-d'));
            $bucketDate = match ($bucketMode) {
                'day' => $cursor->copy(),
                'week' => $cursor->copy()->startOfWeek(Carbon::MONDAY),
                default => $cursor->copy()->startOfMonth(),
            };
            $bucketKey = $bucketDate->format('Y-m-d');

            if (! isset($trendBuckets[$bucketKey])) {
                $bucketLabel = match ($bucketMode) {
                    'day' => $cursor->copy()->locale(app()->getLocale())->translatedFormat('D'),
                    'week' => __('Week :date', ['date' => $bucketDate->copy()->locale(app()->getLocale())->translatedFormat('d M')]),
                    default => $bucketDate->copy()->locale(app()->getLocale())->translatedFormat('M Y'),
                };
                $trendBuckets[$bucketKey] = [
                    'label' => $bucketLabel,
                    'date' => $bucketDate->copy()->locale(app()->getLocale())->translatedFormat('d M Y'),
                    'revenue' => 0.0,
                    'orders' => 0,
                ];
            }

            $trendBuckets[$bucketKey]['revenue'] += (float) ($row->paid_revenue ?? 0);
            $trendBuckets[$bucketKey]['orders'] += (int) ($row->order_count ?? 0);
            $cursor->addDay();
        }

        $salesTrend = collect(array_values($trendBuckets));

        // Keep the view's established variable names to minimize template changes.
        $todayOrderCount = $totalOrders;
        $todayPaidOrderCount = $paidOrders;
        $todayRevenue = $totalRevenue;
        $averageOrderValue = $paidOrders > 0 ? $totalRevenue / $paidOrders : 0;
        $paymentCompletionRate = $totalOrders > 0
            ? (int) round(($paidOrders / $totalOrders) * 100)
            : 0;

        $topSellingProducts = DB::table('order_items')
            ->join('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->join('product', 'product.product_id', '=', 'order_items.product_id')
            ->where('orders.is_paid', true)
            ->whereBetween('orders.order_date', [$start, $end])
            ->select('product.product_name')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->groupBy('product.product_id', 'product.product_name')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get();

        $topSellingMax = max(1, (int) ($topSellingProducts->max('units_sold') ?? 0));

        $recentOrders = Order::with(['customer', 'cashier', 'paymentMethod'])
            ->whereBetween('order_date', [$start, $end])
            ->orderByDesc('order_date')
            ->limit(7)
            ->get();

        return view('admin.Admin_dashboard', compact(
            'totalOrders', 'paidOrders', 'pendingOrders', 'totalRevenue',
            'productCount', 'stockUnits', 'customerCount', 'lowStockProducts',
            'topStockProducts', 'stockChartLabels', 'stockChartValues', 'recentOrders',
            'salesTrend', 'todayOrderCount', 'todayRevenue', 'averageOrderValue',
            'paymentCompletionRate', 'topSellingProducts', 'topSellingMax', 'todayPaidOrderCount',
            'range', 'dateRangeLabel', 'startDateValue', 'endDateValue', 'filterError'
        ));
    }
}
