<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Offer;
use App\Models\StockLog;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category');

        $products = Product::with(['category', 'supplier'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('product_name', 'like', "%{$search}%")
                        ->orWhere('product_type', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query) => $query->where('category_id', $category))
            ->orderByDesc('product_id')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::orderBy('category_name')->get();
        return view('admin.product', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('category_name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        return view('admin.product.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255|unique:product,product_name',
            'category_id' => 'required|exists:category,category_id',
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
            'product_type' => 'nullable|string|max:100',
            'status' => 'required|in:Active,Inactive',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = now()->format('YmdHis').'_' . Str::random(8).'.'.$image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);
            $validated['image'] = $imageName;
        }

        $initialQty = (int) $validated['qty'];
        $product = DB::transaction(function () use ($validated, $initialQty) {
            $product = Product::create($validated);
            if ($initialQty > 0) {
                StockLog::create([
                    'product_id' => $product->product_id,
                    'user_id' => auth()->id(),
                    'type' => 'IN',
                    'quantity' => $initialQty,
                    'reason' => 'Opening stock',
                    'note' => 'Opening quantity recorded when product #'.$product->product_id.' was created.',
                ]);
            }
            return $product;
        });

        return redirect()->route('products.index')->with('success', 'Product created successfully; opening stock was logged.');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::orderBy('category_name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        return view('admin.product.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'product_name' => 'required|string|max:255|unique:product,product_name,'.$id.',product_id',
            'category_id' => 'required|exists:category,category_id',
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
            'product_type' => 'nullable|string|max:100',
            'status' => 'required|in:Active,Inactive',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096',
        ]);

        $oldImage = $product->image;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = now()->format('YmdHis').'_' . Str::random(8).'.'.$image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);
            $validated['image'] = $imageName;
        }

        $newQty = (int) $validated['qty'];
        unset($validated['qty']);

        try {
            DB::transaction(function () use ($id, $validated, $newQty) {
                $locked = Product::where('product_id', $id)->lockForUpdate()->firstOrFail();
                $oldQty = (int) $locked->qty;
                $locked->fill($validated);
                $locked->qty = $newQty;
                $locked->save();

                // Keep scheduled and live offer prices aligned when an admin changes
                // the product's regular price. Existing order_items keep their saved price.
                Offer::query()->where('product_id', $locked->product_id)->get()->each(function (Offer $offer) use ($locked) {
                    $offer->new_price = round((float) $locked->price * (1 - ((float) $offer->discount / 100)), 2);
                    $offer->save();
                });

                $difference = $newQty - $oldQty;
                if ($difference !== 0) {
                    StockLog::create([
                        'product_id' => $locked->product_id,
                        'user_id' => auth()->id(),
                        'type' => $difference > 0 ? 'IN' : 'OUT',
                        'quantity' => abs($difference),
                        'reason' => 'Manual adjustment',
                        'note' => "Product edit changed quantity from {$oldQty} to {$newQty}.",
                    ]);
                }
            }, 3);
        } catch (Throwable $exception) {
            return back()->withInput()->with('error', 'Product update failed. No quantity adjustment was saved.');
        }

        if ($request->hasFile('image') && $oldImage && $oldImage !== $validated['image'] && file_exists(public_path('images/'.$oldImage))) {
            @unlink(public_path('images/'.$oldImage));
        }

        return redirect()->route('products.index')->with('success', 'Product updated; any stock change was recorded in inventory logs.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $isReferenced = DB::table('order_items')->where('product_id', $product->product_id)->exists()
            || DB::table('purchases')->where('product_id', $product->product_id)->exists()
            || DB::table('stock_logs')->where('product_id', $product->product_id)->exists()
            || DB::table('offers')->where('product_id', $product->product_id)->exists();

        if ($isReferenced) {
            return back()->with('error', 'This product is part of order, purchase, stock, or offer history. Mark it inactive instead of deleting its audit history.');
        }

        if ($product->image && file_exists(public_path('images/'.$product->image))) {
            @unlink(public_path('images/'.$product->image));
        }
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}
