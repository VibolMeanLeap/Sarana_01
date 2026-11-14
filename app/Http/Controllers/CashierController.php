<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
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
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:paid,unpaid'],
            'period' => ['nullable', 'in:today,week,month'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

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

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($builder) use ($search) {
                $builder->where('order_id', 'like', "%{$search}%")
                    ->orWhere('payment_status', 'like', "%{$search}%");
            });
        }

        if (! empty($validated['status'])) {
            $query->where('is_paid', $validated['status'] === 'paid');
        }

        if (! empty($validated['period'])) {
            $periodStart = match ($validated['period']) {
                'today' => today()->toDateString(),
                'week' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'month' => now()->startOfMonth()->toDateString(),
            };
            $query->whereDate('order_date', '>=', $periodStart)
                ->whereDate('order_date', '<=', today()->toDateString());
        } else {
            if (! empty($validated['start_date'])) {
                $query->whereDate('order_date', '>=', $validated['start_date']);
            }

            if (! empty($validated['end_date'])) {
                $query->whereDate('order_date', '<=', $validated['end_date']);
            }
        }

        $orders = $query->orderByDesc('order_date')->paginate(12)->withQueryString();

        return view('cashier.orders', compact('orders'));
    }
}
