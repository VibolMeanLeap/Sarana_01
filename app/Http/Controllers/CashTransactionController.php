<?php

namespace App\Http\Controllers;

use App\Models\AccountingCategory;
use App\Models\CashTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\AdminDateFilter;
use Illuminate\Validation\ValidationException;

class CashTransactionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:accounting_category,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:15,25,50,100'],
        ]);

        $dateFilter = AdminDateFilter::resolve($request, 'from', 'to');
        $fromDate = $dateFilter['from'];
        $toDate = $dateFilter['to'];

        // Revenue is calculated from verified paid orders only.
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

        $datedExpenses = CashTransaction::query()
            ->whereHas('category', fn ($category) => $category->where('id_type', 2))
            ->when($fromDate, fn ($query) => $query->whereDate('transaction_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('transaction_date', '<=', $toDate));

        // Purchase-linked expenses are cash outflows for inventory. Manual expenses
        // are operating costs. Inventory cost is recognized in profit through COGS.
        $inventoryPurchases = (float) (clone $datedExpenses)
            ->whereNotNull('purchase_id')
            ->sum('amount');

        $operatingExpenses = (float) (clone $datedExpenses)
            ->whereNull('purchase_id')
            ->sum('amount');

        // Total expenses is the full expense ledger for the selected dates:
        // supplier purchases plus manual operating expenses. Net profit still
        // subtracts COGS and operating expenses only to avoid double-counting
        // inventory purchases before the stock is sold.
        $totalExpenses = $inventoryPurchases + $operatingExpenses;

        $grossProfit = $totalSales - $totalCogs;
        $netProfit = $grossProfit - $operatingExpenses;
        $cashInflow = $totalSales;
        $cashOutflow = $totalExpenses;
        $netCashFlow = $cashInflow - $cashOutflow;

        $ledgerQuery = CashTransaction::with(['category', 'user', 'purchase'])
            ->whereHas('category', fn ($category) => $category->where('id_type', 2))
            ->when($fromDate, fn ($query) => $query->whereDate('transaction_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('transaction_date', '<=', $toDate));

        if (! empty($filters['category_id'])) {
            $ledgerQuery->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $ledgerQuery->where(fn ($query) => $query->where('note', 'like', "%{$term}%")
                ->orWhere('id', 'like', "%{$term}%")
                ->orWhere('purchase_id', 'like', "%{$term}%"));
        }

        // Sum every matching ledger row before pagination, so the displayed
        // total represents the full filtered result set rather than one page.
        $filteredExpenseTotal = (float) (clone $ledgerQuery)->sum('amount');

        $sortDirection = $filters['sort_dir'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 15);
        $transactions = $ledgerQuery->orderBy('transaction_date', $sortDirection)
            ->orderByDesc('id')->paginate($perPage)->withQueryString();

        $categories = AccountingCategory::with('type')->where('id_type', 2)->orderBy('name')->get();
        $summary = [
            'sales' => $totalSales,
            'inventory_purchases' => $inventoryPurchases,
            'operating_expenses' => $operatingExpenses,
            'total_expenses' => $totalExpenses,
            'cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'cash_inflow' => $cashInflow,
            'cash_outflow' => $cashOutflow,
            'net_cash_flow' => $netCashFlow,
        ];

        return view('admin.expense_income', compact('summary', 'transactions', 'categories', 'perPage', 'fromDate', 'toDate', 'dateFilter', 'filteredExpenseTotal'));
    }

    public function create()
    {
        $categories = AccountingCategory::with('type')->where('id_type', 2)->orderBy('name')->get();
        return view('admin.expense_income_create', compact('categories'));
    }

    public function edit($id)
    {
        $transaction = CashTransaction::findOrFail($id);
        if ($transaction->purchase_id !== null) {
            return redirect()->route('expense_income.index')->with('error', 'Purchase-linked expense records are managed from the Purchases tab.');
        }
        if ((int) $transaction->category?->id_type !== 2) {
            return redirect()->route('expense_income.index')->with('error', 'Only expense transactions can be edited here.');
        }
        $categories = AccountingCategory::with('type')->where('id_type', 2)->orderBy('name')->get();
        return view('admin.expense_income_edit', compact('transaction', 'categories'));
    }

    public function store(Request $request)
    {
        $request->merge(['note' => is_string($request->input('note')) ? trim($request->input('note')) : $request->input('note')]);
        $validated = $request->validate([
            'category_id' => 'required|exists:accounting_category,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'note' => 'required|string|min:15|max:1000',
        ], [
            'note.required' => 'Enter the reason for this expense.',
            'note.min' => 'Please provide a clear expense reason of at least 15 characters.',
        ]);

        $category = AccountingCategory::findOrFail($validated['category_id']);
        if ((int) $category->id_type !== 2) {
            throw ValidationException::withMessages(['category_id' => 'Only expense categories can be recorded here. Sales income is calculated automatically from paid orders.']);
        }

        $validated['user_id'] = auth()->id();
        CashTransaction::create($validated);
        return redirect()->route('expense_income.index')->with('success', 'Expense recorded successfully.');
    }

    public function update(Request $request, $id)
    {
        $transaction = CashTransaction::findOrFail($id);
        if ($transaction->purchase_id !== null) {
            return redirect()->route('expense_income.index')->with('error', 'Purchase-linked expense records are managed from the Purchases tab.');
        }
        if ((int) $transaction->category?->id_type !== 2) {
            return redirect()->route('expense_income.index')->with('error', 'Historical income records are read-only and are not part of the expense ledger.');
        }

        $request->merge(['note' => is_string($request->input('note')) ? trim($request->input('note')) : $request->input('note')]);
        $validated = $request->validate([
            'category_id' => 'required|exists:accounting_category,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'note' => 'required|string|min:15|max:1000',
        ], [
            'note.required' => 'Enter the reason for this expense.',
            'note.min' => 'Please provide a clear expense reason of at least 15 characters.',
        ]);
        $category = AccountingCategory::findOrFail($validated['category_id']);
        if ((int) $category->id_type !== 2) {
            throw ValidationException::withMessages(['category_id' => 'Only expense categories can be selected.']);
        }

        $transaction->update($validated);
        return redirect()->route('expense_income.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy($id)
    {
        $transaction = CashTransaction::findOrFail($id);
        if ($transaction->purchase_id !== null) {
            return redirect()->route('expense_income.index')->with('error', 'Purchase-linked expense records cannot be deleted here. Manage the purchase from the Purchases tab.');
        }
        if ((int) $transaction->category?->id_type !== 2) {
            return redirect()->route('expense_income.index')->with('error', 'Only expense transactions can be managed here.');
        }
        $transaction->delete();
        return redirect()->route('expense_income.index')->with('success', 'Expense deleted successfully.');
    }
}
