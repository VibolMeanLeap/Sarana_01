<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $offersQuery = Offer::with(['product', 'product.category'])
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->input('product_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->whereHas('product', fn ($productQuery) => $productQuery->where('category_id', $request->input('category_id'))))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim((string) $request->input('search'));
                $query->whereHas('product', fn ($productQuery) => $productQuery->where('product_name', 'like', "%{$term}%"));
            });

        if ($request->input('status') === 'active') {
            $offersQuery->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today);
        } elseif ($request->input('status') === 'upcoming') {
            $offersQuery->whereDate('start_date', '>', $today);
        } elseif ($request->input('status') === 'expired') {
            $offersQuery->whereDate('end_date', '<', $today);
        }
        if ($request->filled('date_from')) $offersQuery->whereDate('start_date', '>=', $request->input('date_from'));
        if ($request->filled('date_to')) $offersQuery->whereDate('end_date', '<=', $request->input('date_to'));

        $sortBy = in_array($request->input('sort_by'), ['start_date', 'end_date', 'discount', 'offer_id'], true) ? $request->input('sort_by') : 'start_date';
        $sortDirection = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $offers = $offersQuery->orderBy($sortBy, $sortDirection)->paginate(15)->withQueryString();

        $offers->getCollection()->transform(function (Offer $offer) use ($today) {
            $start = Carbon::parse($offer->start_date)->toDateString();
            $end = Carbon::parse($offer->end_date)->toDateString();
            $offer->display_status = $today < $start ? 'Upcoming' : ($today <= $end ? 'Active' : 'Expired');
            return $offer;
        });

        $products = Product::orderBy('product_name')->get();
        $categories = \App\Models\Category::orderBy('category_name')->get();
        return view('admin.Offer', compact('offers', 'products', 'categories'));
    }

    public function create()
    {
        $products = Product::query()
            ->where('status', 'Active')
            ->orderBy('product_name')
            ->get();

        return view('admin.offer.Add', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedOffer($request);
        Offer::create($validated);

        return redirect()->route('offer.list')
            ->with('success', 'Offer created. It will appear automatically in the website shop during its scheduled dates.');
    }

    public function edit($id)
    {
        $offer = Offer::findOrFail($id);
        $products = Product::query()->orderBy('product_name')->get();

        return view('admin.offer.Edit', compact('offer', 'products'));
    }

    public function update(Request $request, $id)
    {
        $offer = Offer::findOrFail($id);
        $validated = $this->validatedOffer($request, $offer->offer_id);
        $offer->update($validated);

        return redirect()->route('offer.list')
            ->with('success', 'Offer updated successfully. The website banner and discounted price will use the new details.');
    }

    public function destroy($id)
    {
        $offer = Offer::findOrFail($id);
        $offer->delete();

        return redirect()->route('offer.list')->with('success', 'Offer deleted successfully.');
    }

    /**
     * Validate the schedule and calculate the sale price on the server.
     * The browser preview is only a convenience; it is never trusted for pricing.
     */
    private function validatedOffer(Request $request, ?int $ignoreOfferId = null): array
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:product,product_id'],
            'discount' => ['required', 'numeric', 'gt:0', 'lt:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        if ((float) $product->price <= 0) {
            throw ValidationException::withMessages([
                'product_id' => 'The selected product must have a price greater than zero before an offer can be created.',
            ]);
        }

        $discount = (float) $validated['discount'];
        $newPrice = round((float) $product->price * (1 - ($discount / 100)), 2);
        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        // Avoid two overlapping offers for the same product: checkout must have one clear price.
        $overlap = Offer::query()
            ->where('product_id', $product->product_id)
            ->where('start_date', '<=', $end->format('Y-m-d H:i:s'))
            ->where('end_date', '>=', $start->format('Y-m-d H:i:s'))
            ->when($ignoreOfferId, fn ($query) => $query->where('offer_id', '<>', $ignoreOfferId))
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'product_id' => 'This product already has an offer that overlaps those dates. Edit that offer or choose a different date range.',
            ]);
        }

        return [
            'product_id' => $product->product_id,
            'discount' => round($discount, 2),
            'new_price' => $newPrice,
            // Keep the whole final day eligible. A DATE input saved directly into DATETIME
            // would otherwise expire at 00:00 at the start of the selected end date.
            'start_date' => $start->format('Y-m-d H:i:s'),
            'end_date' => $end->format('Y-m-d H:i:s'),
        ];
    }
}
