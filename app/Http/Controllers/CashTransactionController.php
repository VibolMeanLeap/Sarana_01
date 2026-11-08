<?php

namespace App\Http\Controllers;

use App\Models\AccountingCategory;
use App\Models\CashTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashTransactionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'category_id' => ['nullable', 'integer', 'exists:accounting_category,id'],
            'type' => ['nullable', 'in:income,expense'],
            'search' => ['nullable', 'string', 'max:120'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:15,25,50,100'],
        ]);

        $fromDate = $filters['from'] ?? null;
        $toDate = $filters['to'] ?? null;

        // Sales income comes only from successfully paid orders. It is deliberately
        // separate from manual cash transactions so sales are never counted twice.
        $paidOrders = Order::query()->where('is_paid', 1);
        if ($fromDate) $paidOrders->whereDate('order_date', '>=', $fromDate);
        if ($toDate) $paidOrders->whereDate('order_date', '<=', $toDate);
        $totalSales = (float) (clone $paidOrders)->sum(DB::raw('COALESCE(final_price, total_amount, 0)'));

        $cogsQuery = DB::table('orders')
            ->join('order_items', 'orders.order_id', '=', 'order_items.order_id')
            ->leftJoin('product', 'order_items.product_id', '=', 'product.product_id')
            ->where('orders.is_paid', 1);
        if ($fromDate) $cogsQuery->whereDate('orders.order_date', '>=', $fromDate);
        if ($toDate) $cogsQuery->whereDate('orders.order_date', '<=', $toDate);
        $totalCogs = (float) $cogsQuery->sum(DB::raw('COALESCE(order_items.unit_cost, product.cost, 0) * COALESCE(order_items.quantity, 0)'));

        // Summary calculations use the date range only. Category/type/search filters
        // below affect the ledger, not the headline financial comparison.
        $datedTransactions = CashTransaction::query()->where(function ($query) {
            $query->whereHas('category', fn ($category) => $category->whereIn('id_type', [1, 2]))
                ->orWhereNull('category_id');
        });
        if ($fromDate) $datedTransactions->whereDate('transaction_date', '>=', $fromDate);
        if ($toDate) $datedTransactions->whereDate('transaction_date', '<=', $toDate);

        $otherIncome = (float) (clone $datedTransactions)
            ->whereNull('purchase_id')->whereNull('order_id')
            ->whereHas('category', fn ($query) => $query->where('id_type', 1))
            ->sum('amount');

        $inventoryPurchases = (float) (clone $datedTransactions)
            ->whereNotNull('purchase_id')
            ->whereHas('category', fn ($query) => $query->where('id_type', 2))
            ->sum('amount');

        $totalExpense = (float) (clone $datedTransactions)
            ->whereNull('purchase_id')
            ->whereHas('category', fn ($query) => $query->where('id_type', 2))
            ->sum('amount');

        $grossProfit = $totalSales - $totalCogs;
        // Purchase cash outflows are inventory assets. They are shown in the cash
        // comparison, but not deducted again from profit; sold inventory is already
        // recognized through COGS.
        $netProfit = $grossProfit + $otherIncome - $totalExpense;
        $cashInflow = $totalSales + $otherIncome;
        $cashOutflow = $inventoryPurchases + $totalExpense;
        $netCashFlow = $cashInflow - $cashOutflow;

        $ledgerQuery = CashTransaction::with(['category', 'user', 'purchase'])
            ->when($fromDate, fn ($query) => $query->whereDate('transaction_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('transaction_date', '<=', $toDate));

        if (! empty($filters['category_id'])) {
            $ledgerQuery->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['type'])) {
            $typeId = $filters['type'] === 'income' ? 1 : 2;
            $ledgerQuery->whereHas('category', fn ($query) => $query->where('id_type', $typeId));
        }
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $ledgerQuery->where(fn ($query) => $query->where('note', 'like', "%{$term}%")
                ->orWhere('id', 'like', "%{$term}%")
                ->orWhere('purchase_id', 'like', "%{$term}%"));
        }

        $sortDirection = $filters['sort_dir'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 15);
        $transactions = $ledgerQuery->orderBy('transaction_date', $sortDirection)
            ->orderByDesc('id')->paginate($perPage)->withQueryString();

        $categories = AccountingCategory::with('type')->orderBy('name')->get();
        $summary = [
            'sales' => $totalSales,
            'other_income' => $otherIncome,
            'inventory_purchases' => $inventoryPurchases,
            'operating_expenses' => $totalExpense,
            'cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'cash_inflow' => $cashInflow,
            'cash_outflow' => $cashOutflow,
            'net_cash_flow' => $netCashFlow,
        ];

        return view('admin.expense_income', compact('summary', 'transactions', 'categories', 'perPage', 'fromDate', 'toDate'));
    }

    public function create()
    {
        $categories = AccountingCategory::with('type')->orderBy('name')->get();
        return view('admin.expense_income_create', compact('categories'));
    }

    public function edit($id)
    {
        $transaction = CashTransaction::findOrFail($id);
        if ($transaction->purchase_id !== null) {
            return redirect()->route('expense_income.index')->with('error', 'Purchase-linked expense records are managed from the Purchases tab.');
        }
        $categories = AccountingCategory::with('type')->orderBy('name')->get();
        return view('admin.expense_income_edit', compact('transaction', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:accounting_category,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'note' => 'nullable|string|max:2000',
        ]);

        $category = AccountingCategory::findOrFail($validated['category_id']);
        if ((int) $category->id_type === 2 && $request->filled('purchase_id')) {
            return back()->withInput()->withErrors(['purchase_id' => 'Purchase expenses must be created through the Purchases module.']);
        }

        $validated['user_id'] = auth()->id();
        CashTransaction::create($validated);
        return redirect()->route('expense_income.index')->with('success', 'Transaction recorded successfully.');
    }

    public function update(Request $request, $id)
    {
        $transaction = CashTransaction::findOrFail($id);
        if ($transaction->purchase_id !== null) {
            return redirect()->route('expense_income.index')->with('error', 'Purchase-linked expense records are managed from the Purchases tab.');
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:accounting_category,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'note' => 'nullable|string|max:2000',
        ]);
        $transaction->update($validated);
        return redirect()->route('expense_income.index')->with('success', 'Transaction updated successfully.');
    }

    public function destroy($id)
    {
        $transaction = CashTransaction::findOrFail($id);
        if ($transaction->purchase_id !== null) {
            return redirect()->route('expense_income.index')->with('error', 'Purchase-linked expense records cannot be deleted here. Manage the purchase from the Purchases tab.');
        }
        $transaction->delete();
        return redirect()->route('expense_income.index')->with('success', 'Transaction deleted successfully.');
    }
}
