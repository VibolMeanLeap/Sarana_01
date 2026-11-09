<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AdminActionCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOperationsController extends Controller
{
    /** Return live sidebar action counts and newly-created website orders. */
    public function updates(Request $request, AdminActionCountService $actionCounts): JsonResponse
    {
        $validated = $request->validate([
            'since_order_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $sinceOrderId = (int) ($validated['since_order_id'] ?? 0);
        $newOrders = Order::query()
            ->with('customer')
            ->where('order_source', 'website')
            ->where('order_id', '>', $sinceOrderId)
            ->orderBy('order_id')
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'order_id' => (int) $order->order_id,
                'customer' => (string) ($order->customer?->name ?: 'Website customer'),
                'total' => (float) ($order->final_price ?? $order->total_amount ?? 0),
                'payment_status' => (string) ($order->payment_status ?: ($order->is_paid ? 'Paid' : 'Unpaid')),
                'order_status' => (string) ($order->order_status ?: 'pending'),
            ])
            ->values();

        // Advance the browser cursor only to an order actually returned by this
        // query. Do not jump to MAX(order_id) on an empty result: a new order can
        // be committed between the list query and MAX(), and skipping ahead here
        // would hide that order from the next poll.
        $lastReturnedId = (int) ($newOrders->last()['order_id'] ?? $sinceOrderId);
        $latestWebsiteOrderId = $actionCounts->latestWebsiteOrderId();

        return response()->json([
            'success' => true,
            'counts' => $actionCounts->get(),
            'new_orders' => $newOrders,
            // Advance by the last batch item when another batch may remain.
            'next_since_order_id' => $lastReturnedId,
            'latest_website_order_id' => $latestWebsiteOrderId,
            'checked_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
