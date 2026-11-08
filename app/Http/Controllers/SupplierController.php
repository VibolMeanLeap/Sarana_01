<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $sortColumns = ['supplier_id' => 'supplier_id', 'name' => 'name'];
        $sortBy = $sortColumns[$request->input('sort_by')] ?? 'supplier_id';
        $sortDirection = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Supplier::query()
            ->when($search !== '', function ($builder) use ($search) {
                $builder->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortBy, $sortDirection);

        $supp_key = $query->paginate(10)->withQueryString();
        $totalSupplier = Supplier::count();

        return view('admin.supplier', compact('supp_key', 'totalSupplier'));
    }

    public function create()
    {
        return view('admin.crud_supp.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'nullable|email|max:255|unique:suppliers,email',
            'phone' => 'nullable|string|max:50',
            'address' => 'required|string|max:255',
        ]);

        Supplier::create($validated);
        session()->flash('supplier_status', 'Supplier created successfully.');

        return response()->json(['status' => 201, 'message' => 'Supplier created successfully.'], 201);
    }

    public function delete(string $supplier_id)
    {
        $supplier = Supplier::findOrFail($supplier_id);
        $isReferenced = DB::table('product')->where('supplier_id', $supplier->supplier_id)->exists()
            || DB::table('purchases')->where('supplier_id', $supplier->supplier_id)->exists();

        if ($isReferenced) {
            return back()->with('error', 'This supplier is linked to products or purchase history and cannot be deleted.');
        }

        $supplier->delete();
        return back()->with('delete_supplier', 'Supplier deleted successfully.');
    }

    public function edit(string $supplier_id)
    {
        $supplier = Supplier::findOrFail($supplier_id);

        return view('admin.crud_supp.edit', compact('supplier'));
    }

    public function update(Request $request, string $supplier_id)
    {
        $supplier = Supplier::findOrFail($supplier_id);
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplier->supplier_id, 'supplier_id')],
            'phone' => 'nullable|string|max:50',
            'address' => 'required|string|max:255',
        ]);

        $supplier->update($validated);

        return redirect()->route('supplier.list')->with('supplier_update', 'Supplier updated successfully.');
    }
}
