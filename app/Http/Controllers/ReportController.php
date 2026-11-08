<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $data = $this->buildDashboardData($filters);

        return view('admin.reports.index', compact('filters', 'data'));
    }

    public function csv(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $data = $this->buildDashboardData($filters);
        $filename = 'coffee-corner-management-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($filters, $data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['COFFEE CORNER AURA - MANAGEMENT REPORT']);
            fputcsv($out, ['Period', $filters['start_date'] . ' to ' . $filters['end_date']]);
            fputcsv($out, ['Order source', strtoupper($filters['source'])]);
            fputcsv($out, []);

            fputcsv($out, ['FINANCIAL SUMMARY', 'Amount (USD)']);
            foreach ($data['financial'] as $label => $value) {
                fputcsv($out, [$label, number_format((float) $value, 2, '.', '')]);
            }
            fputcsv($out, []);

            $sections = $this->exportSections($filters);
            foreach ($sections as $section) {
                fputcsv($out, [$section['title']]);
                fputcsv($out, $section['headers']);
                foreach ($section['query']->cursor() as $row) {
                    fputcsv($out, array_map(fn ($value) => $this->safeCsvValue($value), (array) $row));
                }
                fputcsv($out, []);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function print(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $data = $this->buildDashboardData($filters);

        return view('admin.reports.print', compact('filters', 'data'));
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'source' => ['nullable', 'in:all,pos,website'],
        ]);

        return [
            'start_date' => $validated['start_date'] ?? now()->startOfMonth()->toDateString(),
            'end_date' => $validated['end_date'] ?? now()->toDateString(),
            'source' => $validated['source'] ?? 'all',
        ];
    }

    private function buildDashboardData(array $filters): array
    {
        $salesQuery = $this->paidOrdersQuery($filters);
        $sales = (float) (clone $salesQuery)->sum('o.final_price');
        $salesCount = (int) (clone $salesQuery)->count();
        $unpaidCount = (int) $this->ordersInPeriod($filters)->where('o.is_paid', false)->count();
        $cancelledOrders = (int) (clone $this->ordersInPeriod($filters))->where('o.order_status', 'cancelled')->count();
        $customersInPeriod = (int) (clone $salesQuery)->whereNotNull('o.customer_id')->distinct()->count('o.customer_id');

        $otherIncomeQuery = $this->manualTransactionsQuery($filters, 1);
        $otherIncome = (float) (clone $otherIncomeQuery)->sum('ct.amount');
        $operatingExpenseQuery = $this->manualTransactionsQuery($filters, 2);
        $operatingExpenses = (float) (clone $operatingExpenseQuery)->sum('ct.amount');

        $purchaseQuery = $this->purchasesQuery($filters);
        $suppliersInPeriod = (int) (clone $purchaseQuery)->whereNotNull('pu.supplier_id')->distinct()->count('pu.supplier_id');
        $purchaseSpend = (float) (clone $purchaseQuery)->selectRaw('COALESCE(SUM(COALESCE(pu.quantity,0) * COALESCE(pu.cost_price,0)),0) as total')->value('total');
        $purchaseCount = (int) (clone $purchaseQuery)->count();

        $cogsQuery = $this->costOfGoodsQuery($filters);
        $cogs = (float) (clone $cogsQuery)->selectRaw('COALESCE(SUM(COALESCE(oi.quantity,0) * COALESCE(oi.unit_cost,p.cost,0)),0) as total')->value('total');

        $grossProfit = $sales - $cogs;
        $netProfit = $grossProfit + $otherIncome - $operatingExpenses;
        $cashMovement = $sales + $otherIncome - $purchaseSpend - $operatingExpenses;

        // Normalize the calculated date in a derived table, then group by its
        // plain column alias. This is compatible with MySQL ONLY_FULL_GROUP_BY.
        $dailyOrderRows = $this->paidOrdersQuery($filters)
            ->selectRaw('DATE(o.order_date) as report_day, o.final_price as order_total');
        $dailySales = DB::query()
            ->fromSub($dailyOrderRows, 'daily_order_rows')
            ->select('report_day')
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(order_total), 0) as sales_total')
            ->groupBy('report_day')
            ->orderBy('report_day')
            ->get();
        $maxDailySales = max(1, (float) $dailySales->max('sales_total'));
        $dailySales = $dailySales->map(function ($day) use ($maxDailySales) {
            $day->bar_width = max(3, (int) round(((float) $day->sales_total / $maxDailySales) * 100));
            return $day;
        });

        $topProducts = $this->costOfGoodsQuery($filters)
            ->select([
                'p.product_id', 'p.product_name',
                DB::raw('COALESCE(SUM(oi.quantity),0) as units_sold'),
                DB::raw('COALESCE(SUM(COALESCE(oi.quantity,0) * COALESCE(oi.price,0)),0) as sales_total'),
                DB::raw('COALESCE(SUM(COALESCE(oi.quantity,0) * COALESCE(oi.unit_cost,p.cost,0)),0) as cogs_total'),
            ])
            ->groupBy('p.product_id', 'p.product_name')
            ->orderByDesc('units_sold')
            ->limit(8)
            ->get();

        // Group the normalized status in an outer query. Grouping the original
        // LOWER/COALESCE expression directly can trigger error 1055 on some
        // MySQL/MariaDB ONLY_FULL_GROUP_BY configurations.
        $normalizedStatusRows = $this->ordersInPeriod($filters)
            ->selectRaw("COALESCE(NULLIF(LOWER(o.order_status), ''), 'unknown') as status_name");
        $orderStatusBreakdown = DB::query()
            ->fromSub($normalizedStatusRows, 'normalized_status_rows')
            ->select('status_name')
            ->selectRaw('COUNT(*) as order_count')
            ->groupBy('status_name')
            ->orderByDesc('order_count')
            ->get();

        // Use the same derived-table approach for POS/Website source totals.
        $normalizedSourceRows = $this->paidOrdersQuery($filters)
            ->selectRaw("COALESCE(NULLIF(LOWER(o.order_source), ''), 'pos') as source_name, o.final_price as order_total");
        $sourceBreakdown = DB::query()
            ->fromSub($normalizedSourceRows, 'normalized_source_rows')
            ->select('source_name')
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(order_total), 0) as sales_total')
            ->groupBy('source_name')
            ->orderByDesc('sales_total')
            ->get();

        $deliveryBase = DB::table('delivery as d')
            ->join('orders as o', 'o.order_id', '=', 'd.order_id')
            ->whereBetween(DB::raw('DATE(o.order_date)'), [$filters['start_date'], $filters['end_date']]);
        $deliveryStats = [
            'total' => (int) (clone $deliveryBase)->count(),
            'pending' => (int) (clone $deliveryBase)->where('d.status', 'pending')->count(),
            'on_way' => (int) (clone $deliveryBase)->where('d.status', 'out_for_delivery')->count(),
            'delivered' => (int) (clone $deliveryBase)->where('d.status', 'delivered')->count(),
            'cancelled' => (int) (clone $deliveryBase)->where('d.status', 'cancelled')->count(),
            'fees' => (float) (clone $deliveryBase)->sum('d.cost'),
        ];

        $reviewBase = DB::table('reviews as r')
            ->join('orders as o', 'o.order_id', '=', 'r.order_id')
            ->whereBetween(DB::raw('DATE(o.order_date)'), [$filters['start_date'], $filters['end_date']]);
        $reviewStats = [
            'count' => (int) (clone $reviewBase)->count(),
            'average' => round((float) ((clone $reviewBase)->avg('r.rating') ?? 0), 1),
            'five_star' => (int) (clone $reviewBase)->where('r.rating', 5)->count(),
        ];

        $stockStats = [
            'products' => (int) DB::table('product')->count(),
            'stock_products' => (int) DB::table('product')->whereRaw("LOWER(TRIM(COALESCE(product_type,'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")->count(),
            'non_stock_products' => (int) DB::table('product')->whereRaw("LOWER(TRIM(COALESCE(product_type,''))) IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")->count(),
            'low_stock' => (int) DB::table('product')->whereRaw("LOWER(TRIM(COALESCE(product_type,'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")->where('qty', '<=', 5)->count(),
            'units_available' => (int) DB::table('product')->whereRaw("LOWER(TRIM(COALESCE(product_type,'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")->sum('qty'),
            'inventory_value' => (float) DB::table('product')->whereRaw("LOWER(TRIM(COALESCE(product_type,'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")->selectRaw('COALESCE(SUM(COALESCE(qty,0) * COALESCE(cost,0)),0) as total')->value('total'),
        ];

        $financial = [
            'Sales income (paid orders)' => $sales,
            'Other income' => $otherIncome,
            'Inventory purchases (cash outflow)' => $purchaseSpend,
            'Operating expenses' => $operatingExpenses,
            'Cost of goods sold (COGS)' => $cogs,
            'Gross profit' => $grossProfit,
            'Net profit (estimated)' => $netProfit,
            'Net cash movement (estimated)' => $cashMovement,
        ];

        return [
            'financial' => $financial,
            'sales_count' => $salesCount,
            'unpaid_count' => $unpaidCount,
            'cancelled_orders' => $cancelledOrders,
            'customers_in_period' => $customersInPeriod,
            'suppliers_in_period' => $suppliersInPeriod,
            'purchase_count' => $purchaseCount,
            'order_status_breakdown' => $orderStatusBreakdown,
            'daily_sales' => $dailySales,
            'top_products' => $topProducts,
            'source_breakdown' => $sourceBreakdown,
            'delivery' => $deliveryStats,
            'reviews' => $reviewStats,
            'stock' => $stockStats,
            'recent_orders' => $this->paidOrdersQuery($filters)
                ->leftJoin('customer as c', 'c.customer_id', '=', 'o.customer_id')
                ->select(['o.order_id', 'o.order_date', 'o.order_source', 'o.final_price', 'c.name as customer_name'])
                ->orderByDesc('o.order_date')->limit(10)->get(),
            'recent_purchases' => (clone $purchaseQuery)
                ->leftJoin('product as p', 'p.product_id', '=', 'pu.product_id')
                ->leftJoin('suppliers as s', 's.supplier_id', '=', 'pu.supplier_id')
                ->select(['pu.purchase_id', 'pu.purchase_date', 'pu.quantity', 'pu.cost_price', 'p.product_name', 's.name as supplier_name'])
                ->orderByDesc('pu.purchase_date')->limit(10)->get(),
            'recent_transactions' => (clone $otherIncomeQuery)
                ->select(['ct.id', 'ct.transaction_date', 'ct.amount', 'ct.note', 'ac.name as category_name', 'ac.id_type'])
                ->orderByDesc('ct.transaction_date')->limit(8)->get()
                ->concat((clone $operatingExpenseQuery)
                    ->select(['ct.id', 'ct.transaction_date', 'ct.amount', 'ct.note', 'ac.name as category_name', 'ac.id_type'])
                    ->orderByDesc('ct.transaction_date')->limit(8)->get())
                ->sortByDesc('transaction_date')->take(10)->values(),
        ];
    }

    private function ordersInPeriod(array $filters): Builder
    {
        $query = DB::table('orders as o')->whereBetween(DB::raw('DATE(o.order_date)'), [$filters['start_date'], $filters['end_date']]);
        if ($filters['source'] !== 'all') {
            $query->whereRaw('LOWER(COALESCE(o.order_source, ?)) = ?', ['pos', $filters['source']]);
        }
        return $query;
    }

    private function paidOrdersQuery(array $filters): Builder
    {
        return $this->ordersInPeriod($filters)->where('o.is_paid', true);
    }

    private function costOfGoodsQuery(array $filters): Builder
    {
        $query = DB::table('order_items as oi')
            ->join('orders as o', 'o.order_id', '=', 'oi.order_id')
            ->join('product as p', 'p.product_id', '=', 'oi.product_id')
            ->where('o.is_paid', true)
            ->whereBetween(DB::raw('DATE(o.order_date)'), [$filters['start_date'], $filters['end_date']]);
        if ($filters['source'] !== 'all') {
            $query->whereRaw('LOWER(COALESCE(o.order_source, ?)) = ?', ['pos', $filters['source']]);
        }
        return $query;
    }

    private function manualTransactionsQuery(array $filters, int $type): Builder
    {
        return DB::table('cash_transactions as ct')
            ->join('accounting_category as ac', 'ac.id', '=', 'ct.category_id')
            ->where('ac.id_type', $type)
            ->whereNull('ct.order_id')
            ->whereNull('ct.purchase_id')
            ->whereBetween(DB::raw('DATE(ct.transaction_date)'), [$filters['start_date'], $filters['end_date']]);
    }

    private function purchasesQuery(array $filters): Builder
    {
        return DB::table('purchases as pu')
            ->whereBetween(DB::raw('DATE(pu.purchase_date)'), [$filters['start_date'], $filters['end_date']]);
    }

    private function exportSections(array $filters): array
    {
        $sales = $this->ordersInPeriod($filters)
            ->leftJoin('customer as c', 'c.customer_id', '=', 'o.customer_id')
            ->select(['o.order_id', 'o.order_date', 'o.order_source', 'o.final_price', 'o.is_paid', 'o.payment_status', 'o.order_status', 'c.name as customer_name'])
            ->orderBy('o.order_date');

        $products = $this->costOfGoodsQuery($filters)
            ->select(['p.product_id', 'p.product_name', DB::raw('SUM(oi.quantity) as units_sold'), DB::raw('SUM(oi.quantity * oi.price) as sales_total'), DB::raw('SUM(oi.quantity * COALESCE(oi.unit_cost,p.cost,0)) as cogs_total')])
            ->groupBy('p.product_id', 'p.product_name')->orderByDesc('units_sold');

        $purchases = $this->purchasesQuery($filters)
            ->leftJoin('product as p', 'p.product_id', '=', 'pu.product_id')
            ->leftJoin('suppliers as s', 's.supplier_id', '=', 'pu.supplier_id')
            ->select(['pu.purchase_id', 'pu.purchase_date', 'p.product_name', 's.name as supplier_name', 'pu.quantity', 'pu.cost_price', DB::raw('(pu.quantity * pu.cost_price) as total_cost')])
            ->orderBy('pu.purchase_date');

        $deliveries = DB::table('delivery as d')->join('orders as o', 'o.order_id', '=', 'd.order_id')
            ->leftJoin('customer as c', 'c.customer_id', '=', 'o.customer_id')
            ->whereBetween(DB::raw('DATE(o.order_date)'), [$filters['start_date'], $filters['end_date']])
            ->select(['d.delivery_id', 'd.order_id', 'o.order_date', 'c.name as customer_name', 'd.address', 'd.cost', 'd.status'])
            ->orderBy('o.order_date');

        $transactions = DB::table('cash_transactions as ct')->join('accounting_category as ac', 'ac.id', '=', 'ct.category_id')
            ->whereNull('ct.order_id')->whereNull('ct.purchase_id')
            ->whereBetween(DB::raw('DATE(ct.transaction_date)'), [$filters['start_date'], $filters['end_date']])
            ->select(['ct.id', 'ct.transaction_date', 'ac.name as category_name', DB::raw("CASE WHEN ac.id_type = 1 THEN 'Income' ELSE 'Expense' END as transaction_type"), 'ct.amount', 'ct.note'])
            ->orderBy('ct.transaction_date');

        $payments = DB::table('payments as pay')->join('orders as o', 'o.order_id', '=', 'pay.order_id')
            ->whereBetween(DB::raw('DATE(o.order_date)'), [$filters['start_date'], $filters['end_date']])
            ->when($filters['source'] !== 'all', fn ($q) => $q->whereRaw('LOWER(COALESCE(o.order_source, ?)) = ?', ['pos', $filters['source']]))
            ->select(['pay.id as payment_id', 'pay.order_id', 'o.order_date', 'pay.provider', 'pay.tran_id', 'pay.amount', 'pay.currency', 'pay.status'])
            ->orderBy('o.order_date');

        $inventory = DB::table('product as p')->leftJoin('category as c', 'c.category_id', '=', 'p.category_id')
            ->select(['p.product_id', 'p.product_name', 'c.category_name', DB::raw("CASE WHEN LOWER(TRIM(COALESCE(p.product_type,''))) IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked') THEN 'Non-stock' ELSE 'Stock' END as product_type"), 'p.qty', 'p.price', 'p.cost', DB::raw('(COALESCE(p.qty,0) * COALESCE(p.cost,0)) as stock_value'), 'p.status'])
            ->orderBy('p.product_name');

        $reviews = DB::table('reviews as r')->join('orders as o', 'o.order_id', '=', 'r.order_id')
            ->leftJoin('customer as c', 'c.customer_id', '=', 'r.customer_id')
            ->whereBetween(DB::raw('DATE(o.order_date)'), [$filters['start_date'], $filters['end_date']])
            ->select(['r.review_id', 'r.order_id', 'o.order_date', 'c.name as customer_name', 'r.rating', 'r.comment'])
            ->orderBy('o.order_date');

        return [
            ['title' => 'Orders (all payment states)', 'headers' => ['Order ID', 'Order Date', 'Source', 'Final Price', 'Paid', 'Payment Status', 'Order Status', 'Customer'], 'query' => $sales],
            ['title' => 'Payment Transactions', 'headers' => ['Payment ID', 'Order ID', 'Order Date', 'Provider', 'Transaction ID', 'Amount', 'Currency', 'Payment Status'], 'query' => $payments],
            ['title' => 'Product Performance (paid orders)', 'headers' => ['Product ID', 'Product', 'Units Sold', 'Sales Total', 'COGS Total'], 'query' => $products],
            ['title' => 'Current Inventory Snapshot (not date-filtered)', 'headers' => ['Product ID', 'Product', 'Category', 'Product Type', 'Quantity', 'Selling Price', 'Unit Cost', 'Stock Value', 'Status'], 'query' => $inventory],
            ['title' => 'Purchases', 'headers' => ['Purchase ID', 'Purchase Date', 'Product', 'Supplier', 'Quantity', 'Unit Cost', 'Total Cost'], 'query' => $purchases],
            ['title' => 'Deliveries', 'headers' => ['Delivery ID', 'Order ID', 'Order Date', 'Customer', 'Address', 'Cost', 'Status'], 'query' => $deliveries],
            ['title' => 'Other Income and Operating Expenses', 'headers' => ['Transaction ID', 'Date', 'Category', 'Type', 'Amount', 'Note'], 'query' => $transactions],
            ['title' => 'Customer Reviews', 'headers' => ['Review ID', 'Order ID', 'Order Date', 'Customer', 'Rating', 'Comment'], 'query' => $reviews],
        ];
    }

    private function safeCsvValue(mixed $value): string|int|float|null
    {
        if (is_string($value) && preg_match('/^[=+@\\-\\t\\r]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }
}
