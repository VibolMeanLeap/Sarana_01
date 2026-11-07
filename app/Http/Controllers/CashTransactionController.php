<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order; 
use App\Models\CashTransaction;
use App\Models\AccountingCategory;

class CashTransactionController extends Controller
{
    // ==========================================
    // 1. បង្ហាញបញ្ជី Expense/Income និង Summary Cards
    // ==========================================
    public function index(Request $request)
    {
        $fromDate   = $request->input('from');
        $toDate     = $request->input('to');
        $categoryId = $request->input('category_id');

        // 1. គណនា Total Sales ពី POS (ចាត់ទុកតែ Order ដែល បង់ប្រាក់រួច filter is_paid = 1)
        $salesQuery = Order::where('is_paid', 1);
        
        if ($fromDate) $salesQuery->whereDate('order_date', '>=', $fromDate);
        if ($toDate)   $salesQuery->whereDate('order_date', '<=', $toDate);

        $totalSales = $salesQuery->sum(DB::raw('COALESCE(final_price, total_amount, 0)')); 

        // 2. គណនា Cost of Goods Sold (COGS / ដើមទុន)
        $cogsQuery = Order::where('orders.is_paid', 1)
            ->join('order_items', 'orders.order_id', '=', 'order_items.order_id')
            ->leftJoin('product', 'order_items.product_id', '=', 'product.product_id');

        if ($fromDate) $cogsQuery->whereDate('orders.order_date', '>=', $fromDate);
        if ($toDate)   $cogsQuery->whereDate('orders.order_date', '<=', $toDate);

        $totalCogs = $cogsQuery->sum(DB::raw('COALESCE(order_items.unit_cost, product.cost, 0) * order_items.quantity')); 

        // 3. គណនា Gross Profit (ចំណេញដុល)
        $grossProfit = $totalSales - $totalCogs;

        // 4. ទាញទិន្នន័យ Expense & Other Income (ពី cash_transactions)
        $transQuery = CashTransaction::with(['category', 'user']);

        if ($fromDate)   $transQuery->whereDate('transaction_date', '>=', $fromDate);
        if ($toDate)     $transQuery->whereDate('transaction_date', '<=', $toDate);
        if ($categoryId) $transQuery->where('category_id', $categoryId);

        $transactions = (clone $transQuery)->orderBy('transaction_date', 'desc')->paginate(15);

        // Inventory purchases are cash outflows, but their cost is recognized in COGS
        // when stock is sold. Exclude purchase-linked rows from operating expenses
        // so the same cost is not deducted twice in the net-profit calculation.
        $inventoryPurchases = (clone $transQuery)->whereNotNull('purchase_id')
            ->whereHas('category', fn ($q) => $q->where('id_type', 2))
            ->sum('amount');

        $totalExpense = (clone $transQuery)->whereNull('purchase_id')
            ->whereHas('category', fn ($q) => $q->where('id_type', 2))
            ->sum('amount');

        // គណនាសរុបចំណូលផ្សេងៗ (Other Income: id_type = 1)
        $otherIncome = (clone $transQuery)->whereHas('category', function($q) {
            $q->where('id_type', 1);
        })->sum('amount');

        // 5. គណនា Net Profit / Loss (ចំណេញ/ខាតសុទ្ធ)
        $netProfit = $grossProfit + $otherIncome - $totalExpense;

        $categories = AccountingCategory::all();

        return view('admin.expense_income', compact(
            'totalSales',
            'totalCogs',
            'grossProfit',
            'otherIncome',
            'totalExpense',
            'inventoryPurchases',
            'netProfit',
            'transactions',
            'categories'
        ));
    }

    public function create()
    {
        $categories = AccountingCategory::with('type')->orderBy('name')->get();

        return view('admin.expense_income_create', compact('categories'));
    }

    public function edit($id)
    {
        $transaction = CashTransaction::findOrFail($id);
        $categories = AccountingCategory::with('type')->orderBy('name')->get();

        return view('admin.expense_income_edit', compact('transaction', 'categories'));
    }

    // ==========================================
    // 2. រក្សាទុក Transaction ថ្មី (New Transaction)
    // ==========================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'      => 'required|exists:accounting_category,id',
            'amount'           => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'note'             => 'nullable|string|max:2000',
        ]);

        $validated['user_id'] = auth()->id();
        CashTransaction::create($validated);

        return redirect()->route('expense_income.index')->with('success', 'Transaction recorded successfully.');
    }

    // ==========================================
    // 3. កែប្រែ Transaction 
    // ==========================================
    public function update(Request $request, $id)
    {
        $transaction = CashTransaction::findOrFail($id);

        $validated = $request->validate([
            'category_id'      => 'required|exists:accounting_category,id',
            'amount'           => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'note'             => 'nullable|string|max:2000',
        ]);

        $transaction->update($validated);

        return redirect()->back()->with('success', 'Transaction updated successfully.');
    }

    // ==========================================
    // 4. លុប Transaction ចោល
    // ==========================================
    public function destroy($id)
    {
        $transaction = CashTransaction::findOrFail($id);
        $transaction->delete();

        return redirect()->back()->with('success', 'Transaction deleted successfully.');
    }
}