<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Offer;
use App\Models\Supplier;
use App\Support\SecurePublicImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $category = $request->input('category', $request->input('category_id'));
        $status = $request->input('status');
        $productType = $request->input('product_type');
        $sortColumns = [
            'product_id' => 'product_id',
            'product_name' => 'product_name',
            'price' => 'price',
            'qty' => 'qty',
            'status' => 'status',
            'product_type' => 'product_type',
        ];
        $sortBy = $sortColumns[$request->input('sort_by')] ?? 'product_id';
        $sortDirection = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $products = Product::with(['category', 'supplier'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('product_name', 'like', "%{$search}%")
                        ->orWhere('product_type', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query) => $query->where('category_id', $category))
            ->when(in_array($status, ['Active', 'Inactive'], true), fn ($query) => $query->where('status', $status))
            ->when(in_array($productType, ['stock', 'non-stock'], true), fn ($query) => $query->where('product_type', $productType))
            ->orderBy($sortBy, $sortDirection)
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
            'product_type' => 'required|in:stock,non-stock',
            'status' => 'required|in:Active,Inactive',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096|dimensions:max_width=6000,max_height=6000',
        ]);

        // Product creation never sets stock manually. New products begin at zero;
        // inventory is received through Purchases or explicit Inventory Adjustments.
        $validated['qty'] = 0;

        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = SecurePublicImageUpload::store($request->file('image'), 'images');
            $validated['image'] = $imageName;
        }

        try {
            $product = DB::transaction(fn () => Product::create($validated), 3);
        } catch (\Throwable $exception) {
            SecurePublicImageUpload::delete('images', $imageName);
            throw $exception;
        }

        return redirect()->route('products.index')->with('success', 'Product created successfully. Record received quantities from Purchases or Inventory Adjustments.');
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
            'product_type' => 'required|in:stock,non-stock',
            'status' => 'required|in:Active,Inactive',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096|dimensions:max_width=6000,max_height=6000',
        ]);

        $oldImage = $product->image;
        $newImageName = null;
        if ($request->hasFile('image')) {
            $newImageName = SecurePublicImageUpload::store($request->file('image'), 'images');
            $validated['image'] = $newImageName;
        }

        try {
            $saved = DB::transaction(function () use ($id, $validated) {
                $locked = Product::where('product_id', $id)->lockForUpdate()->firstOrFail();
                $requestedStockTracked = ($validated['product_type'] ?? 'stock') === 'stock';

                // Once a product has sales, purchases, or stock movements, its
                // inventory classification must remain stable for audit accuracy.
                if ($requestedStockTracked !== $locked->isStockTracked()) {
                    $hasInventoryHistory = DB::table('order_items')->where('product_id', $locked->product_id)->exists()
                        || DB::table('purchases')->where('product_id', $locked->product_id)->exists()
                        || DB::table('stock_logs')->where('product_id', $locked->product_id)->exists();
                    $wouldDiscardStock = ! $requestedStockTracked && (int) $locked->qty > 0;

                    if ($hasInventoryHistory || $wouldDiscardStock) {
                        return false;
                    }
                }

                // Preserve current stock. Product details must never overwrite inventory.
                $locked->fill($validated);
                $locked->save();

                // Keep scheduled and live offer prices aligned when an admin changes
                // the product's regular price. Existing order_items keep their saved price.
                Offer::query()->where('product_id', $locked->product_id)->get()->each(function (Offer $offer) use ($locked) {
                    $offer->new_price = round((float) $locked->price * (1 - ((float) $offer->discount / 100)), 2);
                    $offer->save();
                });

                return true;
            }, 3);

            if ($saved === false) {
                SecurePublicImageUpload::delete('images', $newImageName);
                return back()->withInput()->withErrors([
                    'product_type' => 'Product type cannot be changed when stock remains or sales, purchases, or stock movements exist. Preserve the inventory history and create a new product with the correct type.',
                ]);
            }
        } catch (Throwable $exception) {
            SecurePublicImageUpload::delete('images', $newImageName);
            return back()->withInput()->with('error', 'Product update failed. Your inventory quantity was not changed.');
        }

        if ($newImageName && $oldImage && $oldImage !== $newImageName) {
            SecurePublicImageUpload::delete('images', $oldImage);
        }

        return redirect()->route('products.index')->with('success', 'Product details updated. Inventory quantity remains controlled by Purchases and Inventory Adjustments.');
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
