<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockLogController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::orderBy('product_id', 'desc')->get();
        $logs = StockLog::with(['product', 'user'])
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->product_id))
            ->when($request->filled('type'), fn ($query) => $query->whereRaw('LOWER(type) = ?', [strtolower($request->type)]))
            ->latest('log_id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventory', compact('logs', 'products'));
    }

    public function create()
    {
        $products = Product::orderBy('product_id', 'desc')->get();
        $logs = StockLog::with(['product', 'user'])->latest('log_id')->paginate(20);

        return view('admin.inventory.create', compact('products', 'logs'));
    }

    /** Record a new, append-only stock adjustment. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:product,product_id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'note' => 'nullable|string|max:2000',
        ]);

        $saved = DB::transaction(function () use ($validated) {
            $product = Product::where('product_id', $validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

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
