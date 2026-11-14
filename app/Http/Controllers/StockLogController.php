<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockLogController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:product,product_id'],
            'category_id' => ['nullable', 'integer', 'exists:category,category_id'],
            'type' => ['nullable', 'in:in,out'],
            'stock_status' => ['nullable', 'in:all,healthy,low,out'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:120'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $products = Product::query()
            ->whereRaw("LOWER(TRIM(COALESCE(product_type, 'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")
            ->with('category')
            ->orderBy('product_name')
            ->get();
        $categories = Category::orderBy('category_name')->get();

        // Whole-inventory KPIs are intentionally independent of the current filters.
        $stockSummary = Product::query()
            ->whereRaw("LOWER(TRIM(COALESCE(product_type, 'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('COALESCE(SUM(COALESCE(qty, 0)), 0) as total_units')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(qty, 0) BETWEEN 1 AND ? THEN 1 ELSE 0 END), 0) as low_stock', [self::LOW_STOCK_THRESHOLD])
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(qty, 0) <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock')
            ->first();

        $stockItemsQuery = Product::query()
            ->with('category')
            ->whereRaw("LOWER(TRIM(COALESCE(product_type, 'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")
            ->when(!empty($validated['product_id']), fn ($q) => $q->where('product_id', $validated['product_id']))
            ->when(!empty($validated['category_id']), fn ($q) => $q->where('category_id', $validated['category_id']))
            ->when($search !== '', fn ($q) => $q->where('product_name', 'like', "%{$search}%"))
            ->when(($validated['stock_status'] ?? 'all') === 'healthy', fn ($q) => $q->whereRaw('COALESCE(qty, 0) > ?', [self::LOW_STOCK_THRESHOLD]))
            ->when(($validated['stock_status'] ?? 'all') === 'low', fn ($q) => $q->whereRaw('COALESCE(qty, 0) BETWEEN 1 AND ?', [self::LOW_STOCK_THRESHOLD]))
            ->when(($validated['stock_status'] ?? 'all') === 'out', fn ($q) => $q->whereRaw('COALESCE(qty, 0) <= 0'));

        $stockItems = $stockItemsQuery
            ->orderByRaw('CASE WHEN COALESCE(qty, 0) <= 0 THEN 0 WHEN COALESCE(qty, 0) <= '.self::LOW_STOCK_THRESHOLD.' THEN 1 ELSE 2 END')
            ->orderBy('product_name')
            ->paginate(8, ['*'], 'stock_page')
            ->withQueryString();

        $logsQuery = StockLog::with(['product.category', 'user'])
            ->when(!empty($validated['product_id']), fn ($q) => $q->where('product_id', $validated['product_id']))
            ->when(!empty($validated['category_id']), fn ($q) => $q->whereHas('product', fn ($productQuery) => $productQuery->where('category_id', $validated['category_id'])))
            ->when(!empty($validated['type']), fn ($q) => $q->whereRaw('LOWER(type) = ?', [$validated['type']]))
            ->when(!empty($validated['date_from']), fn ($q) => $q->whereDate('log_date', '>=', $validated['date_from']))
            ->when(!empty($validated['date_to']), fn ($q) => $q->whereDate('log_date', '<=', $validated['date_to']))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($nested) use ($search) {
                    $nested->where('reason', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('product_name', 'like', "%{$search}%"));
                });
            });

        $sortDirection = $validated['sort_dir'] ?? 'desc';
        $logs = $logsQuery
            ->orderBy('log_date', $sortDirection)
            ->orderByDesc('log_id')
            ->paginate(12, ['*'], 'log_page')
            ->withQueryString();

        return view('admin.inventory', [
            'logs' => $logs,
            'products' => $products,
            'categories' => $categories,
            'stockItems' => $stockItems,
            'stockSummary' => $stockSummary,
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    public function create()
    {
        $products = Product::query()
            ->whereRaw("LOWER(TRIM(COALESCE(product_type, 'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")
            ->orderBy('product_name')
            ->get();
        $logs = StockLog::with(['product', 'user'])->latest('log_id')->paginate(8);

        return view('admin.inventory.create', compact('products', 'logs'));
    }

    /** Record a new, append-only stock adjustment. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:product,product_id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1|max:1000000',
            'reason' => 'required|string|max:255',
            'note' => 'nullable|string|max:2000',
        ]);

        $saved = DB::transaction(function () use ($validated) {
            $product = Product::where('product_id', $validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $product->isStockTracked()) {
                return 'non_stock';
            }

            if ($validated['type'] === 'out' && (int) $product->qty < (int) $validated['quantity']) {
                return false;
            }

            StockLog::create($validated + ['user_id' => Auth::id()]);
            $delta = $validated['type'] === 'in'
                ? (int) $validated['quantity']
                : -(int) $validated['quantity'];

            $product->qty = (int) $product->qty + $delta;
            $product->save();

            return true;
        }, 3);

        if ($saved === 'non_stock') {
            return back()->withInput()->with('error', 'Non-stock products cannot have inventory adjustments.');
        }
        if (! $saved) {
            return back()->withInput()->with('error', 'Insufficient stock for this stock-out adjustment.');
        }

        return redirect()->route('inventory.index')->with('success', 'Stock movement recorded successfully.');
    }

    /** Stock movements are immutable; corrections must be new adjustment rows. */
    public function edit(StockLog $log)
    {
        return redirect()->route('inventory.index')->with(
            'error',
            'Stock history cannot be edited. Record a new IN or OUT adjustment with a correction note instead.'
        );
    }

    public function update(Request $request, StockLog $log)
    {
        return redirect()->route('inventory.index')->with(
            'error',
            'Stock history cannot be edited. Record a new IN or OUT adjustment with a correction note instead.'
        );
    }

    public function destroy(StockLog $log)
    {
        return redirect()->route('inventory.index')->with(
            'error',
            'Stock history cannot be deleted. Record a new IN or OUT adjustment to reverse an incorrect movement.'
        );
    }
}
