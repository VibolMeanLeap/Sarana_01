<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\StoreContactMessage;
use Illuminate\Support\Facades\Schema;

/**
 * Small, query-backed action counters for the administrator navigation.
 * Counts represent work that still needs attention, not totals of all records.
 */
class AdminActionCountService
{
    public function get(): array
    {
        $counts = [
            'orders' => 0,
            'delivery' => 0,
            'inventory' => 0,
            'reviews' => 0,
            'enquiries' => 0,
        ];

        if (Schema::hasTable('orders')) {
            // Pending includes COD and online orders awaiting payment verification.
            // The Orders badge is a queue indicator: it clears when staff confirms the order.
            $counts['orders'] = (int) Order::query()
                ->where('order_status', 'pending')
                ->count();
        }

        if (Schema::hasTable('delivery')) {
            $counts['delivery'] = (int) Delivery::query()
                ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery'])
                ->count();
        }

        if (Schema::hasTable('product')) {
            $counts['inventory'] = (int) Product::query()
                ->where('status', 'Active')
                ->where('qty', '<=', 0)
                ->whereRaw("LOWER(TRIM(COALESCE(product_type, 'stock'))) NOT IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')")
                ->count();
        }

        if (Schema::hasTable('reviews')) {
            $counts['reviews'] = (int) Review::query()
                ->where('is_approved', false)
                ->count();
        }

        if (Schema::hasTable('store_contact_messages')) {
            $enquiries = StoreContactMessage::query()
                ->where('status', '<>', 'resolved')
                ->where(function ($query) {
                    $query->whereNull('read_at');
                    if (Schema::hasTable('store_contact_replies')) {
                        $query->orWhereHas('replies', function ($replyQuery) {
                            $replyQuery->where('sender_type', 'customer')
                                ->whereNull('read_at');
                        });
                    }
                });

            $counts['enquiries'] = (int) $enquiries->count();
        }

        return $counts;
    }

    public function latestWebsiteOrderId(): int
    {
        if (! Schema::hasTable('orders')) {
            return 0;
        }

        return (int) (Order::query()->where('order_source', 'website')->max('order_id') ?? 0);
    }
}
