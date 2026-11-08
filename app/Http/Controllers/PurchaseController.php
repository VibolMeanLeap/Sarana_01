<?php

namespace App\Http\Controllers;

use App\Models\AccountingCategory;
use App\Models\CashTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockLog;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $query = Purchase::with(['supplier', 'product', 'user'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('purchase_id', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('product_name', 'like', "%{$search}%"))
                        ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->input('product_id')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->input('supplier_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('purchase_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('purchase_date', '<=', $request->input('date_to')));

        $sortBy = in_array($request->input('sort_by'), ['purchase_date', 'purchase_id', 'quantity', 'cost_price'], true) ? $request->input('sort_by') : 'purchase_date';
        $sortDirection = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $purchases = $query->orderBy($sortBy, $sortDirection)->orderByDesc('purchase_id')->paginate(15)->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::where('product_type', 'stock')->orderBy('product_name')->get();

        return view('admin.purchase', compact('purchases', 'suppliers', 'products'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::where('product_type', 'stock')->orderBy('product_name')->get();

        return view('admin.purchase.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'product_id' => 'required|exists:product,product_id',
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
        ]);

        $selectedProduct = Product::findOrFail($validated['product_id']);
        if (! $selectedProduct->isStockTracked()) {
            return back()->withInput()->withErrors(['product_id' => 'Purchases can only be recorded for Stock products.']);
        }

        DB::transaction(function () use ($validated) {
            $product = Product::where('product_id', $validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $purchase = Purchase::create($validated + ['user_id' => Auth::id()]);
            $product->qty = (int) $product->qty + (int) $validated['quantity'];
            $product->save();

            StockLog::create([
                'product_id' => $product->product_id,
                'user_id' => Auth::id(),
                'type' => 'IN',
                'quantity' => $validated['quantity'],
                'reason' => 'Purchase',
                'note' => "Restock from purchase #{$purchase->purchase_id}",
            ]);

            $this->syncPurchaseExpense($purchase, $validated);
        });

        return redirect()->route('purchase.index')->with('success', 'Purchase recorded, stock updated, and expense linked.');
    }

    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::where('product_type', 'stock')->orderBy('product_name')->get();

        return view('admin.purchase.edit', compact('purchase', 'suppliers', 'products'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'product_id' => 'required|exists:product,product_id',
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
        ]);

        $updated = DB::transaction(function () use ($validated, $purchase) {
            $lockedPurchase = Purchase::where('purchase_id', $purchase->purchase_id)
                ->lockForUpdate()
                ->firstOrFail();

            $productIds = array_values(array_unique([
                (int) $lockedPurchase->product_id,
                (int) $validated['product_id'],
            ]));
            sort($productIds);
            $products = Product::whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $oldProduct = $products->get($lockedPurchase->product_id);
            $newProduct = $products->get($validated['product_id']);
            if (!$oldProduct || !$newProduct || ! $oldProduct->isStockTracked() || ! $newProduct->isStockTracked() || (int) $oldProduct->qty < (int) $lockedPurchase->quantity) {
                return false;
            }

            // Revert the old purchase quantity, then apply the edited purchase.
            $oldProduct->qty = (int) $oldProduct->qty - (int) $lockedPurchase->quantity;
            $oldProduct->save();
            $newProduct->qty = (int) $newProduct->qty + (int) $validated['quantity'];
            $newProduct->save();

            $purchaseNote = "Restock from purchase #{$lockedPurchase->purchase_id}";
            $stockLog = StockLog::where('note', $purchaseNote)->latest('log_id')->first();

            if ((int) $oldProduct->product_id === (int) $newProduct->product_id) {
                if ($stockLog) {
                    $stockLog->update([
                        'quantity' => $validated['quantity'],
                        'type' => 'IN',
                        'reason' => 'Purchase',
                    ]);
                }
            } else {
                if ($stockLog) {
                    $stockLog->update([
                        'type' => 'OUT',
                        'quantity' => $lockedPurchase->quantity,
                        'reason' => 'Purchase correction',
                        'note' => "Stock reversed after editing purchase #{$lockedPurchase->purchase_id}",
                    ]);
                }
                StockLog::create([
                    'product_id' => $newProduct->product_id,
                    'user_id' => Auth::id(),
                    'type' => 'IN',
                    'quantity' => $validated['quantity'],
                    'reason' => 'Purchase',
                    'note' => $purchaseNote,
                ]);
            }

            $lockedPurchase->update($validated);
            $this->syncPurchaseExpense($lockedPurchase, $validated);

            return true;
        });

        if (!$updated) {
            return back()->withInput()->with('error', 'This purchase cannot be changed because the original quantity is no longer available in stock.');
        }

        return redirect()->route('purchase.index')->with('success', 'Purchase, stock history, and linked expense were updated.');
    }

    public function destroy(Purchase $purchase)
    {
        $deleted = DB::transaction(function () use ($purchase) {
            $lockedPurchase = Purchase::where('purchase_id', $purchase->purchase_id)
                ->lockForUpdate()
                ->firstOrFail();
            $product = Product::where('product_id', $lockedPurchase->product_id)
                ->lockForUpdate()
                ->first();

            if (!$product || (int) $product->qty < (int) $lockedPurchase->quantity) {
                return false;
            }

            $product->qty = (int) $product->qty - (int) $lockedPurchase->quantity;
            $product->save();

            $purchaseNote = "Restock from purchase #{$lockedPurchase->purchase_id}";
            $stockLog = StockLog::where('note', $purchaseNote)->latest('log_id')->first();
            if ($stockLog) {
                $stockLog->update([
                    'type' => 'OUT',
                    'quantity' => $lockedPurchase->quantity,
                    'reason' => 'Purchase reversal',
                    'note' => "Stock reversed after deleting purchase #{$lockedPurchase->purchase_id}",
                ]);
            } else {
                StockLog::create([
                    'product_id' => $product->product_id,
                    'user_id' => Auth::id(),
                    'type' => 'OUT',
                    'quantity' => $lockedPurchase->quantity,
                    'reason' => 'Purchase reversal',
                    'note' => "Stock reversed after deleting purchase #{$lockedPurchase->purchase_id}",
                ]);
            }

            CashTransaction::where('purchase_id', $lockedPurchase->purchase_id)->delete();
            $lockedPurchase->delete();

            return true;
        });

        if (!$deleted) {
            return back()->with('error', 'This purchase cannot be removed because some of its stock is no longer available.');
        }

        return back()->with('success', 'Purchase removed; stock and linked finance entry were reversed.');
    }

    private function syncPurchaseExpense(Purchase $purchase, array $validated): void
    {
        $expenseCategory = AccountingCategory::where('id_type', 2)->orderBy('id')->first();
        if (!$expenseCategory) {
            return;
        }

        CashTransaction::updateOrCreate(
            ['purchase_id' => $purchase->purchase_id],
            [
                'category_id' => $expenseCategory->id,
                'amount' => (float) $validated['quantity'] * (float) $validated['cost_price'],
                'transaction_date' => $validated['purchase_date'],
                'user_id' => Auth::id(),
                'note' => "Purchase #{$purchase->purchase_id}",
            ]
        );
    }
}
