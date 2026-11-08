<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'in:10,15,25,50,100'],
        ]);

        $query = Review::with(['order', 'customer'])
            ->when(!empty($validated['rating']), fn ($q) => $q->where('rating', $validated['rating']))
            ->when(!empty($validated['search']), function ($q) use ($validated) {
                $term = trim($validated['search']);
                $q->where(function ($nested) use ($term) {
                    $nested->where('comment', 'like', "%{$term}%")
                        ->orWhere('order_id', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%"));
                });
            })
            ->when(!empty($validated['start_date']), fn ($q) => $q->whereHas('order', fn ($order) => $order->whereDate('order_date', '>=', $validated['start_date'])) )
            ->when(!empty($validated['end_date']), fn ($q) => $q->whereHas('order', fn ($order) => $order->whereDate('order_date', '<=', $validated['end_date'])) );

        $statsQuery = clone $query;
        $stats = [
            'count' => (clone $statsQuery)->count(),
            'average' => round((float) ((clone $statsQuery)->avg('rating') ?? 0), 1),
            'five_star' => (clone $statsQuery)->where('rating', 5)->count(),
            'low_ratings' => (clone $statsQuery)->where('rating', '<=', 2)->count(),
        ];
        $distribution = [];
        for ($rating = 5; $rating >= 1; $rating--) {
            $distribution[$rating] = (clone $statsQuery)->where('rating', $rating)->count();
        }

        $direction = $validated['sort_dir'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 15);
        $reviews = $query
            ->orderBy(Order::query()->select('order_date')->whereColumn('orders.order_id', 'reviews.order_id'), $direction)
            ->orderBy('review_id', $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.review', compact('reviews', 'stats', 'distribution'));
    }

    public function create()
    {
        $orders = Order::with('customer')
            ->where('is_paid', true)
            ->whereNotNull('customer_id')
            ->where('order_status', '!=', 'cancelled')
            ->whereDoesntHave('review')
            ->orderByDesc('order_date')
            ->get();

        return view('admin.reviews.create', compact('orders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,order_id', 'unique:reviews,order_id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = Order::where('order_id', $validated['order_id'])
            ->where('is_paid', true)
            ->whereNotNull('customer_id')
            ->where('order_status', '!=', 'cancelled')
            ->first();

        if (! $order) {
            throw ValidationException::withMessages(['order_id' => 'Only paid orders linked to a customer can receive a review.']);
        }

        Review::create([
            'order_id' => $order->order_id,
            'customer_id' => $order->customer_id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return redirect()->route('reviews.index')->with('success', 'Customer feedback recorded successfully.');
    }

    public function show(Review $review)
    {
        $review->load(['order', 'customer']);
        return view('admin.reviews.show', compact('review'));
    }

    public function edit(Review $review)
    {
        $review->load('customer');
        return view('admin.reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        // Keep customer/order ownership immutable; an administrator can moderate the content only.
        $review->update($validated);
        return redirect()->route('reviews.index')->with('success', 'Review updated successfully.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return redirect()->route('reviews.index')->with('success', 'Review removed.');
    }
}
