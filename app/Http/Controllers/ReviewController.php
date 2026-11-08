<?php

namespace App\Http\Controllers;

use App\Models\Costumer;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['order', 'customer'])
            ->when($request->filled('rating'), fn ($query) => $query->where('rating', $request->input('rating')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim((string) $request->input('search'));
                $query->where(function ($nested) use ($term) {
                    $nested->where('comment', 'like', "%{$term}%")
                        ->orWhere('order_id', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('start_date'), fn ($query) => $query->whereHas('order', fn ($orderQuery) => $orderQuery->whereDate('order_date', '>=', $request->input('start_date'))))
            ->when($request->filled('end_date'), fn ($query) => $query->whereHas('order', fn ($orderQuery) => $orderQuery->whereDate('order_date', '<=', $request->input('end_date'))))
            ->orderBy(\App\Models\Order::query()->select('order_date')->whereColumn('orders.order_id', 'reviews.order_id'), in_array($request->input('sort_dir'), ['asc', 'desc'], true) ? $request->input('sort_dir') : 'desc')
            ->orderBy('review_id', in_array($request->input('sort_dir'), ['asc', 'desc'], true) ? $request->input('sort_dir') : 'desc')
            ->paginate(15)
            ->withQueryString();

        $avgRating = round((float) (Review::avg('rating') ?? 0), 1);
        return view('admin.review', compact('reviews', 'avgRating'));
    }

    public function create()
    {
        $customers = Costumer::orderByDesc('customer_id')->get();
        $orders = Order::where('is_paid', true)
            ->whereDoesntHave('review')
            ->orderByDesc('order_id')
            ->get();

        return view('admin.reviews.create', compact('customers', 'orders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,order_id|unique:reviews,order_id',
            'customer_id' => 'required|exists:customer,customer_id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        Review::create($validated);

        return redirect()->route('reviews.index')->with('success', 'Review added successfully.');
    }

    public function show(Review $review)
    {
        $review->load(['order', 'customer']);

        return view('admin.reviews.show', compact('review'));
    }

    public function edit(Review $review)
    {
        $customers = Costumer::orderByDesc('customer_id')->get();

        return view('admin.reviews.edit', compact('review', 'customers'));
    }

    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customer,customer_id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
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
