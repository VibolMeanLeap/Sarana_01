<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AccountingCategory;
use Illuminate\Http\Request;

class AccountingCategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_type' => 'required|exists:in_and_exp_types,id_type',
        ]);

        AccountingCategory::create($validated);

        return back()->with('success', 'Category added.');
    }

    public function destroy(AccountingCategory $category)
    {
        if ($category->transactions()->exists()) {
            return back()->with('error', 'This category is already used by finance transactions. Keep it to preserve the accounting history.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
