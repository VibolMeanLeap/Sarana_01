<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function dashboard()
    {
        $cashierId = auth()->id();
        $todayOrders = Order::where('cashier_id', $cashierId)->whereDate('order_date', today());

        $summary = [
            'today_orders' => (clone $todayOrders)->count(),
            'paid_orders' => (clone $todayOrders)->where('is_paid', true)->count(),
            'pending_orders' => (clone $todayOrders)->where('is_paid', false)->count(),
            'today_revenue' => (float) (clone $todayOrders)->where('is_paid', true)
                ->sum(DB::raw('COALESCE(final_price, total_amount, 0)')),
        ];

        $recentOrders = Order::with(['customer', 'orderType', 'paymentMethod', 'orderItems'])
            ->where('cashier_id', $cashierId)
            ->orderByDesc('order_date')
            ->limit(8)
            ->get();

        return view('cashier.dashboard', compact('summary', 'recentOrders'));
    }

    public function orders(Request $request)
    {
        $cashierId = (int) auth()->id();
        $query = Order::with(['customer', 'orderType', 'paymentMethod', 'orderItems'])
            ->withCount('delivery')
            ->where(function ($builder) use ($cashierId) {
                $builder->where('order_source', 'website')
                    ->orWhere(function ($pos) use ($cashierId) {
                        $pos->where('cashier_id', $cashierId)
                            ->where(function ($source) {
                                $source->where('order_source', 'pos')->orWhereNull('order_source');
                            });
                    });
            });

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('order_id', 'like', "%{$search}%")
                    ->orWhere('payment_status', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_paid', $request->input('status') === 'paid');
        }

        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->input('end_date'));
        }

        $orders = $query->orderByDesc('order_date')->paginate(12)->withQueryString();

        return view('cashier.orders', compact('orders'));
    }
}
