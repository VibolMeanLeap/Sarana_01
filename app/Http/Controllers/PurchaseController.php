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
use Illuminate\Support\Facades\Storage;
use Throwable;
use App\Support\AdminDateFilter;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'product_id' => ['nullable', 'integer', 'exists:product,product_id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,supplier_id'],
            'sort_by' => ['nullable', 'in:purchase_date,purchase_id,quantity,cost_price'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'in:10,15,25,50,100'],
        ]);

        $dateFilter = AdminDateFilter::resolve($request, 'date_from', 'date_to');
        $search = trim((string) ($validated['search'] ?? ''));
        $query = Purchase::with(['supplier', 'product', 'user'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('purchase_id', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('product_name', 'like', "%{$search}%"))
                        ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(!empty($validated['product_id']), fn ($q) => $q->where('product_id', $validated['product_id']))
            ->when(!empty($validated['supplier_id']), fn ($q) => $q->where('supplier_id', $validated['supplier_id']))
            ;
        AdminDateFilter::apply($query, 'purchase_date', $dateFilter);

        $summaryQuery = clone $query;
        $summary = [
            'count' => (clone $summaryQuery)->count(),
            'quantity' => (int) (clone $summaryQuery)->sum('quantity'),
            'spend' => (float) (clone $summaryQuery)->selectRaw('COALESCE(SUM(COALESCE(quantity,0) * COALESCE(cost_price,0)),0) as aggregate')->value('aggregate'),
        ];

        $sortBy = $validated['sort_by'] ?? 'purchase_date';
        $sortDirection = $validated['sort_dir'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 15);
        $purchases = $query->orderBy($sortBy, $sortDirection)
            ->orderByDesc('purchase_id')
            ->paginate($perPage)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::whereRaw("LOWER(TRIM(COALESCE(product_type, 'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")
            ->orderBy('product_name')->get();

        return view('admin.purchase', compact('purchases', 'suppliers', 'products', 'summary', 'dateFilter'));
    }

    /** Download a purchase receipt from private storage (admin routes only). */
    public function receipt(Purchase $purchase)
    {
        $path = $purchase->receipt_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $filename = 'purchase-'.$purchase->purchase_id.'-receipt'.($extension ? '.'.$extension : '');

        return Storage::disk('local')->download($path, $filename);
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::whereRaw("LOWER(TRIM(COALESCE(product_type, 'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")->orderBy('product_name')->get();

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
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $selectedProduct = Product::findOrFail($validated['product_id']);
        if (! $selectedProduct->isStockTracked()) {
            return back()->withInput()->withErrors(['product_id' => 'Purchases can only be recorded for Stock products.']);
        }

        $receiptPath = $request->hasFile('receipt')
            ? $request->file('receipt')->store('purchase-receipts', 'local')
            : null;
        if ($request->hasFile('receipt') && ! $receiptPath) {
            return back()->withInput()->withErrors(['receipt' => 'The receipt could not be saved. Please try again.']);
        }

        $purchaseData = $validated;
        unset($purchaseData['receipt']);

        try {
            DB::transaction(function () use ($purchaseData, $receiptPath) {
                $product = Product::where('product_id', $purchaseData['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $purchase = Purchase::create($purchaseData + [
                    'user_id' => Auth::id(),
                    'receipt_path' => $receiptPath,
                ]);

                $product->qty = (int) $product->qty + (int) $purchaseData['quantity'];
                $product->save();

                StockLog::create([
                    'product_id' => $product->product_id,
                    'user_id' => Auth::id(),
                    'type' => 'IN',
                    'quantity' => $purchaseData['quantity'],
                    'reason' => 'Purchase',
                    'note' => "Restock from purchase #{$purchase->purchase_id}",
                ]);

                $this->syncPurchaseExpense($purchase, $purchaseData);
            });
        } catch (Throwable $exception) {
            if ($receiptPath) {
                Storage::disk('local')->delete($receiptPath);
            }
            throw $exception;
        }

        return redirect()->route('purchase.index')->with(
            'success',
            'Purchase recorded, stock updated, and expense linked.'.($receiptPath ? ' Receipt attached securely.' : '')
        );
    }

    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::whereRaw("LOWER(TRIM(COALESCE(product_type, 'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")->orderBy('product_name')->get();

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
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'remove_receipt' => 'nullable|boolean',
        ]);

        $newReceiptPath = $request->hasFile('receipt')
            ? $request->file('receipt')->store('purchase-receipts', 'local')
            : null;
        if ($request->hasFile('receipt') && ! $newReceiptPath) {
            return back()->withInput()->withErrors(['receipt' => 'The receipt could not be saved. Please try again.']);
        }

        $purchaseData = $validated;
        unset($purchaseData['receipt'], $purchaseData['remove_receipt']);
        $removeReceipt = $request->boolean('remove_receipt');
        $oldReceiptPath = null;

        try {
            $updated = DB::transaction(function () use ($purchaseData, $purchase, $newReceiptPath, $removeReceipt, &$oldReceiptPath) {
                $lockedPurchase = Purchase::where('purchase_id', $purchase->purchase_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $productIds = array_values(array_unique([
                    (int) $lockedPurchase->product_id,
                    (int) $purchaseData['product_id'],
                ]));
                sort($productIds);
                $products = Product::whereIn('product_id', $productIds)
                    ->orderBy('product_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');

                $oldProduct = $products->get($lockedPurchase->product_id);
                $newProduct = $products->get($purchaseData['product_id']);
                if (! $oldProduct || ! $newProduct || ! $oldProduct->isStockTracked() || ! $newProduct->isStockTracked() || (int) $oldProduct->qty < (int) $lockedPurchase->quantity) {
                    return false;
                }

                // Reverse the previous received quantity, then apply the corrected purchase.
                $oldProduct->qty = (int) $oldProduct->qty - (int) $lockedPurchase->quantity;
                $oldProduct->save();
                $newProduct->qty = (int) $newProduct->qty + (int) $purchaseData['quantity'];
                $newProduct->save();

                $purchaseNote = "Restock from purchase #{$lockedPurchase->purchase_id}";
                $stockLog = StockLog::where('note', $purchaseNote)->latest('log_id')->first();

                if ((int) $oldProduct->product_id === (int) $newProduct->product_id) {
                    if ($stockLog) {
                        $stockLog->update([
                            'quantity' => $purchaseData['quantity'],
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
                        'quantity' => $purchaseData['quantity'],
                        'reason' => 'Purchase',
                        'note' => $purchaseNote,
                    ]);
                }

                $oldReceiptPath = $lockedPurchase->receipt_path;
                if ($newReceiptPath) {
                    $purchaseData['receipt_path'] = $newReceiptPath;
                } elseif ($removeReceipt) {
                    $purchaseData['receipt_path'] = null;
                }

                $lockedPurchase->update($purchaseData);
                $this->syncPurchaseExpense($lockedPurchase, $purchaseData);

                return true;
            });
        } catch (Throwable $exception) {
            if ($newReceiptPath) {
                Storage::disk('local')->delete($newReceiptPath);
            }
            throw $exception;
        }

        if (! $updated) {
            if ($newReceiptPath) {
                Storage::disk('local')->delete($newReceiptPath);
            }
            return back()->withInput()->with('error', 'This purchase cannot be changed because the original quantity is no longer available in stock.');
        }

        if (($newReceiptPath || $removeReceipt) && $oldReceiptPath) {
            Storage::disk('local')->delete($oldReceiptPath);
        }

        return redirect()->route('purchase.index')->with('success', 'Purchase, stock history, linked expense, and receipt details were updated.');
    }

    public function destroy(Purchase $purchase)
    {
        $receiptPath = $purchase->receipt_path;
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

        if ($receiptPath) {
            Storage::disk('local')->delete($receiptPath);
        }

        return back()->with('success', 'Purchase removed; stock, linked finance entry, and attached receipt were reversed.');
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
