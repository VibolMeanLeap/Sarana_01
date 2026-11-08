<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;

class CategoryController extends Controller
{
    // 1. បង្ហាញបញ្ជីប្រភេទផលិតផលទាំងអស់
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $sortColumns = ['category_id' => 'category_id', 'category_name' => 'category_name'];
        $sortBy = $sortColumns[$request->input('sort_by')] ?? 'category_id';
        $sortDirection = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $categories = Category::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('category_name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy($sortBy, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return view('admin.category', compact('categories'));
    }

    // 2. បង្ហាញ Form សម្រាប់បង្កើតប្រភេទផលិតផលថ្មី
    public function create()
    {
        return view('admin.category.create');
    }

    // 3. រក្សាទុកទិន្នន័យប្រភេទផលិតផលថ្មីចូល Database
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'min:2', 'max:255', 'unique:category,category_name'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        Category::create($validated);

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    // 4. បង្ហាញ Form កែប្រែទិន្នន័យប្រភេទផលិតផល
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('admin.category.edit', compact('category'));
    }

    // 5. ធ្វើបច្ចុប្បន្នភាពទិន្នន័យថ្មីទៅក្នុង Database
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'category_name' => ['required', 'string', 'min:2', 'max:255', 'unique:category,category_name,'.$id.',category_id'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    // 6. លុបប្រភេទផលិតផលចោល
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        if (Product::where('category_id', $category->category_id)->exists()) {
            return back()->with('error', 'This category still contains products. Move or deactivate those products before deleting it.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}