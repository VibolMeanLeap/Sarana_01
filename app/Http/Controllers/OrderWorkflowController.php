<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OrderWorkflowController extends Controller
{
    public function updateByCashier(Request $request, int $orderId)
    {
        return $this->updateStatus($request, $orderId, false);
    }

    public function updateByAdmin(Request $request, int $orderId)
    {
        return $this->updateStatus($request, $orderId, true);
    }

    private function updateStatus(Request $request, int $orderId, bool $isAdmin)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:confirmed,preparing,ready,completed,cancelled',
        ]);

        $targetStatus = $validated['order_status'];

        try {
            DB::transaction(function () use ($orderId, $targetStatus, $isAdmin): void {
                $order = Order::query()
                    ->where('order_id', $orderId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $isAdmin) {
                    $isWebsiteOrder = ($order->order_source ?? '') === 'website';
                    $isOwnPosOrder = ($order->order_source ?? 'pos') === 'pos'
                        && (int) $order->cashier_id === (int) auth()->id();

                    abort_unless($isWebsiteOrder || $isOwnPosOrder, 403);
                }

                $currentStatus = (string) ($order->order_status ?: 'pending');

                if ($currentStatus === 'cancelled') {
                    throw ValidationException::withMessages([
                        'order_status' => 'A cancelled order cannot be changed again.',
                    ]);
                }

                if ($targetStatus === 'cancelled') {
                    if ((bool) $order->is_paid) {
                        throw ValidationException::withMessages([
                            'order_status' => 'A paid order cannot be cancelled here. Record a refund or adjustment first.',
                        ]);
                    }

                    $items = OrderItem::query()
                        ->where('order_id', $order->order_id)
                        ->get(['product_id', 'quantity']);

                    $productIds = $items->pluck('product_id')->filter()->unique()->sort()->values();
                    $products = Product::query()
                        ->whereIn('product_id', $productIds)
                        ->orderBy('product_id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('product_id');

                    $quantityByProduct = $items->groupBy('product_id')->map(
                        fn ($rows) => (int) $rows->sum('quantity')
                    );

                    foreach ($quantityByProduct as $productId => $quantity) {
                        $product = $products->get((int) $productId);
                        if (! $product) {
                            throw new RuntimeException("Cannot restore stock: product #{$productId} no longer exists.");
                        }

                        $product->qty = (int) $product->qty + $quantity;
                        $product->save();

                        StockLog::create([
                            'product_id' => $product->product_id,
                            'user_id' => auth()->id(),
                            'type' => 'IN',
                            'quantity' => $quantity,
                            'reason' => 'Order cancellation',
                            'note' => 'Stock returned for cancelled order #'.$order->order_id.'.',
                        ]);
                    }

                    Payment::query()
                        ->where('order_id', $order->order_id)
                        ->where('status', 'pending')
                        ->update(['status' => 'cancelled']);

                    $order->order_status = 'cancelled';
                    $order->save();
                    return;
                }

                if (! $order->is_paid) {
                    throw ValidationException::withMessages([
                        'order_status' => 'Payment must be verified before an order can be processed.',
                    ]);
                }

                $sequence = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];
                $currentIndex = array_search($currentStatus, $sequence, true);
                $targetIndex = array_search($targetStatus, $sequence, true);

                if ($currentIndex === false || $targetIndex === false || $targetIndex !== $currentIndex + 1) {
                    throw ValidationException::withMessages([
                        'order_status' => 'Invalid status transition. Move the order forward one step at a time.',
                    ]);
                }

                $order->order_status = $targetStatus;
                $order->save();
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $destination = $isAdmin ? 'admin.orders.index' : 'cashier.orders';
        return redirect()->route($destination)->with('success', 'Order #'.$orderId.' status updated to '.str_replace('_', ' ', $targetStatus).'.');
    }
}
