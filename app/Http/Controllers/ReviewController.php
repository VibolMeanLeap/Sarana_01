<?php

namespace App\Http\Controllers;

use App\Models\Costumer;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'visibility' => ['nullable', 'in:pending,published'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'in:10,15,25,50,100'],
        ]);

        $query = Review::with(['order', 'customer'])
            ->when(!empty($validated['rating']), fn ($q) => $q->where('rating', $validated['rating']))
            ->when(($validated['visibility'] ?? null) === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when(($validated['visibility'] ?? null) === 'published', fn ($q) => $q->where('is_approved', true))
            ->when(!empty($validated['search']), function ($q) use ($validated) {
                $term = trim($validated['search']);
                $q->where(function ($nested) use ($term) {
                    $nested->where('comment', 'like', "%{$term}%")
                        ->orWhere('order_id', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%"));
                });
            })
            ->when(!empty($validated['start_date']), fn ($q) => $q->whereHas('order', fn ($order) => $order->whereDate('order_date', '>=', $validated['start_date'])))
            ->when(!empty($validated['end_date']), fn ($q) => $q->whereHas('order', fn ($order) => $order->whereDate('order_date', '<=', $validated['end_date'])));

        $statsQuery = clone $query;
        $stats = [
            'count' => (clone $statsQuery)->count(),
            'average' => round((float) ((clone $statsQuery)->avg('rating') ?? 0), 1),
            'five_star' => (clone $statsQuery)->where('rating', 5)->count(),
            'low_ratings' => (clone $statsQuery)->where('rating', '<=', 2)->count(),
            'pending' => Review::where('is_approved', false)->count(),
            'published' => Review::where('is_approved', true)->count(),
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
            ->where('order_status', 'completed')
            ->whereDoesntHave('review')
            ->orderByDesc('order_date')
            ->get();

        return view('admin.reviews.create', compact('orders'));
    }

    /** Admin-entered feedback is treated as already reviewed and published. */
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
            ->where('order_status', 'completed')
            ->first();

        if (! $order) {
            throw ValidationException::withMessages(['order_id' => 'Only paid and completed orders linked to a customer can receive a review.']);
        }

        Review::create([
            'order_id' => $order->order_id,
            'customer_id' => $order->customer_id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_approved' => true,
        ]);

        return redirect()->route('reviews.index')->with('success', 'Customer feedback recorded and published.');
    }

    /** A customer can leave one review only for their own paid, completed order. */
    public function storeFromCustomer(Request $request, int $orderId)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $customer = Costumer::query()->where('user_id', auth()->id())->firstOrFail();

        DB::transaction(function () use ($orderId, $customer, $validated): void {
            $order = Order::query()
                ->where('order_id', $orderId)
                ->where('customer_id', $customer->customer_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $order->is_paid || strtolower((string) $order->order_status) !== 'completed') {
                throw ValidationException::withMessages([
                    'review' => 'You can review this order after it has been paid and delivered or collected.',
                ]);
            }

            if ($order->review()->exists()) {
                throw ValidationException::withMessages([
                    'review' => 'A review has already been submitted for this order.',
                ]);
            }

            Review::create([
                'order_id' => $order->order_id,
                'customer_id' => $customer->customer_id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'is_approved' => false,
            ]);
        }, 3);

        return redirect(route('web.orders.show', $orderId).'#review')
            ->with('success', 'Thank you! Your review was submitted and will appear on the store after approval.');
    }

    /** Admin moderation controls whether a customer review is public on the storefront. */
    public function setApproval(Request $request, Review $review)
    {
        $validated = $request->validate([
            'is_approved' => ['required', 'boolean'],
        ]);

        $review->is_approved = (bool) $validated['is_approved'];
        $review->save();

        return back()->with('success', $review->is_approved ? 'Review published on the storefront.' : 'Review hidden from the storefront.');
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

        $review->update($validated);
        return redirect()->route('reviews.index')->with('success', 'Review updated successfully.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return redirect()->route('reviews.index')->with('success', 'Review removed.');
    }
}
