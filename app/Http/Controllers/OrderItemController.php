<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderItemController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $orderId = $request->input('order_id');
        $productId = $request->input('product_id');
        $categoryId = $request->input('category_id');
        $sortBy = in_array($request->input('sort_by'), ['order_date', 'order_item_id', 'quantity', 'price'], true) ? $request->input('sort_by') : 'order_date';
        $sortDirection = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $orderItems = OrderItem::query()
            ->select('order_items.*')
            ->leftJoin('orders as filter_orders', 'filter_orders.order_id', '=', 'order_items.order_id')
            ->with(['order', 'product'])
            ->when($orderId !== null && $orderId !== '', fn ($query) => $query->where('order_items.order_id', $orderId))
            ->when($productId !== null && $productId !== '', fn ($query) => $query->where('order_items.product_id', $productId))
            ->when($categoryId !== null && $categoryId !== '', fn ($query) => $query->whereHas('product', fn ($productQuery) => $productQuery->where('category_id', $categoryId)))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('order_items.order_id', 'like', "%{$search}%")
                        ->orWhere('order_items.product_id', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('product_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('start_date'), fn ($query) => $query->whereDate('filter_orders.order_date', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn ($query) => $query->whereDate('filter_orders.order_date', '<=', $request->input('end_date')))
            ->orderBy($sortBy === 'order_date' ? 'filter_orders.order_date' : 'order_items.'.$sortBy, $sortDirection)
            ->orderByDesc('order_items.order_item_id')
            ->paginate(15)
            ->withQueryString();

        $products = Product::orderBy('product_name')->get();
        $categories = \App\Models\Category::orderBy('category_name')->get();

        return view('admin.older_item', compact('orderItems', 'orderId', 'products', 'categories'));
    }

    // Order items are created by POS or website checkout. Manual creation is disabled
    // so administrators cannot bypass order pricing, payment and stock workflows.

    public function edit($id)
    {
        $orderItem = OrderItem::with('order')->findOrFail($id);
        if (! $orderItem->order || $this->orderIsLocked($orderItem->order)) {
            return redirect()->route('admin.older_items')->with('error', 'Items belonging to paid, cancelled, or payment-attempt orders are locked.');
        }

        $orders = Order::orderByDesc('order_id')->get();
        $products = Product::orderBy('product_name')->get();
        return view('admin.older_item.edit', compact('orderItem', 'orders', 'products'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,order_id',
            'product_id' => 'required|exists:product,product_id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        try {
            $result = DB::transaction(function () use ($validated, $id) {
                $item = OrderItem::where('order_item_id', $id)->lockForUpdate()->firstOrFail();
                $previousOrderId = (int) $item->order_id;
                $nextOrderId = (int) $validated['order_id'];

                $orderIds = array_values(array_unique([$previousOrderId, $nextOrderId]));
                sort($orderIds);
                $orders = Order::whereIn('order_id', $orderIds)
                    ->orderBy('order_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('order_id');

                $oldOrder = $orders->get($previousOrderId);
                $newOrder = $orders->get($nextOrderId);
                if (! $oldOrder || ! $newOrder || $this->orderIsLocked($oldOrder) || $this->orderIsLocked($newOrder)) {
                    return 'locked';
                }

                $productIds = array_values(array_unique([(int) $item->product_id, (int) $validated['product_id']]));
                sort($productIds);
                $products = Product::whereIn('product_id', $productIds)
                    ->orderBy('product_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');

                $oldProduct = $products->get((int) $item->product_id);
                $newProduct = $products->get((int) $validated['product_id']);
                if (! $oldProduct || ! $newProduct) {
                    return 'product';
                }

                $oldTracksStock = $oldProduct->isStockTracked();
                $newTracksStock = $newProduct->isStockTracked();
                $availableForReplacement = $newTracksStock ? (int) $newProduct->qty : PHP_INT_MAX;
                if ($newTracksStock && $oldTracksStock && (int) $oldProduct->product_id === (int) $newProduct->product_id) {
                    $availableForReplacement += (int) $item->quantity;
                }
                if ($newTracksStock && (int) $validated['quantity'] > $availableForReplacement) {
                    return 'stock';
                }

                if ($oldTracksStock) {
                    $oldProduct->qty = (int) $oldProduct->qty + (int) $item->quantity;
                    $oldProduct->save();
                    StockLog::create([
                        'product_id' => $oldProduct->product_id,
                        'user_id' => Auth::id(),
                        'type' => 'IN',
                        'quantity' => (int) $item->quantity,
                        'reason' => 'Sale correction',
                        'note' => "Stock returned while editing order item #{$item->order_item_id} (order #{$item->order_id}).",
                    ]);
                }
                if ($newTracksStock) {
                    $newProduct->qty = (int) $newProduct->qty - (int) $validated['quantity'];
                    $newProduct->save();
                    StockLog::create([
                        'product_id' => $newProduct->product_id,
                        'user_id' => Auth::id(),
                        'type' => 'OUT',
                        'quantity' => (int) $validated['quantity'],
                        'reason' => 'Sale correction',
                        'note' => "Stock issued for updated order item #{$item->order_item_id} (order #{$nextOrderId}).",
                    ]);
                }

                $unitCost = (int) $oldProduct->product_id === (int) $newProduct->product_id
                    ? (float) $item->unit_cost
                    : (float) $newProduct->cost;

                $item->update([
                    ...$validated,
                    'unit_cost' => $unitCost,
                ]);
                $this->syncOrderTotal($previousOrderId);
                if ($nextOrderId !== $previousOrderId) {
                    $this->syncOrderTotal($nextOrderId);
                }
                return 'ok';
            }, 3);
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'The order item could not be updated. No stock changes were saved.');
        }

        if ($result !== 'ok') {
            $message = match ($result) {
                'locked' => 'This item belongs to a paid, cancelled, or payment-attempt order and cannot be changed.',
                'stock' => 'There is not enough stock for the replacement quantity.',
                default => 'The selected product is no longer available.',
            };
            return back()->withInput()->with('error', $message);
        }

        return redirect()->route('admin.older_items')->with('success', 'Order item and inventory were updated.');
    }

    public function destroy($id)
    {
        try {
            $result = DB::transaction(function () use ($id) {
                $item = OrderItem::where('order_item_id', $id)->lockForUpdate()->firstOrFail();
                $order = Order::where('order_id', $item->order_id)->lockForUpdate()->first();
                if (! $order || $this->orderIsLocked($order)) {
                    return 'locked';
                }

                $product = Product::where('product_id', $item->product_id)->lockForUpdate()->first();
                if ($product && $product->isStockTracked()) {
                    $product->qty = (int) $product->qty + (int) $item->quantity;
                    $product->save();
                    StockLog::create([
                        'product_id' => $product->product_id,
                        'user_id' => Auth::id(),
                        'type' => 'IN',
                        'quantity' => (int) $item->quantity,
                        'reason' => 'Sale reversal',
                        'note' => "Stock returned after removing order item #{$item->order_item_id} (order #{$item->order_id}).",
                    ]);
                }

                $orderId = (int) $item->order_id;
                $item->delete();
                $this->syncOrderTotal($orderId);
                return 'ok';
            }, 3);
        } catch (Throwable $exception) {
            return back()->with('error', 'The order item could not be removed. No stock changes were saved.');
        }

        if ($result === 'locked') {
            return back()->with('error', 'Items belonging to paid, cancelled, or payment-attempt orders cannot be removed.');
        }

        return redirect()->route('admin.older_items')->with('success', 'Order item removed and stock returned.');
    }

    private function orderIsLocked(Order $order): bool
    {
        return (bool) $order->is_paid
            || $order->order_status === 'cancelled'
            || Payment::where('order_id', $order->order_id)->exists();
    }

    private function syncOrderTotal(int $orderId): void
    {
        $order = Order::where('order_id', $orderId)->lockForUpdate()->first();
        if (! $order) {
            return;
        }

        $lineTotal = (float) OrderItem::where('order_id', $orderId)
            ->selectRaw('COALESCE(SUM(quantity * price), 0) AS line_total')
            ->value('line_total');
        $order->total_amount = round($lineTotal, 2);
        $order->save();
    }
}
