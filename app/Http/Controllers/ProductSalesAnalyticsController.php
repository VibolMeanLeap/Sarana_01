<?php

namespace App\Http\Controllers;

use App\Support\AdminDateFilter;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductSalesAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $itemsQuery = $this->filteredItemSalesQuery($filters);

        $summary = (clone $itemsQuery)->selectRaw('
            COALESCE(SUM(COALESCE(oi.quantity, 0)), 0) as units_sold,
            COUNT(DISTINCT p.product_id) as products_sold,
            COUNT(DISTINCT o.order_id) as orders_count,
            COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.price, 0)), 0) as item_sales_total,
            COALESCE(SUM(COALESCE(oi.discount_amount, 0)), 0) as item_discount_total,
            COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.unit_cost, p.cost, 0)), 0) as cogs_total
        ')->first();

        // The summary is calculated from every matching order item, not just
        // the current 20-row page. This also supplies the table footer totals.
        $summary->average_selling_price = (float) $summary->units_sold > 0
            ? (float) $summary->item_sales_total / (float) $summary->units_sold
            : 0.0;

        $productsQuery = $this->productPerformanceQuery($itemsQuery);
        $products = (clone $productsQuery)
            ->orderByDesc('units_sold')
            ->orderByDesc('item_sales_total')
            ->orderBy('p.product_name')
            ->paginate(20)
            ->withQueryString();

        $topProducts = (clone $productsQuery)
            ->orderByDesc('units_sold')
            ->orderByDesc('item_sales_total')
            ->limit(5)
            ->get();
        $bestSeller = $topProducts->first();

        $categories = (clone $itemsQuery)
            ->select([
                'p.category_id',
                DB::raw("COALESCE(c.category_name, 'Uncategorized') as category_name"),
            ])
            ->selectRaw('COALESCE(SUM(COALESCE(oi.quantity, 0)), 0) as units_sold')
            ->selectRaw('COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.price, 0)), 0) as item_sales_total')
            ->groupBy('p.category_id', 'c.category_name')
            ->orderByDesc('units_sold')
            ->limit(6)
            ->get();
        $maxCategoryUnits = max(1, (int) $categories->max('units_sold'));
        $categories->transform(function ($category) use ($maxCategoryUnits) {
            $category->bar_width = max(3, (int) round(((int) $category->units_sold / $maxCategoryUnits) * 100));
            return $category;
        });

        // Derived-table grouping keeps this query compatible with MariaDB/MySQL
        // configurations that enable ONLY_FULL_GROUP_BY.
        $dailyItemRows = (clone $itemsQuery)->selectRaw(
            'DATE(o.order_date) as sale_day, COALESCE(oi.quantity, 0) as item_quantity'
        );
        $dailySales = DB::query()
            ->fromSub($dailyItemRows, 'daily_item_rows')
            ->select('sale_day')
            ->selectRaw('COALESCE(SUM(item_quantity), 0) as units_sold')
            ->groupBy('sale_day')
            ->orderByDesc('sale_day')
            ->limit(14)
            ->get()
            ->sortBy('sale_day')
            ->values();
        $maxDailyUnits = max(1, (int) $dailySales->max('units_sold'));
        $dailySales->transform(function ($day) use ($maxDailyUnits) {
            $day->bar_width = max(3, (int) round(((int) $day->units_sold / $maxDailyUnits) * 100));
            return $day;
        });

        $staff = DB::table('users as u')
            ->join('role as r', 'r.role_id', '=', 'u.role_id')
            ->whereIn(DB::raw('LOWER(r.role_name)'), ['admin', 'cashier'])
            ->orderByRaw("CASE WHEN LOWER(r.role_name) = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('u.name')
            ->get(['u.user_id', 'u.name', 'r.role_name', 'u.status']);
        $categoryOptions = DB::table('category')->orderBy('category_name')->get(['category_id', 'category_name']);

        $summary->gross_margin = (float) $summary->item_sales_total - (float) $summary->item_discount_total - (float) $summary->cogs_total;

        return view('admin.product-sales.index', compact(
            'filters',
            'summary',
            'products',
            'topProducts',
            'bestSeller',
            'categories',
            'dailySales',
            'staff',
            'categoryOptions'
        ));
    }

    public function csv(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $itemsQuery = $this->filteredItemSalesQuery($filters);
        $query = $this->productPerformanceQuery($itemsQuery)
            ->orderByDesc('units_sold')
            ->orderByDesc('item_sales_total')
            ->orderBy('p.product_name');

        // Export totals use the full filtered dataset, never a page-limited query.
        $summary = (clone $itemsQuery)->selectRaw('
            COALESCE(SUM(COALESCE(oi.quantity, 0)), 0) as units_sold,
            COUNT(DISTINCT p.product_id) as products_sold,
            COUNT(DISTINCT o.order_id) as orders_count,
            COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.price, 0)), 0) as item_sales_total,
            COALESCE(SUM(COALESCE(oi.discount_amount, 0)), 0) as item_discount_total,
            COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.unit_cost, p.cost, 0)), 0) as cogs_total
        ')->first();
        $averageSellingPrice = (float) ($summary->units_sold ?? 0) > 0
            ? (float) $summary->item_sales_total / (float) $summary->units_sold
            : 0.0;
        $grossMargin = (float) ($summary->item_sales_total ?? 0)
            - (float) ($summary->item_discount_total ?? 0)
            - (float) ($summary->cogs_total ?? 0);

        $filename = 'product-sales-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query, $filters, $summary, $averageSellingPrice, $grossMargin) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Product sales report']);
            fputcsv($out, ['Date preset', $filters['date_preset']]);
            fputcsv($out, ['From', $filters['start_date'] ?: 'All time']);
            fputcsv($out, ['To', $filters['end_date'] ?: 'Today']);
            fputcsv($out, ['Source', $filters['source']]);
            fputcsv($out, ['Category', $filters['category_name'] ?: 'All categories']);
            fputcsv($out, ['Search', $filters['search'] ?: '—']);
            fputcsv($out, []);
            fputcsv($out, ['Product ID', 'Product', 'Category', 'Units sold', 'Orders', 'Average selling price (USD/unit)', 'Item sales subtotal (USD)', 'Item discount (USD)', 'Estimated COGS (USD)', 'Estimated gross margin (USD)']);

            foreach ($query->cursor() as $row) {
                fputcsv($out, [
                    $row->product_id,
                    $this->safeCsvValue($row->product_name),
                    $this->safeCsvValue($row->category_name),
                    (int) $row->units_sold,
                    (int) $row->orders_count,
                    number_format((float) $row->average_selling_price, 2, '.', ''),
                    number_format((float) $row->item_sales_total, 2, '.', ''),
                    number_format((float) $row->item_discount_total, 2, '.', ''),
                    number_format((float) $row->cogs_total, 2, '.', ''),
                    number_format((float) $row->gross_margin, 2, '.', ''),
                ]);
            }

            if ((int) ($summary->products_sold ?? 0) > 0) {
                fputcsv($out, []);
                fputcsv($out, [
                    'TOTAL',
                    '',
                    '',
                    (int) ($summary->units_sold ?? 0),
                    (int) ($summary->orders_count ?? 0),
                    number_format($averageSellingPrice, 2, '.', ''),
                    number_format((float) ($summary->item_sales_total ?? 0), 2, '.', ''),
                    number_format((float) ($summary->item_discount_total ?? 0), 2, '.', ''),
                    number_format((float) ($summary->cogs_total ?? 0), 2, '.', ''),
                    number_format($grossMargin, 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Open the same branded, print-ready report used by the standard Order exports.
     * The export uses the complete filtered dataset rather than the current page.
     */
    public function pdf(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $itemsQuery = $this->filteredItemSalesQuery($filters);
        $products = $this->productPerformanceQuery($itemsQuery)
            ->orderByDesc('units_sold')
            ->orderByDesc('item_sales_total')
            ->orderBy('p.product_name')
            ->get();

        $summary = (clone $itemsQuery)->selectRaw('
            COALESCE(SUM(COALESCE(oi.quantity, 0)), 0) as units_sold,
            COUNT(DISTINCT p.product_id) as products_sold,
            COUNT(DISTINCT o.order_id) as orders_count,
            COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.price, 0)), 0) as item_sales_total,
            COALESCE(SUM(COALESCE(oi.discount_amount, 0)), 0) as item_discount_total,
            COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.unit_cost, p.cost, 0)), 0) as cogs_total
        ')->first();
        $averageSellingPrice = (float) ($summary->units_sold ?? 0) > 0
            ? (float) $summary->item_sales_total / (float) $summary->units_sold
            : 0.0;
        $grossMargin = (float) ($summary->item_sales_total ?? 0)
            - (float) ($summary->item_discount_total ?? 0)
            - (float) ($summary->cogs_total ?? 0);

        $headers = [
            'Product ID', 'Product', 'Category', 'Units sold', 'Orders',
            'Average selling price (USD/unit)', 'Item sales subtotal (USD)', 'Item discount (USD)', 'Estimated COGS (USD)', 'Estimated gross margin (USD)',
        ];
        $rows = $products->map(fn ($row) => [
            $row->product_id,
            $row->product_name,
            $row->category_name,
            number_format((int) $row->units_sold),
            number_format((int) $row->orders_count),
            '$'.$this->formatAverageSellingPrice((float) $row->average_selling_price),
            '$'.number_format((float) $row->item_sales_total, 2),
            '$'.number_format((float) $row->item_discount_total, 2),
            '$'.number_format((float) $row->cogs_total, 2),
            '$'.number_format((float) $row->gross_margin, 2),
        ])->all();

        $totalRow = [
            'TOTAL', '', '', number_format((int) ($summary->units_sold ?? 0)), number_format((int) ($summary->orders_count ?? 0)),
            '$'.$this->formatAverageSellingPrice($averageSellingPrice),
            '$'.number_format((float) ($summary->item_sales_total ?? 0), 2),
            '$'.number_format((float) ($summary->item_discount_total ?? 0), 2),
            '$'.number_format((float) ($summary->cogs_total ?? 0), 2),
            '$'.number_format($grossMargin, 2),
        ];

        $sourceLabel = match ($filters['source']) {
            'pos' => 'POS',
            'website' => 'Website',
            default => 'All sources',
        };
        $staffLabel = 'All staff and website';
        if ($filters['staff_id'] === 'website') {
            $staffLabel = 'Website / no staff';
        } elseif ($filters['staff_id'] !== '') {
            $staffLabel = DB::table('users')->where('user_id', $filters['staff_id'])->value('name') ?: 'Staff #'.$filters['staff_id'];
        }
        $dateLabel = $filters['start_date'] || $filters['end_date']
            ? (($filters['start_date'] ?: 'Beginning').' to '.($filters['end_date'] ?: 'Today'))
            : 'All dates';
        $filterSummary = collect([
            'Date: '.$dateLabel,
            'Source: '.$sourceLabel,
            'Category: '.($filters['category_name'] ?: 'All categories'),
            'Staff: '.$staffLabel,
            $filters['search'] !== '' ? 'Product: '.$filters['search'] : null,
        ])->filter()->implode(' · ');

        return view('admin.exports.print', [
            'title' => 'Product Sales Report',
            'headers' => $headers,
            'rows' => $rows,
            'totalRows' => count($rows),
            'totalRow' => $totalRow,
            'summaryItems' => [
                'Units sold' => number_format((int) ($summary->units_sold ?? 0)),
                'Products sold' => number_format((int) ($summary->products_sold ?? 0)),
                'Orders' => number_format((int) ($summary->orders_count ?? 0)),
                'Average selling price' => '$'.$this->formatAverageSellingPrice($averageSellingPrice),
                'Item sales subtotal' => '$'.number_format((float) ($summary->item_sales_total ?? 0), 2),
                'Product discounts' => '$'.number_format((float) ($summary->item_discount_total ?? 0), 2),
                'Estimated COGS' => '$'.number_format((float) ($summary->cogs_total ?? 0), 2),
                'Estimated gross margin' => '$'.number_format($grossMargin, 2),
            ],
            'generatedAt' => now(),
            'filterSummary' => $filterSummary ?: 'All paid, non-cancelled product sales',
            'shopSettings' => app('shop.settings'),
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        $staffIds = DB::table('users as u')
            ->join('role as r', 'r.role_id', '=', 'u.role_id')
            ->whereIn(DB::raw('LOWER(r.role_name)'), ['admin', 'cashier'])
            ->pluck('u.user_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $validated = $request->validate([
            'source' => ['nullable', Rule::in(['all', 'pos', 'website'])],
            'category_id' => ['nullable', 'integer', 'exists:category,category_id'],
            'staff_id' => ['nullable', Rule::in(array_merge(['website'], $staffIds))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $dateRange = AdminDateFilter::resolve($request, 'start_date', 'end_date');
        $categoryName = null;
        if (! empty($validated['category_id'])) {
            $categoryName = DB::table('category')->where('category_id', $validated['category_id'])->value('category_name');
        }

        return [
            'date_preset' => $dateRange['preset'],
            'start_date' => $dateRange['from'],
            'end_date' => $dateRange['to'],
            'date_filter' => $dateRange,
            'source' => $validated['source'] ?? 'all',
            'category_id' => $validated['category_id'] ?? '',
            'category_name' => $categoryName,
            'staff_id' => $validated['staff_id'] ?? '',
            'search' => trim((string) ($validated['search'] ?? '')),
        ];
    }

    private function filteredItemSalesQuery(array $filters): Builder
    {
        $query = DB::table('order_items as oi')
            ->join('orders as o', 'o.order_id', '=', 'oi.order_id')
            ->join('product as p', 'p.product_id', '=', 'oi.product_id')
            ->leftJoin('category as c', 'c.category_id', '=', 'p.category_id')
            ->where('o.is_paid', true)
            ->where('o.order_status', '<>', 'cancelled');

        if ($filters['start_date']) {
            $query->whereDate('o.order_date', '>=', $filters['start_date']);
        }
        if ($filters['end_date']) {
            $query->whereDate('o.order_date', '<=', $filters['end_date']);
        }
        if ($filters['source'] !== 'all') {
            $query->whereRaw('LOWER(COALESCE(o.order_source, ?)) = ?', ['pos', $filters['source']]);
        }
        if ($filters['category_id'] !== '') {
            $query->where('p.category_id', (int) $filters['category_id']);
        }
        if ($filters['staff_id'] === 'website') {
            $query->whereNull('o.cashier_id')
                ->whereRaw('LOWER(COALESCE(o.order_source, ?)) = ?', ['pos', 'website']);
        } elseif ($filters['staff_id'] !== '') {
            $query->where('o.cashier_id', (int) $filters['staff_id']);
        }
        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where('p.product_name', 'like', '%' . $search . '%');
        }

        return $query;
    }

    private function productPerformanceQuery(Builder $baseQuery): Builder
    {
        return (clone $baseQuery)
            ->select([
                'p.product_id',
                'p.product_name',
                'p.image',
                DB::raw("COALESCE(c.category_name, 'Uncategorized') as category_name"),
            ])
            ->selectRaw('COALESCE(SUM(COALESCE(oi.quantity, 0)), 0) as units_sold')
            ->selectRaw('COUNT(DISTINCT o.order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.price, 0)), 0) as item_sales_total')
            ->selectRaw('COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.price, 0)) / NULLIF(SUM(COALESCE(oi.quantity, 0)), 0), 0) as average_selling_price')
            ->selectRaw('COALESCE(SUM(COALESCE(oi.discount_amount, 0)), 0) as item_discount_total')
            ->selectRaw('COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.unit_cost, p.cost, 0)), 0) as cogs_total')
            ->selectRaw('COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.price, 0)), 0) - COALESCE(SUM(COALESCE(oi.discount_amount, 0)), 0) - COALESCE(SUM(COALESCE(oi.quantity, 0) * COALESCE(oi.unit_cost, p.cost, 0)), 0) as gross_margin')
            ->groupBy('p.product_id', 'p.product_name', 'p.image', 'c.category_name');
    }

    /**
     * Display averaged prices consistently to two decimal places.
     * Item sales remains the exact sum of recorded quantity × unit price.
     */
    private function formatAverageSellingPrice(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    private function safeCsvValue(mixed $value): string|int|float|null
    {
        if (is_string($value) && preg_match('/^[=+@\-\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
