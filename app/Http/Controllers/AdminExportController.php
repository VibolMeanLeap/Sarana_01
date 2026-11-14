<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AdminExportController extends Controller
{
    /**
     * Export the currently filtered report as an Excel-compatible UTF-8 CSV.
     * CSV is intentionally used because this project has no spreadsheet package
     * installed; it opens directly in Microsoft Excel without adding dependencies.
     */
    public function excel(Request $request, string $type)
    {
        $this->validateFilters($request);
        $report = $this->buildReport($type, $request);
        $shopSettings = app('shop.settings');
        $shopName = trim((string) ($shopSettings?->shop_name ?? '')) ?: config('app.name', 'Store');
        $storeFilePrefix = Str::slug($shopName) ?: 'store';
        $filename = $storeFilePrefix.'-'.(Str::slug($type) ?: 'report').'-'.now()->format('Ymd-His').'.csv';
        $generatedAt = now();

        return response()->streamDownload(function () use ($report, $shopSettings, $shopName, $generatedAt) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM makes Khmer and other Unicode text display correctly in Excel.
            fwrite($handle, "\xEF\xBB\xBF");

            // Put the current Store Profile above the tabular report data.
            fputcsv($handle, [$this->safeCsvCell($shopName)]);
            if (filled($shopSettings?->address)) {
                fputcsv($handle, ['Address', $this->safeCsvCell($shopSettings->address)]);
            }
            if (filled($shopSettings?->tel)) {
                fputcsv($handle, ['Telephone', $this->safeCsvCell($shopSettings->tel)]);
            }
            if (is_numeric($shopSettings?->exchange_rate) && (float) $shopSettings->exchange_rate > 0) {
                fputcsv($handle, ['Exchange rate (USD/KHR)', number_format((float) $shopSettings->exchange_rate, 2, '.', '')]);
            }
            fputcsv($handle, ['Report', $this->safeCsvCell($report['title'])]);
            fputcsv($handle, ['Generated at', $generatedAt->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);
            fputcsv($handle, $report['headers']);

            foreach ($report['rows'] as $row) {
                fputcsv($handle, array_map([$this, 'safeCsvCell'], $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Open a clean, print-optimized report. In the browser choose
     * "Save as PDF" in the print destination to create a PDF file.
     */
    public function pdf(Request $request, string $type)
    {
        $this->validateFilters($request);
        $report = $this->buildReport($type, $request);

        return view('admin.exports.print', $report + [
            'generatedAt' => now(),
            'filterSummary' => $this->filterSummary($request),
            'shopSettings' => app('shop.settings'),
        ]);
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'period' => ['nullable', 'in:today,week,month'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
    }

    private function buildReport(string $type, Request $request): array
    {
        $report = match ($type) {
            'products' => $this->products($request),
            'categories' => $this->categories($request),
            'suppliers' => $this->suppliers($request),
            'customers' => $this->customers($request),
            'customer-accounts' => $this->customerAccounts($request),
            'staff-accounts' => $this->staffAccounts($request),
            'accounting-categories' => $this->accountingCategories($request),
            'offers' => $this->offers($request),
            'orders' => $this->orders($request),
            'order-items' => $this->orderItems($request),
            'purchases' => $this->purchases($request),
            'inventory' => $this->inventory($request),
            'transactions' => $this->transactions($request),
            'deliveries' => $this->deliveries($request),
            'reviews' => $this->reviews($request),
            default => null,
        };

        if ($report === null) {
            abort(404, 'The requested report is not available.');
        }

        return $report;
    }

    private function products(Request $request): array
    {
        $query = DB::table('product as p')
            ->leftJoin('category as c', 'c.category_id', '=', 'p.category_id')
            ->leftJoin('suppliers as s', 's.supplier_id', '=', 'p.supplier_id')
            ->select([
                'p.product_id as Product_ID', 'p.product_name as Product',
                'c.category_name as Category', 's.name as Supplier',
                'p.price as Selling_Price', 'p.cost as Unit_Cost',
                'p.qty as Stock', DB::raw("CASE WHEN LOWER(TRIM(COALESCE(p.product_type, ''))) IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked') THEN 'Non-stock' ELSE 'Stock' END as Type"), 'p.status as Status',
            ]);

        $this->productFilters($query, $request, 'p');
        $sort = $this->sortColumn($request, [
            'product_id' => 'p.product_id', 'product_name' => 'p.product_name',
            'price' => 'p.price', 'qty' => 'p.qty', 'status' => 'p.status',
        ], 'p.product_id');
        $query->orderBy($sort, $this->sortDirection($request));

        return $this->report('Product Inventory', ['Product ID', 'Product', 'Category', 'Supplier', 'Selling Price (USD)', 'Unit Cost (USD)', 'Stock', 'Type', 'Status'], $query);
    }

    private function categories(Request $request): array
    {
        $query = DB::table('category as c')
            ->leftJoin('product as p', 'p.category_id', '=', 'c.category_id')
            ->select([
                'c.category_id as Category_ID', 'c.category_name as Category',
                'c.description as Description', DB::raw('COUNT(p.product_id) as Product_Count'),
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(function ($w) use ($term) {
                    $w->where('c.category_name', 'like', "%{$term}%")
                        ->orWhere('c.description', 'like', "%{$term}%");
                });
            })
            ->groupBy('c.category_id', 'c.category_name', 'c.description');
        $query->orderBy($this->sortColumn($request, ['category_id' => 'c.category_id', 'category_name' => 'c.category_name'], 'c.category_id'), $this->sortDirection($request));

        return $this->report('Product Categories', ['Category ID', 'Category', 'Description', 'Product Count'], $query);
    }

    private function suppliers(Request $request): array
    {
        $query = DB::table('suppliers as s')
            ->select(['s.supplier_id as Supplier_ID', 's.name as Supplier', 's.email as Email', 's.phone as Phone', 's.address as Address'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('s.name', 'like', "%{$term}%")
                    ->orWhere('s.email', 'like', "%{$term}%")
                    ->orWhere('s.phone', 'like', "%{$term}%"));
            });
        $query->orderBy($this->sortColumn($request, ['supplier_id' => 's.supplier_id', 'name' => 's.name'], 's.supplier_id'), $this->sortDirection($request));

        return $this->report('Suppliers', ['Supplier ID', 'Supplier', 'Email', 'Phone', 'Address'], $query);
    }

    private function customers(Request $request): array
    {
        $query = DB::table('customer as c')
            ->leftJoin('users as u', 'u.user_id', '=', 'c.user_id')
            ->select(['c.customer_id as Customer_ID', 'c.name as Customer', 'c.phone as Phone', 'u.email as Email', 'u.status as Account_Status'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('c.name', 'like', "%{$term}%")
                    ->orWhere('c.phone', 'like', "%{$term}%")
                    ->orWhere('u.email', 'like', "%{$term}%"));
            });
        $query->orderBy($this->sortColumn($request, ['customer_id' => 'c.customer_id', 'name' => 'c.name'], 'c.customer_id'), $this->sortDirection($request));

        return $this->report('Customer Directory', ['Customer ID', 'Customer', 'Phone', 'Email', 'Account Status'], $query);
    }

    private function customerAccounts(Request $request): array
    {
        $query = DB::table('users as u')
            ->join('role as r', 'r.role_id', '=', 'u.role_id')
            ->whereRaw('LOWER(r.role_name) = ?', ['customer'])
            ->select(['u.user_id as Account_ID', 'u.name as Customer', 'u.username as Username', 'u.email as Email', 'u.phone as Phone', 'u.status as Status', 'u.created_at as Registered_At'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('u.name', 'like', "%{$term}%")
                    ->orWhere('u.username', 'like', "%{$term}%")
                    ->orWhere('u.email', 'like', "%{$term}%")
                    ->orWhere('u.phone', 'like', "%{$term}%"));
            })
            ->when(in_array($request->input('status'), ['active', 'inactive'], true), fn ($q) => $q->where('u.status', $request->input('status')));
        $this->applyDateRange($query, 'u.created_at', $request, ['start_date', 'date_from', 'from'], ['end_date', 'date_to', 'to']);
        $query->orderBy($this->sortColumn($request, ['user_id' => 'u.user_id', 'name' => 'u.name', 'created_at' => 'u.created_at'], 'u.created_at'), $this->sortDirection($request));

        return $this->report('Customer Accounts', ['Account ID', 'Customer', 'Username', 'Email', 'Phone', 'Status', 'Registered At'], $query);
    }

    private function staffAccounts(Request $request): array
    {
        $query = DB::table('users as u')
            ->join('role as r', 'r.role_id', '=', 'u.role_id')
            ->whereIn(DB::raw('LOWER(r.role_name)'), ['admin', 'cashier'])
            ->select(['u.user_id as Staff_ID', 'u.name as Staff_Name', 'u.username as Username', 'u.email as Email', 'u.phone as Phone', 'r.role_name as Role', 'u.status as Status', 'u.created_at as Created_At'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('u.name', 'like', "%{$term}%")
                    ->orWhere('u.username', 'like', "%{$term}%")
                    ->orWhere('u.email', 'like', "%{$term}%")
                    ->orWhere('u.phone', 'like', "%{$term}%"));
            })
            ->when(in_array($request->input('role'), ['admin', 'cashier'], true), fn ($q) => $q->whereRaw('LOWER(r.role_name) = ?', [$request->input('role')]))
            ->when(in_array($request->input('status'), ['active', 'inactive'], true), fn ($q) => $q->where('u.status', $request->input('status')));

        $this->applyDateRange($query, 'u.created_at', $request, ['start_date', 'date_from', 'from'], ['end_date', 'date_to', 'to']);
        $query->orderBy($this->sortColumn($request, ['user_id' => 'u.user_id', 'name' => 'u.name', 'created_at' => 'u.created_at'], 'u.created_at'), $this->sortDirection($request));

        return $this->report('Staff Accounts', ['Staff ID', 'Name', 'Username', 'Email', 'Phone', 'Role', 'Status', 'Created At'], $query);
    }

    private function accountingCategories(Request $request): array
    {
        $query = DB::table('accounting_category as ac')
            ->leftJoin('in_and_exp_types as iet', 'iet.id_type', '=', 'ac.id_type')
            ->where('ac.id_type', 2)
            ->select(['ac.id as Category_ID', 'ac.name as Category', 'iet.name as Type'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where('ac.name', 'like', "%{$term}%");
            });
        $query->orderBy($this->sortColumn($request, ['id' => 'ac.id', 'name' => 'ac.name'], 'ac.id'), $this->sortDirection($request));

        return $this->report('Accounting Categories', ['Category ID', 'Category', 'Type'], $query);
    }

    private function offers(Request $request): array
    {
        $today = now()->toDateString();
        $query = DB::table('offers as o')
            ->join('product as p', 'p.product_id', '=', 'o.product_id')
            ->leftJoin('category as c', 'c.category_id', '=', 'p.category_id')
            ->select(['o.offer_id as Offer_ID', 'p.product_name as Product', 'c.category_name as Category', 'p.price as Regular_Price', 'o.discount as Discount_Percent', 'o.new_price as Offer_Price', 'o.start_date as Starts_At', 'o.end_date as Ends_At'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('p.product_name', 'like', "%{$term}%")
                    ->orWhere('o.offer_id', 'like', "%{$term}%"));
            })
            ->when($request->filled('product_id'), fn ($q) => $q->where('o.product_id', $request->input('product_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('p.category_id', $request->input('category_id')));

        if ($request->input('status') === 'active') {
            $query->whereDate('o.start_date', '<=', $today)->whereDate('o.end_date', '>=', $today);
        } elseif ($request->input('status') === 'upcoming') {
            $query->whereDate('o.start_date', '>', $today);
        } elseif ($request->input('status') === 'expired') {
            $query->whereDate('o.end_date', '<', $today);
        }
        if ($request->filled('date_from')) $query->whereDate('o.start_date', '>=', $request->input('date_from'));
        if ($request->filled('date_to')) $query->whereDate('o.end_date', '<=', $request->input('date_to'));
        $query->orderBy($this->sortColumn($request, ['offer_id' => 'o.offer_id', 'start_date' => 'o.start_date', 'end_date' => 'o.end_date', 'discount' => 'o.discount'], 'o.start_date'), $this->sortDirection($request));

        return $this->report('Promotional Offers', ['Offer ID', 'Product', 'Category', 'Regular Price (USD)', 'Discount (%)', 'Offer Price (USD)', 'Start Date', 'End Date'], $query);
    }

    private function orders(Request $request): array
    {
        $query = DB::table('orders as o')
            ->leftJoin('customer as c', 'c.customer_id', '=', 'o.customer_id')
            ->leftJoin('users as u', 'u.user_id', '=', 'o.cashier_id')
            ->leftJoin('order_type as ot', 'ot.order_type_id', '=', 'o.order_type_id')
            ->leftJoin('payment_method as pm', 'pm.payment_method_id', '=', 'o.payment_method_id')
            ->select(['o.order_id as Order_ID', 'o.waiting_num as Queue_No', 'c.name as Customer', 'c.phone as Customer_Phone', 'u.name as Cashier', 'ot.order_name as Order_Type', 'o.order_source as Source', 'o.order_status as Order_Status', 'o.payment_status as Payment_Status', 'o.total_amount as Subtotal', 'o.discount as Discount_Percent', 'o.final_price as Final_Total', 'o.order_date as Order_Date'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('o.order_id', 'like', "%{$term}%")
                    ->orWhere('c.name', 'like', "%{$term}%")
                    ->orWhere('c.phone', 'like', "%{$term}%")
                    ->orWhere('u.name', 'like', "%{$term}%")
                    ->orWhere('o.customer_note', 'like', "%{$term}%"));
            });
        $paymentStatus = $request->input('payment_status', $request->input('status'));
        if (in_array($paymentStatus, ['paid', 'unpaid'], true)) {
            $query->where('o.is_paid', $paymentStatus === 'paid' ? 1 : 0);
        }
        if ($request->filled('order_status')) $query->where('o.order_status', $request->input('order_status'));
        if (in_array($request->input('source'), ['pos', 'website'], true)) $query->where('o.order_source', $request->input('source'));
        if ($request->input('review_status') === 'open') {
            $query->whereExists(function ($subquery) {
                $subquery->selectRaw('1')->from('order_review_flags as orf')
                    ->whereColumn('orf.order_id', 'o.order_id')->where('orf.status', 'open');
            });
        } elseif ($request->input('review_status') === 'clear') {
            $query->whereNotExists(function ($subquery) {
                $subquery->selectRaw('1')->from('order_review_flags as orf')
                    ->whereColumn('orf.order_id', 'o.order_id')->where('orf.status', 'open');
            });
        }
        if (in_array($request->input('period'), ['today', 'week', 'month'], true)) {
            $periodStart = match ($request->input('period')) {
                'today' => today()->toDateString(),
                'week' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'month' => now()->startOfMonth()->toDateString(),
            };
            $query->whereDate('o.order_date', '>=', $periodStart)
                ->whereDate('o.order_date', '<=', today()->toDateString());
        } else {
            $this->applyDateRange($query, 'o.order_date', $request, ['start_date', 'date_from', 'from'], ['end_date', 'date_to', 'to']);
        }
        $query->orderBy($this->sortColumn($request, ['order_date' => 'o.order_date', 'order_id' => 'o.order_id', 'final_price' => 'o.final_price'], 'o.order_date'), $this->sortDirection($request));

        return $this->report('Orders', ['Order ID', 'Queue No', 'Customer', 'Customer Phone', 'Cashier', 'Order Type', 'Source', 'Order Status', 'Payment Status', 'Subtotal (USD)', 'Discount (%)', 'Final Total (USD)', 'Order Date'], $query);
    }

    private function orderItems(Request $request): array
    {
        $query = DB::table('order_items as oi')
            ->leftJoin('orders as o', 'o.order_id', '=', 'oi.order_id')
            ->leftJoin('product as p', 'p.product_id', '=', 'oi.product_id')
            ->leftJoin('category as c', 'c.category_id', '=', 'p.category_id')
            ->select(['oi.order_item_id as Item_ID', 'oi.order_id as Order_ID', 'o.order_date as Order_Date', 'p.product_name as Product', 'c.category_name as Category', 'oi.quantity as Quantity', 'oi.price as Unit_Price', DB::raw('(COALESCE(oi.quantity,0) * COALESCE(oi.price,0)) as Line_Total')])
            ->when($request->filled('order_id'), fn ($q) => $q->where('oi.order_id', $request->input('order_id')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('oi.product_id', $request->input('product_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('p.category_id', $request->input('category_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('oi.order_id', 'like', "%{$term}%")
                    ->orWhere('p.product_name', 'like', "%{$term}%"));
            });
        $this->applyDateRange($query, 'o.order_date', $request, ['start_date', 'date_from', 'from'], ['end_date', 'date_to', 'to']);
        $query->orderBy($this->sortColumn($request, ['order_date' => 'o.order_date', 'order_id' => 'oi.order_id', 'quantity' => 'oi.quantity', 'price' => 'oi.price'], 'o.order_date'), $this->sortDirection($request));

        return $this->report('Order Items', ['Item ID', 'Order ID', 'Order Date', 'Product', 'Category', 'Quantity', 'Unit Price (USD)', 'Line Total (USD)'], $query);
    }

    private function purchases(Request $request): array
    {
        $query = DB::table('purchases as pu')
            ->leftJoin('product as p', 'p.product_id', '=', 'pu.product_id')
            ->leftJoin('suppliers as s', 's.supplier_id', '=', 'pu.supplier_id')
            ->leftJoin('users as u', 'u.user_id', '=', 'pu.user_id')
            ->select(['pu.purchase_id as Purchase_ID', 's.name as Supplier', 'p.product_name as Product', 'pu.quantity as Quantity', 'pu.cost_price as Unit_Cost', DB::raw('(COALESCE(pu.quantity,0) * COALESCE(pu.cost_price,0)) as Total_Cost'), 'pu.purchase_date as Purchase_Date', 'u.name as Recorded_By'])
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('pu.supplier_id', $request->input('supplier_id')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('pu.product_id', $request->input('product_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('pu.purchase_id', 'like', "%{$term}%")
                    ->orWhere('p.product_name', 'like', "%{$term}%")
                    ->orWhere('s.name', 'like', "%{$term}%"));
            });
        $this->applyDateRange($query, 'pu.purchase_date', $request);
        $query->orderBy($this->sortColumn($request, ['purchase_date' => 'pu.purchase_date', 'purchase_id' => 'pu.purchase_id', 'total_cost' => DB::raw('(COALESCE(pu.quantity,0) * COALESCE(pu.cost_price,0))')], 'pu.purchase_date'), $this->sortDirection($request));

        return $this->report('Purchases', ['Purchase ID', 'Supplier', 'Product', 'Quantity', 'Unit Cost (USD)', 'Total Cost (USD)', 'Purchase Date', 'Recorded By'], $query);
    }

    private function inventory(Request $request): array
    {
        $query = DB::table('stock_logs as sl')
            ->leftJoin('product as p', 'p.product_id', '=', 'sl.product_id')
            ->leftJoin('category as c', 'c.category_id', '=', 'p.category_id')
            ->leftJoin('users as u', 'u.user_id', '=', 'sl.user_id')
            ->select(['sl.log_id as Log_ID', 'sl.log_date as Movement_Date', 'p.product_name as Product', 'c.category_name as Category', 'sl.type as Movement', 'sl.quantity as Quantity', 'sl.reason as Reason', 'sl.note as Note', 'u.name as Recorded_By'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('sl.product_id', $request->input('product_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('p.category_id', $request->input('category_id')))
            ->when(in_array(strtolower((string) $request->input('type')), ['in', 'out'], true), fn ($q) => $q->whereRaw('LOWER(sl.type) = ?', [strtolower($request->input('type'))]))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('p.product_name', 'like', "%{$term}%")
                    ->orWhere('sl.reason', 'like', "%{$term}%")
                    ->orWhere('sl.note', 'like', "%{$term}%"));
            });
        $this->applyDateRange($query, 'sl.log_date', $request);
        $query->orderBy($this->sortColumn($request, ['log_date' => 'sl.log_date', 'log_id' => 'sl.log_id'], 'sl.log_date'), $this->sortDirection($request));

        return $this->report('Inventory Movements', ['Log ID', 'Movement Date', 'Product', 'Category', 'Movement', 'Quantity', 'Reason', 'Note', 'Recorded By'], $query);
    }

    private function transactions(Request $request): array
    {
        $query = DB::table('cash_transactions as ct')
            ->join('accounting_category as ac', 'ac.id', '=', 'ct.category_id')
            ->leftJoin('in_and_exp_types as iet', 'iet.id_type', '=', 'ac.id_type')
            ->where('ac.id_type', 2)
            ->leftJoin('users as u', 'u.user_id', '=', 'ct.user_id')
            ->select(['ct.id as Transaction_ID', 'ct.transaction_date as Transaction_Date', 'iet.name as Type', 'ac.name as Category', 'ct.amount as Amount', 'ct.order_id as Order_ID', 'ct.purchase_id as Purchase_ID', 'u.name as Recorded_By', 'ct.note as Note'])
            ->when($request->filled('category_id'), fn ($q) => $q->where('ct.category_id', $request->input('category_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('ct.note', 'like', "%{$term}%")
                    ->orWhere('ac.name', 'like', "%{$term}%")
                    ->orWhere('ct.order_id', 'like', "%{$term}%")
                    ->orWhere('ct.purchase_id', 'like', "%{$term}%"));
            });
        $this->applyDateRange($query, 'ct.transaction_date', $request, ['from', 'date_from', 'start_date'], ['to', 'date_to', 'end_date']);
        $query->orderBy($this->sortColumn($request, ['transaction_date' => 'ct.transaction_date', 'id' => 'ct.id', 'amount' => 'ct.amount'], 'ct.transaction_date'), $this->sortDirection($request));

        return $this->report('Operating Expenses', ['Transaction ID', 'Transaction Date', 'Type', 'Category', 'Amount (USD)', 'Order ID', 'Purchase ID', 'Recorded By', 'Note'], $query);
    }

    private function deliveries(Request $request): array
    {
        $query = DB::table('delivery as d')
            ->leftJoin('orders as o', 'o.order_id', '=', 'd.order_id')
            ->leftJoin('customer as c', 'c.customer_id', '=', 'o.customer_id')
            ->select(['d.delivery_id as Delivery_ID', 'd.order_id as Order_ID', 'o.order_date as Order_Date', 'c.name as Customer', 'c.phone as Customer_Phone', 'd.address as Address', 'd.cost as Delivery_Cost', 'd.status as Status'])
            ->when($request->filled('status'), fn ($q) => $q->where('d.status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('d.order_id', 'like', "%{$term}%")
                    ->orWhere('c.name', 'like', "%{$term}%")
                    ->orWhere('d.address', 'like', "%{$term}%"));
            });
        $this->applyDateRange($query, 'o.order_date', $request, ['start_date', 'date_from', 'from'], ['end_date', 'date_to', 'to']);
        $query->orderBy($this->sortColumn($request, ['order_date' => 'o.order_date', 'delivery_id' => 'd.delivery_id'], 'o.order_date'), $this->sortDirection($request));

        return $this->report('Deliveries', ['Delivery ID', 'Order ID', 'Order Date', 'Customer', 'Customer Phone', 'Address', 'Delivery Cost (USD)', 'Status'], $query);
    }

    private function reviews(Request $request): array
    {
        $query = DB::table('reviews as r')
            ->leftJoin('orders as o', 'o.order_id', '=', 'r.order_id')
            ->leftJoin('customer as c', 'c.customer_id', '=', 'r.customer_id')
            ->select(['r.review_id as Review_ID', 'r.order_id as Order_ID', 'o.order_date as Order_Date', 'c.name as Customer', 'r.rating as Rating', 'r.comment as Comment', 'r.is_approved as Published'])
            ->when($request->filled('rating'), fn ($q) => $q->where('r.rating', $request->input('rating')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(fn ($w) => $w->where('c.name', 'like', "%{$term}%")
                    ->orWhere('r.comment', 'like', "%{$term}%")
                    ->orWhere('r.order_id', 'like', "%{$term}%"));
            });
        $this->applyDateRange($query, 'o.order_date', $request, ['start_date', 'date_from', 'from'], ['end_date', 'date_to', 'to']);
        $query->orderBy($this->sortColumn($request, ['order_date' => 'o.order_date', 'review_id' => 'r.review_id', 'rating' => 'r.rating'], 'o.order_date'), $this->sortDirection($request));

        return $this->report('Customer Reviews', ['Review ID', 'Order ID', 'Order Date', 'Customer', 'Rating', 'Comment', 'Published'], $query);
    }

    private function productFilters(Builder $query, Request $request, string $alias): void
    {
        $query->when($request->filled('search'), function ($q) use ($request, $alias) {
            $term = trim((string) $request->input('search'));
            $q->where(fn ($w) => $w->where($alias.'.product_name', 'like', "%{$term}%")
                ->orWhere($alias.'.product_type', 'like', "%{$term}%")
                ->orWhere($alias.'.description', 'like', "%{$term}%"));
        });
        $categoryId = $request->input('category_id', $request->input('category'));
        if ($categoryId !== null && $categoryId !== '') $query->where($alias.'.category_id', $categoryId);
        if ($request->filled('status') && in_array($request->input('status'), ['Active', 'Inactive'], true)) $query->where($alias.'.status', $request->input('status'));
        if ($request->filled('product_type') && in_array($request->input('product_type'), ['stock', 'non-stock'], true)) {
            if ($request->input('product_type') === 'non-stock') {
                $query->whereRaw("LOWER(TRIM(COALESCE({$alias}.product_type, ''))) IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')");
            } else {
                $query->whereRaw("LOWER(TRIM(COALESCE({$alias}.product_type, ''))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')");
            }
        }
    }

    private function applyDateRange(Builder $query, string $column, Request $request, array $fromKeys = ['date_from', 'start_date', 'from'], array $toKeys = ['date_to', 'end_date', 'to']): void
    {
        $from = $this->firstFilled($request, $fromKeys);
        $to = $this->firstFilled($request, $toKeys);
        if ($from) $query->whereDate($column, '>=', $from);
        if ($to) $query->whereDate($column, '<=', $to);
    }

    private function firstFilled(Request $request, array $keys): ?string
    {
        foreach ($keys as $key) {
            if ($request->filled($key)) return (string) $request->input($key);
        }
        return null;
    }

    private function sortColumn(Request $request, array $allowed, string $default): mixed
    {
        $requested = (string) $request->input('sort_by', '');
        return $allowed[$requested] ?? $default;
    }

    private function sortDirection(Request $request): string
    {
        return strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
    }

    private function report(string $title, array $headers, Builder $query): array
    {
        $rows = $query->get()->map(fn ($row) => array_values((array) $row))->all();
        return [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'totalRows' => count($rows),
        ];
    }

    private function safeCsvCell(mixed $value): string
    {
        $cell = $value === null ? '' : (string) $value;
        // Prevent spreadsheet formula injection from untrusted free-text fields.
        // Numeric negative values remain numeric; non-numeric cells beginning with
        // optional whitespace and =, +, -, or @ are treated as formulas.
        if ($cell !== '' && ! is_numeric($cell) && preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $cell)) {
            return "'".$cell;
        }
        return $cell;
    }

    private function filterSummary(Request $request): string
    {
        $labels = [];
        foreach (['search' => 'Search', 'category_id' => 'Category ID', 'category' => 'Category ID', 'product_id' => 'Product ID', 'order_id' => 'Order ID', 'supplier_id' => 'Supplier ID', 'status' => 'Status', 'payment_status' => 'Payment', 'order_status' => 'Order status', 'source' => 'Source', 'period' => 'Period', 'rating' => 'Rating', 'type' => 'Type', 'role' => 'Role', 'start_date' => 'From', 'date_from' => 'From', 'from' => 'From', 'end_date' => 'To', 'date_to' => 'To', 'to' => 'To'] as $key => $label) {
            if ($request->filled($key)) $labels[] = $label.': '.$request->input($key);
        }
        return $labels ? implode(' · ', array_unique($labels)) : 'All records';
    }
}
